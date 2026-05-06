<?php

namespace App\Jobs;

use App\Models\OutboundWebhook;
use App\Services\Webhooks\OutboundWebhookSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class SendOutboundWebhookJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $outboundWebhookId,
        public string $eventKey,
        public array $payload
    ) {}

    public function handle(OutboundWebhookSender $sender): void
    {
        $hook = OutboundWebhook::query()->find($this->outboundWebhookId);
        if ($hook === null || ! $hook->is_enabled) {
            return;
        }

        $sender->send($hook, $this->eventKey, $this->payload);
    }
}
