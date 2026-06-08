<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceSeo;
use App\Services\Governance\MappingProtectionService;
use App\Services\Governance\MasterDataProtection;
use App\Services\Governance\ServiceCreationGuard;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceRelatedPageTokens;
use Database\Seeders\Support\MedcaLaunchMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populates the six Medca clinical service lines for production launch.
 * Idempotent — safe to re-run.
 */
class MedcaLaunchServicesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app(MasterDataProtection::class)->allowsWrite('seeder')) {
            return;
        }

        DB::transaction(function (): void {
            foreach ($this->definitions() as $definition) {
                $this->upsertService($definition);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertService(array $definition): void
    {
        $code = (string) $definition['service_code'];

        if (! app(ServiceCreationGuard::class)->canCreateService($code, 'seeder')) {
            return;
        }

        $featured = MedcaLaunchMedia::featuredPath($code);
        $gallery = MedcaLaunchMedia::galleryPaths($code);

        $service = Service::query()->updateOrCreate(
            ['service_code' => $code],
            [
                'title' => $definition['title'],
                'short_summary' => $definition['short_summary'],
                'description' => $definition['description'],
                'procedures' => $definition['procedures'],
                'price_range' => $definition['price_range'] ?? null,
                'featured_image' => $featured,
                'gallery' => $gallery,
                'image_alt' => $definition['image_alt'],
                'target_keywords' => $definition['target_keywords'],
                'is_active' => true,
                'is_featured' => (bool) ($definition['is_featured'] ?? false),
                'publish_status' => PublishStatus::Published,
                'visibility' => ServiceVisibility::Public,
                'sort_order' => (int) ($definition['sort_order'] ?? 0),
            ]
        );

        $pinIds = PinCode::eligibleForCoverage()
            ->pluck('id')
            ->all();
        if ($pinIds !== []) {
            $pinIds = app(MappingProtectionService::class)->filterAttachablePinIds($service, $pinIds, 'seeder');
            if ($pinIds !== []) {
                $service->pincodes()->sync($pinIds);
            }
        }

        ServiceSeo::query()->updateOrCreate(
            ['service_id' => $service->id],
            $definition['seo']
        );

        $page = app(ServiceDetailPageProvisioner::class)->provision($service->fresh(['seo']));
        $service->forceFill(['detail_page_id' => $page->id])->save();

        app(ServiceRelatedPageTokens::class)->applyToDetailPage(
            $service->fresh(),
            $definition['related'] ?? []
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            $this->homeNursing(),
            $this->elderCare(),
            $this->caregiverServices(),
            $this->doctorHomeVisit(),
            $this->physiotherapy(),
            $this->icuSpecializedCare(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function homeNursing(): array
    {
        return [
            'service_code' => 'homenursing-services',
            'title' => 'Home Nursing',
            'sort_order' => 1,
            'is_featured' => true,
            'short_summary' => 'Doctor-supervised skilled nursing at home across Bangalore — wound care, injections, vitals, and post-operative recovery within our 25 km Arekere service belt.',
            'description' => $this->html(
                '<p>Medca Home Nursing brings hospital-grade clinical support to your doorstep. Our registered nurses work under a doctor-led care plan, with structured handovers, infection-control protocols, and family updates you can trust.</p>',
                '<p>Whether you need post-surgical dressing, IV therapy, catheter care, or daily vitals monitoring, we coordinate visits around your schedule — with same-day placement when capacity allows.</p>'
            ),
            'procedures' => [
                'Clinical assessment and care plan review with Medca physician oversight',
                'Wound dressing, suture care, and post-operative monitoring',
                'IV / IM medication administration per prescription',
                'Vitals monitoring, intake–output charts, and escalation to on-call doctor',
                'Catheter, Ryle\'s tube, and tracheostomy care (case-dependent)',
                'Family education and discharge-to-home nursing transition',
            ],
            'price_range' => 'From ₹1,200 per visit · Packages available',
            'image_alt' => 'Home nursing visit by Medca Health Care nurse in Bangalore',
            'target_keywords' => ['home nursing bangalore', 'nurse at home arekere', 'post operative nursing home'],
            'related' => ['elder-care', 'caregivers', 'doctor-home-visit', 'icu-care-at-home'],
            'seo' => [
                'meta_title' => 'Home Nursing in Bangalore | Medca Health Care',
                'meta_description' => 'Skilled home nursing across Bangalore with doctor oversight. Wound care, injections, vitals, and recovery support within 25 km of Arekere. Book a Medca visit.',
                'h1' => 'Home Nursing in Bangalore',
                'h2' => ['Skilled nursing at home', 'Doctor-supervised care plans'],
                'h3' => ['Post-operative recovery', 'Wound and vitals management'],
                'focus_keywords' => ['home nursing', 'bangalore', 'arekere'],
                'ai_context' => 'Premium home nursing provider; 25 km belt from Arekere; doctor-led model.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function elderCare(): array
    {
        return [
            'service_code' => 'elder-care',
            'title' => 'Elder Care',
            'sort_order' => 2,
            'is_featured' => true,
            'short_summary' => 'Compassionate geriatric care at home — mobility support, medication reminders, companionship, and coordination with your family physician.',
            'description' => $this->html(
                '<p>Our Elder Care programme is designed for seniors who wish to age safely at home. Caregivers and nurses follow personalised routines that respect dignity, culture, and medical needs.</p>',
                '<p>Medca coordinates with your treating doctor, tracks red-flag symptoms, and helps families make informed decisions without unnecessary hospital transfers.</p>'
            ),
            'procedures' => [
                'Geriatric needs assessment and daily living support plan',
                'Medication reminders and adherence logging',
                'Mobility assistance, transfers, and fall-risk precautions',
                'Vitals checks and physician escalation pathways',
                'Companionship, hydration, and nutrition support',
                'Family counselling and respite care options',
            ],
            'price_range' => 'From ₹900 per shift · Live-in packages on request',
            'image_alt' => 'Elder care at home by Medca Health Care in Bengaluru',
            'target_keywords' => ['elder care bangalore', 'geriatric care at home', 'senior care arekere'],
            'related' => ['homenursing-services', 'caregivers', 'doctor-home-visit', 'physiotherapy-at-home'],
            'seo' => [
                'meta_title' => 'Elder Care at Home in Bangalore | Medca Health Care',
                'meta_description' => 'Trusted elder and geriatric care at home in Bangalore. Medication support, mobility help, and doctor coordination within Medca\'s Arekere service belt.',
                'h1' => 'Elder Care at Home',
                'h2' => ['Geriatric support', 'Family peace of mind'],
                'h3' => ['Medication adherence', 'Mobility and safety'],
                'focus_keywords' => ['elder care', 'geriatric', 'bangalore'],
                'ai_context' => 'Geriatric home care; family-coordinated; Bangalore south belt.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function caregiverServices(): array
    {
        return [
            'service_code' => 'caregivers',
            'title' => 'Caregiver Services',
            'sort_order' => 3,
            'is_featured' => true,
            'short_summary' => 'Trained caregivers for daily living support — bathing, feeding, mobility, and bedside assistance with nurse escalation when clinical needs arise.',
            'description' => $this->html(
                '<p>Medca Caregiver Services provide reliable, background-verified attendants for patients and elders at home. Every engagement includes supervision protocols and access to nursing and medical escalation.</p>',
                '<p>Choose hourly, 12-hour, or live-in shifts tailored to recovery, dementia care, or chronic illness support.</p>'
            ),
            'procedures' => [
                'Care requirement mapping and caregiver matching',
                'Personal hygiene, feeding, and bedside assistance',
                'Mobility, positioning, and pressure-area care',
                'Vitals observation with nurse escalation triggers',
                'Light housekeeping related to patient care',
                'Shift handover notes for family and clinicians',
            ],
            'price_range' => 'From ₹800 per 12-hour shift',
            'image_alt' => 'Professional caregiver services at home — Medca Health Care',
            'target_keywords' => ['caregiver at home bangalore', 'patient attendant arekere', 'home caregiver services'],
            'related' => ['elder-care', 'homenursing-services', 'physiotherapy-at-home', 'doctor-home-visit'],
            'seo' => [
                'meta_title' => 'Caregiver Services at Home | Medca Health Care Bangalore',
                'meta_description' => 'Hire trained caregivers in Bangalore for elder and patient support. Supervised shifts with nursing escalation. Serving areas within 25 km of Arekere.',
                'h1' => 'Caregiver Services at Home',
                'h2' => ['Trained attendants', 'Supervised home support'],
                'h3' => ['Daily living assistance', 'Nurse escalation'],
                'focus_keywords' => ['caregiver', 'home attendant', 'bangalore'],
                'ai_context' => 'Non-clinical and supportive care with clinical backup.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function doctorHomeVisit(): array
    {
        return [
            'service_code' => 'doctor-home-visit',
            'title' => 'Doctor Home Visit',
            'sort_order' => 4,
            'is_featured' => false,
            'short_summary' => 'General physician and specialist home consultations in Bangalore — examination, prescriptions, and care plan updates without clinic wait times.',
            'description' => $this->html(
                '<p>Book a Medca doctor visit when travel to a hospital is difficult or unsafe. Our physicians conduct structured examinations, review investigations, and document clear next steps for your family and nursing team.</p>',
                '<p>Follow-up teleconsults and nursing orders are coordinated on the same platform for continuity of care.</p>'
            ),
            'procedures' => [
                'Pre-visit triage and appointment confirmation',
                'On-site clinical examination and history review',
                'Prescription and investigation recommendations',
                'Coordination with home nursing or caregiver teams',
                'Referral to specialist or hospital when indicated',
                'Digital visit summary shared with family',
            ],
            'price_range' => 'From ₹1,500 per consultation',
            'image_alt' => 'Doctor home visit by Medca Health Care in Bangalore',
            'target_keywords' => ['doctor home visit bangalore', 'physician at home', 'home consultation arekere'],
            'related' => ['homenursing-services', 'elder-care', 'icu-care-at-home'],
            'seo' => [
                'meta_title' => 'Doctor Home Visit in Bangalore | Medca Health Care',
                'meta_description' => 'Book a doctor home visit in Bangalore. GP and specialist consultations with clear care plans. Medca serves the 25 km belt around Arekere.',
                'h1' => 'Doctor Home Visit',
                'h2' => ['Physician at your doorstep', 'Coordinated follow-up care'],
                'h3' => ['Home consultation', 'Prescription and referrals'],
                'focus_keywords' => ['doctor home visit', 'bangalore'],
                'ai_context' => 'Home-based medical consultation; integrates with nursing.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function physiotherapy(): array
    {
        return [
            'service_code' => 'physiotherapy-at-home',
            'title' => 'Physiotherapy',
            'sort_order' => 5,
            'is_featured' => true,
            'short_summary' => 'Licensed physiotherapists at home for stroke rehab, orthopaedic recovery, pain management, and mobility restoration across Bangalore.',
            'description' => $this->html(
                '<p>Medca Physiotherapy pairs evidence-based protocols with convenient home sessions. Therapists assess range of motion, strength, and pain, then deliver progressive plans aligned with your orthopaedic or neuro physician.</p>',
                '<p>Equipment-light techniques keep therapy practical in apartments and villas alike.</p>'
            ),
            'procedures' => [
                'Initial physiotherapy assessment and goal setting',
                'Exercise therapy for orthopaedic and neuro conditions',
                'Manual therapy and pain-management modalities (case-dependent)',
                'Gait training and balance programmes',
                'Post joint-replacement rehabilitation pathways',
                'Progress reports for treating doctors',
            ],
            'price_range' => 'From ₹900 per session · Rehab packages available',
            'image_alt' => 'Physiotherapy at home in Bangalore — Medca Health Care',
            'target_keywords' => ['physiotherapy at home bangalore', 'home physio arekere', 'stroke rehab home'],
            'related' => ['homenursing-services', 'elder-care', 'caregivers'],
            'seo' => [
                'meta_title' => 'Physiotherapy at Home in Bangalore | Medca Health Care',
                'meta_description' => 'Home physiotherapy in Bangalore for stroke, orthopaedic, and pain recovery. Licensed therapists within Medca\'s Arekere service area. Book a session.',
                'h1' => 'Physiotherapy at Home',
                'h2' => ['Rehab at home', 'Licensed therapists'],
                'h3' => ['Stroke recovery', 'Orthopaedic rehab'],
                'focus_keywords' => ['physiotherapy', 'home physio', 'bangalore'],
                'ai_context' => 'Home-based rehab; coordinates with nursing and physicians.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function icuSpecializedCare(): array
    {
        return [
            'service_code' => 'icu-care-at-home',
            'title' => 'ICU / Specialized Care',
            'sort_order' => 6,
            'is_featured' => false,
            'short_summary' => 'High-acuity home ICU setups — ventilator support, critical care nursing, and 24×7 monitoring for complex recoveries in Bangalore (case review required).',
            'description' => $this->html(
                '<p>When hospital ICU capacity or infection risk makes home the better setting, Medca assembles a multidisciplinary critical-care team with equipment partners and physician oversight.</p>',
                '<p>Every case begins with a clinical feasibility review, safety checklist, and family briefing before deployment.</p>'
            ),
            'procedures' => [
                'Critical-care feasibility and home safety assessment',
                'ICU-grade nursing shifts with escalation protocols',
                'Ventilator, BiPAP, and infusion support (partner-coordinated)',
                '24×7 vitals monitoring and physician on-call coverage',
                'Infection control and consumables management',
                'Planned step-down to nursing or rehab packages',
            ],
            'price_range' => 'Custom care plan — quote after clinical review',
            'image_alt' => 'ICU and specialized critical care at home — Medca Health Care',
            'target_keywords' => ['icu at home bangalore', 'critical care nursing home', 'ventilator care at home'],
            'related' => ['homenursing-services', 'doctor-home-visit', 'caregivers'],
            'seo' => [
                'meta_title' => 'ICU Care at Home in Bangalore | Medca Health Care',
                'meta_description' => 'Specialized and ICU-level care at home in Bangalore. Critical care nursing, monitoring, and physician oversight after clinical review. Contact Medca.',
                'h1' => 'ICU & Specialized Care at Home',
                'h2' => ['Critical care at home', 'Physician-supervised ICU setups'],
                'h3' => ['Ventilator support', '24×7 nursing coverage'],
                'focus_keywords' => ['icu at home', 'critical care', 'bangalore'],
                'ai_context' => 'High-acuity home care; requires clinical approval.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function html(string ...$paragraphs): string
    {
        return implode("\n", $paragraphs);
    }
}
