<?php

namespace Database\Seeders;

use App\Models\GlobalContentVariable;
use App\Services\Deployment\GlobalContentVariableRepository;
use Illuminate\Database\Seeder;

/**
 * Launch-time global contact and brand values (single source for phone / WhatsApp in blocks).
 */
class MedcaLaunchGlobalContentSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'company_name' => config('medca.brand_name', 'Medca Health Care'),
            'tagline' => config('medca.tagline', 'Care You Can Trust'),
            'phone_number' => env('MEDCA_PHONE_DISPLAY', '+91 88849 99002'),
            'phone_tel' => env('MEDCA_PHONE_TEL', 'tel:+918884999002'),
            'whatsapp' => env('MEDCA_WHATSAPP_URL', 'https://wa.me/918884999002'),
            'address' => env('MEDCA_LOCATION', 'Arekere Gate, Bannerghatta Road, Bengaluru — 560076'),
            'primary_cta' => 'Book a home visit',
            'secondary_cta' => 'WhatsApp our care team',
        ];

        foreach ($values as $key => $value) {
            GlobalContentVariable::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => (string) (config("global_content_variables.keys.{$key}.label") ?? $key),
                    'value' => $value,
                ]
            );
        }

        GlobalContentVariableRepository::forgetCache();
    }
}
