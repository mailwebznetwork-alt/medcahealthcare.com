<?php

namespace App\Support;

use App\Enums\EmploymentType;
use App\Enums\VacancyWorkflowStatus;
use App\Models\Vacancy;

final class JobPostingSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function forVacancy(Vacancy $vacancy): array
    {
        if ($vacancy->workflow_status !== VacancyWorkflowStatus::Published) {
            return [];
        }

        $orgName = (string) config('careers.organization_name');
        $orgUrl = (string) config('careers.organization_url');
        $logo = config('careers.organization_logo');

        $employment = match ($vacancy->employment_type) {
            EmploymentType::FullTime => 'FULL_TIME',
            EmploymentType::PartTime => 'PART_TIME',
            EmploymentType::Contract => 'CONTRACTOR',
            EmploymentType::Internship => 'INTERN',
            EmploymentType::Other => 'OTHER',
        };

        $jobUrl = route('careers.show', ['slug' => $vacancy->slug]);

        $baseSalary = [];
        if ($vacancy->salary_min !== null) {
            $value = [
                '@type' => 'QuantitativeValue',
                'minValue' => (float) $vacancy->salary_min,
                'unitText' => 'YEAR',
            ];
            if ($vacancy->salary_max !== null) {
                $value['maxValue'] = (float) $vacancy->salary_max;
            }
            $baseSalary['@type'] = 'MonetaryAmount';
            $baseSalary['currency'] = $vacancy->salary_currency;
            $baseSalary['value'] = $value;
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $vacancy->city,
            'addressRegion' => $vacancy->area,
            'addressCountry' => $vacancy->country_code,
        ], fn ($v) => $v !== null && $v !== '');

        $hiringOrganization = [
            '@type' => 'Organization',
            'name' => $orgName,
            'sameAs' => $orgUrl,
        ];
        if (is_string($logo) && $logo !== '') {
            $hiringOrganization['logo'] = $logo;
        }

        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $vacancy->title,
            'description' => strip_tags((string) ($vacancy->description ?: $vacancy->summary)),
            'datePosted' => ($vacancy->published_at ?? $vacancy->updated_at ?? now())->toIso8601String(),
            'employmentType' => $employment,
            'hiringOrganization' => $hiringOrganization,
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => $orgName,
                'value' => (string) $vacancy->id,
            ],
            'url' => $jobUrl,
            'directApply' => true,
        ];

        if ($address !== []) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => $address,
            ];
        }

        if ($baseSalary !== []) {
            $schema['baseSalary'] = $baseSalary;
        }

        if ($vacancy->closing_date !== null) {
            $schema['validThrough'] = $vacancy->closing_date->endOfDay()->toIso8601String();
        }

        if (is_array($vacancy->schema_json) && $vacancy->schema_json !== []) {
            $schema = array_replace_recursive($schema, $vacancy->schema_json);
        }

        return $schema;
    }
}
