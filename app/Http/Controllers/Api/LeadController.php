<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadSourceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function __construct(
        private LeadSourceResolver $sourceResolver
    ) {}

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $name = Str::of(strip_tags($data['name']))->trim()->toString();
        $phone = Str::of($data['phone'])->trim()->toString();
        if ($phone === '') {
            return response()->json(['message' => 'Phone is required.'], 422);
        }

        $service = Str::of(strip_tags($data['service']))->trim()->toString();
        $message = isset($data['message']) ? Str::of(strip_tags($data['message']))->trim()->toString() : null;
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
                return response()->json([
                    'message' => 'Lead recently captured.',
                    'duplicate' => true,
                    'data' => [
                        'id' => $duplicate->id,
                        'uuid' => $duplicate->uuid,
                    ],
                ], 200);
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
        $lead->save();

        return response()->json([
            'message' => 'Lead created.',
            'duplicate' => false,
            'data' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
            ],
        ], 201);
    }
}
