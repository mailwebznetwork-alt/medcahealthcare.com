<?php

namespace App\Services\Integrations\Exotel;

use App\Enums\CallEventStatus;
use App\Models\CallEvent;
use App\Models\CallTrackingNumber;
use App\Services\Marketing\Attribution\CallEventAttributionStitcher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CallEventIngestionService
{
    public function __construct(
        private readonly CallEventAttributionStitcher $attributionStitcher,
    ) {}

    /**
     * @return array{recorded: bool, duplicate: bool, id: ?int}
     */
    public function ingest(Request $request): array
    {
        if (! config('exotel.enabled', false) || ! Schema::hasTable('call_events')) {
            return ['recorded' => false, 'duplicate' => false, 'id' => null];
        }

        $payload = $this->normalizePayload($request);
        $callSid = (string) ($payload['CallSid'] ?? $payload['call_sid'] ?? '');
        if ($callSid === '') {
            return ['recorded' => false, 'duplicate' => false, 'id' => null];
        }

        $eventType = (string) ($payload['EventType'] ?? $payload['event_type'] ?? 'terminal');
        $rawStatus = (string) ($payload['Status'] ?? $payload['CallType'] ?? $payload['status'] ?? '');
        $dateUpdated = (string) ($payload['DateUpdated'] ?? $payload['date_updated'] ?? now()->toDateTimeString());
        $conversationDuration = $this->intValue($payload['ConversationDuration'] ?? $payload['Duration'] ?? $payload['duration'] ?? null);

        $idempotencyKey = hash('sha256', implode('|', [
            'exotel',
            $callSid,
            $eventType,
            $rawStatus,
            $dateUpdated,
        ]));

        if (CallEvent::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            $existing = CallEvent::query()->where('idempotency_key', $idempotencyKey)->first();

            return [
                'recorded' => false,
                'duplicate' => true,
                'id' => $existing?->id,
            ];
        }

        $caller = (string) ($payload['From'] ?? $payload['CallFrom'] ?? $payload['from'] ?? '');
        $called = (string) ($payload['To'] ?? $payload['CallTo'] ?? $payload['to'] ?? '');
        $trackingNumber = $this->resolveTrackingNumber(
            (string) ($payload['PhoneNumberSid'] ?? $payload['phone_number_sid'] ?? ''),
            $called,
        );

        $status = CallEventStatus::fromExotel($rawStatus, $eventType, $conversationDuration);
        $startedAt = $this->parseTimestamp($payload['StartTime'] ?? $payload['start_time'] ?? null);
        $endedAt = $this->parseTimestamp($payload['EndTime'] ?? $payload['end_time'] ?? null);
        $occurredAt = $this->parseTimestamp($dateUpdated) ?? $endedAt ?? $startedAt ?? now();

        $event = CallEvent::query()->create([
            'provider' => 'exotel',
            'provider_call_sid' => $callSid,
            'idempotency_key' => $idempotencyKey,
            'provider_event_type' => $eventType !== '' ? $eventType : null,
            'status' => $status,
            'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            'direction' => (string) ($payload['Direction'] ?? $payload['direction'] ?? '') ?: null,
            'caller_number' => $caller !== '' ? $caller : null,
            'caller_normalized' => $caller !== '' ? CallTrackingNumber::normalizePhone($caller) : null,
            'called_number' => $called !== '' ? $called : null,
            'call_tracking_number_id' => $trackingNumber?->id,
            'duration_seconds' => $conversationDuration,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'recording_url' => (string) ($payload['RecordingUrl'] ?? $payload['recording_url'] ?? '') ?: null,
            'custom_field' => (string) ($payload['CustomField'] ?? $payload['custom_field'] ?? '') ?: null,
            'raw_payload' => $payload,
            'occurred_at' => $occurredAt,
        ]);

        $this->attributionStitcher->stitch($event);

        return [
            'recorded' => true,
            'duplicate' => false,
            'id' => $event->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(Request $request): array
    {
        $json = $request->json()->all();
        if ($json !== []) {
            return $this->flattenLegs($json);
        }

        return $this->flattenLegs($request->all());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function flattenLegs(array $payload): array
    {
        if (isset($payload['Legs']) && is_array($payload['Legs'])) {
            $payload['legs'] = $payload['Legs'];
        }

        return $payload;
    }

    private function resolveTrackingNumber(string $exophoneSid, string $calledNumber): ?CallTrackingNumber
    {
        if (! Schema::hasTable('call_tracking_numbers')) {
            return null;
        }

        if ($exophoneSid !== '') {
            $match = CallTrackingNumber::query()
                ->where('exophone_sid', $exophoneSid)
                ->where('is_active', true)
                ->first();
            if ($match !== null) {
                return $match;
            }
        }

        $normalized = CallTrackingNumber::normalizePhone($calledNumber);
        if ($normalized !== '') {
            $match = CallTrackingNumber::query()
                ->where('phone_normalized', $normalized)
                ->where('is_active', true)
                ->first();
            if ($match !== null) {
                return $match;
            }
        }

        $primary = CallTrackingNumber::findPrimaryActive();
        if ($primary !== null) {
            return $primary;
        }

        return $this->ensurePrimaryTrackingNumber();
    }

    private function ensurePrimaryTrackingNumber(): ?CallTrackingNumber
    {
        $phone = config('exotel.primary_phone_number');
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $normalized = CallTrackingNumber::normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        return CallTrackingNumber::query()->updateOrCreate(
            [
                'provider' => 'exotel',
                'phone_normalized' => $normalized,
            ],
            [
                'exophone_sid' => config('exotel.primary_exophone_sid'),
                'phone_number' => $phone,
                'label' => 'Primary Exotel',
                'is_active' => true,
                'is_primary' => true,
            ],
        );
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function intValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
