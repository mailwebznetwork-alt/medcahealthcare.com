<?php

namespace Database\Factories;

use App\Models\OutboundWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundWebhook>
 */
class OutboundWebhookFactory extends Factory
{
    protected $model = OutboundWebhook::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Hook '.$this->faker->unique()->numerify('###'),
            'target_url' => 'https://receiver.example.test/webhook',
            'http_method' => 'POST',
            'secret' => 'whsec_'.$this->faker->sha256(),
            'is_enabled' => true,
            'payload_template' => null,
            'custom_headers' => [],
            'auth_bearer_token' => null,
            'enforce_https' => true,
            'verify_ssl' => true,
            'mapping_rules' => null,
            'allowed_destination_cidrs' => null,
            'max_retries' => 3,
            'timeout_seconds' => 15,
            'sort_order' => 0,
            'events' => ['lead.created'],
        ];
    }
}
