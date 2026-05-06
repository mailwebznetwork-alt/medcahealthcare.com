<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'outbound_webhook_id',
        'event_key',
        'attempt_number',
        'request_summary',
        'request_payload',
        'response_status',
        'response_body',
        'response_payload',
        'success',
        'error_message',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OutboundWebhook, $this>
     */
    public function outboundWebhook(): BelongsTo
    {
        return $this->belongsTo(OutboundWebhook::class);
    }
}
