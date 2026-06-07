<?php

namespace App\Services\Leads;

use App\Enums\LeadStatus;
use App\Jobs\ScoreLeadPayloadJob;
use App\Models\Lead;
use App\Services\Integrations\OutboundWebhookDispatcher;
use App\Services\LeadSourceResolver;
use App\Services\Marketing\Attribution\LeadAttributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Single submission path for API and public web lead forms.
 */
class LeadIngestionService
{
    public function __construct(
        private readonly LeadSourceResolver $sourceResolver,
        private readonly LeadAttributionService $attributionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{status: int, body: array<string, mixed>}
     */
    public function ingest(array $data, Request $request): array
    {
        $name = Str::of(strip_tags((string) ($data['name'] ?? '')))->trim()->toString();
        $phone = Str::of((string) ($data['phone'] ?? ''))->trim()->toString();
        if ($phone === '') {
            return ['status' => 422, 'body' => ['message' => 'Phone is required.']];
        }

        $service = Str::of(strip_tags((string) ($data['service'] ?? '')))->trim()->toString();
        $message = isset($data['message']) ? Str::of(strip_tags((string) $data['message']))->trim()->toString() : null;
        if ($message === '') {
            $message = null;
        }

        $email = $data['email'] ?? null;
        if (is_string($email) && $email !== '') {
            $email = Str::lower(trim($email));
        } else {
            $email = null;
        }

        $source = $this->sourceResolver->resolve(
            isset($data['source']) ? trim((string) $data['source']) : null,
            isset($data['utm_source']) ? trim((string) $data['utm_source']) : null,
            isset($data['gclid']) ? trim((string) $data['gclid']) : null,
            isset($data['fbclid']) ? trim((string) $data['fbclid']) : null,
        );

        $campaign = $data['campaign'] ?? $data['utm_campaign'] ?? null;
        if (is_string($campaign)) {
            $campaign = Str::of(strip_tags($campaign))->trim()->toString();
        }
        if ($campaign === '') {
            $campaign = null;
        }

        $norm = Lead::normalizePhone($phone);
        if ($norm !== '') {
            $duplicate = Lead::query()
                ->where('phone_normalized', $norm)
                ->where('service', $service)
                ->where('created_at', '>', now()->subHours(2))
                ->first();

            if ($duplicate !== null) {
                return [
                    'status' => 200,
                    'body' => [
                        'message' => 'Lead recently captured.',
                        'duplicate' => true,
                        'data' => [
                            'id' => $duplicate->id,
                            'uuid' => $duplicate->uuid,
                        ],
                    ],
                ];
            }
        }

        $lead = new Lead;
        $lead->fill([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'service' => $service,
            'message' => $message,
            'source' => $source,
            'campaign' => $campaign,
            'pin_code_id' => $data['pin_code_id'] ?? null,
            'status' => LeadStatus::New,
        ]);
        $this->attributionService->applyToLead($lead, $data, $request);
        $lead->save();

        ScoreLeadPayloadJob::dispatch($lead);

        app(OutboundWebhookDispatcher::class)->dispatch('lead.created', [
            'lead_id' => $lead->id,
            'uuid' => $lead->uuid,
            'source' => $lead->source instanceof \BackedEnum ? $lead->source->value : (string) $lead->source,
            'service' => $lead->service,
        ]);

        $submissionContext = isset($data['submission_context']) ? trim((string) $data['submission_context']) : '';
        if ($submissionContext === 'contact_form') {
            app(OutboundWebhookDispatcher::class)->dispatch('contact.form.submitted', [
                'lead_id' => $lead->id,
                'uuid' => $lead->uuid,
                'source' => $lead->source instanceof \BackedEnum ? $lead->source->value : (string) $lead->source,
                'service' => $lead->service,
                'submission_context' => $submissionContext,
            ]);
        }

        return [
            'status' => 201,
            'body' => [
                'message' => 'Lead created.',
                'duplicate' => false,
                'data' => [
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                ],
            ],
        ];
    }
}
