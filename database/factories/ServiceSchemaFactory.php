<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSchema>
 */
class ServiceSchemaFactory extends Factory
{
    protected $model = ServiceSchema::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'schema_type' => 'Service',
            'schema_json' => null,
        ];
    }
}
