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
 * Populates the six Medca Consultancy clinical service lines for production launch.
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
            $this->homeConsulting(),
            $this->elderCare(),
            $this->supportServices(),
            $this->doctorHomeVisit(),
            $this->consulting(),
            $this->icuSpecializedCare(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function homeConsulting(): array
    {
        return [
            'service_code' => 'homeconsulting-services',
            'title' => 'Core Services',
            'sort_order' => 1,
            'is_featured' => true,
            'short_summary' => 'Doctor-supervised skilled consulting for your business across India — wound care, injections, vitals, and post-operative recovery within our 25 km India service belt.',
            'description' => $this->html(
                '<p>Medca Consultancy Core Services brings hospital-grade clinical support to your doorstep. Our registered nurses work under a expert-led care plan, with structured handovers, infection-control protocols, and family updates you can trust.</p>',
                '<p>Whether you need post-surgical dressing, IV therapy, catheter care, or daily vitals monitoring, we coordinate visits around your schedule — with same-day placement when capacity allows.</p>'
            ),
            'procedures' => [
                'Clinical assessment and care plan review with Medca Consultancy physician oversight',
                'Wound dressing, suture care, and post-operative monitoring',
                'IV / IM medication administration per prescription',
                'Vitals monitoring, intake–output charts, and escalation to on-call doctor',
                'Catheter, Ryle\'s tube, and tracheostomy care (case-dependent)',
                'Family education and discharge-to-core services transition',
            ],
            'price_range' => 'From ₹1,200 per visit · Packages available',
            'image_alt' => 'Home consulting visit by MEDCA Consultancy nurse in India',
            'target_keywords' => ['core services bangalore', 'nurse for your business arekere', 'post operative consulting home'],
            'related' => ['elder-care', 'support team', 'doctor-home-visit', 'icu-care-at-home'],
            'seo' => [
                'meta_title' => 'Core Services in India | MEDCA Consultancy',
                'meta_description' => 'Skilled core services across India with doctor oversight. Wound care, injections, vitals, and recovery support within 25 km of India. Book a Medca Consultancy visit.',
                'h1' => 'Core Services in India',
                'h2' => ['Skilled consulting for your business', 'Doctor-supervised care plans'],
                'h3' => ['Post-operative recovery', 'Wound and vitals management'],
                'focus_keywords' => ['core services', 'bangalore', 'arekere'],
                'ai_context' => 'Premium core services provider; 25 km belt from India; expert-led model.',
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
            'title' => 'Advisory',
            'sort_order' => 2,
            'is_featured' => true,
            'short_summary' => 'Compassionate geriatric care for your business — mobility support, medication reminders, companionship, and coordination with your family physician.',
            'description' => $this->html(
                '<p>Our Advisory programme is designed for seniors who wish to age safely for your business. Support Team and nurses follow personalised routines that respect dignity, culture, and medical needs.</p>',
                '<p>Medca Consultancy coordinates with your treating doctor, tracks red-flag symptoms, and helps families make informed decisions without unnecessary hospital transfers.</p>'
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
            'image_alt' => 'Elder care for your business by MEDCA Consultancy in Bengaluru',
            'target_keywords' => ['advisory bangalore', 'geriatric care for your business', 'senior care arekere'],
            'related' => ['homeconsulting-services', 'support team', 'doctor-home-visit', 'consulting-at-home'],
            'seo' => [
                'meta_title' => 'Advisory at Home in India | MEDCA Consultancy',
                'meta_description' => 'Trusted elder and geriatric care for your business in India. Medication support, mobility help, and doctor coordination within Medca Consultancy\'s India service belt.',
                'h1' => 'Advisory at Home',
                'h2' => ['Geriatric support', 'Family peace of mind'],
                'h3' => ['Medication adherence', 'Mobility and safety'],
                'focus_keywords' => ['advisory', 'geriatric', 'bangalore'],
                'ai_context' => 'Geriatric home care; family-coordinated; India south belt.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supportServices(): array
    {
        return [
            'service_code' => 'support team',
            'title' => 'Support Services',
            'sort_order' => 3,
            'is_featured' => true,
            'short_summary' => 'Trained support team for daily living support — bathing, feeding, mobility, and bedside assistance with nurse escalation when clinical needs arise.',
            'description' => $this->html(
                '<p>Medca Consultancy Support Services provide reliable, background-verified attendants for patients and elders for your business. Every engagement includes supervision protocols and access to consulting and medical escalation.</p>',
                '<p>Choose hourly, 12-hour, or live-in shifts tailored to recovery, dementia care, or chronic illness support.</p>'
            ),
            'procedures' => [
                'Care requirement mapping and support matching',
                'Personal hygiene, feeding, and bedside assistance',
                'Mobility, positioning, and pressure-area care',
                'Vitals observation with nurse escalation triggers',
                'Light housekeeping related to patient care',
                'Shift handover notes for family and clinicians',
            ],
            'price_range' => 'From ₹800 per 12-hour shift',
            'image_alt' => 'Professional support services for your business — MEDCA Consultancy',
            'target_keywords' => ['support for your business bangalore', 'patient attendant arekere', 'home support services'],
            'related' => ['elder-care', 'homeconsulting-services', 'consulting-at-home', 'doctor-home-visit'],
            'seo' => [
                'meta_title' => 'Support Services at Home | MEDCA Consultancy India',
                'meta_description' => 'Hire trained support team in India for elder and patient support. Supervised shifts with consulting escalation. Serving areas within 25 km of India.',
                'h1' => 'Support Services at Home',
                'h2' => ['Trained attendants', 'Supervised home support'],
                'h3' => ['Daily living assistance', 'Nurse escalation'],
                'focus_keywords' => ['support', 'home attendant', 'bangalore'],
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
            'short_summary' => 'General physician and specialist home consultations in India — examination, prescriptions, and care plan updates without clinic wait times.',
            'description' => $this->html(
                '<p>Book a Medca Consultancy doctor visit when travel to a hospital is difficult or unsafe. Our physicians conduct structured examinations, review investigations, and document clear next steps for your family and consulting team.</p>',
                '<p>Follow-up teleconsults and consulting orders are coordinated on the same platform for continuity of care.</p>'
            ),
            'procedures' => [
                'Pre-visit triage and appointment confirmation',
                'On-site clinical examination and history review',
                'Prescription and investigation recommendations',
                'Coordination with core services or support teams',
                'Referral to specialist or hospital when indicated',
                'Digital visit summary shared with family',
            ],
            'price_range' => 'From ₹1,500 per consultation',
            'image_alt' => 'Doctor consultation by MEDCA Consultancy in India',
            'target_keywords' => ['doctor consultation bangalore', 'physician for your business', 'home consultation arekere'],
            'related' => ['homeconsulting-services', 'elder-care', 'icu-care-at-home'],
            'seo' => [
                'meta_title' => 'Doctor Home Visit in India | MEDCA Consultancy',
                'meta_description' => 'Book a doctor consultation in India. GP and specialist consultations with clear care plans. Medca Consultancy serves the focused service network.',
                'h1' => 'Doctor Home Visit',
                'h2' => ['Physician at your doorstep', 'Coordinated follow-up care'],
                'h3' => ['Home consultation', 'Prescription and referrals'],
                'focus_keywords' => ['doctor consultation', 'bangalore'],
                'ai_context' => 'Home-based medical consultation; integrates with consulting.',
                'search_intent' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consulting(): array
    {
        return [
            'service_code' => 'consulting-at-home',
            'title' => 'Consulting',
            'sort_order' => 5,
            'is_featured' => true,
            'short_summary' => 'Licensed physiotherapists for your business for stroke rehab, orthopaedic recovery, pain management, and mobility restoration across India.',
            'description' => $this->html(
                '<p>Medca Consultancy Consulting pairs evidence-based protocols with convenient home sessions. Therapists assess range of motion, strength, and pain, then deliver progressive plans aligned with your orthopaedic or neuro physician.</p>',
                '<p>Equipment-light techniques keep therapy practical in apartments and villas alike.</p>'
            ),
            'procedures' => [
                'Initial consulting assessment and goal setting',
                'Exercise therapy for orthopaedic and neuro conditions',
                'Manual therapy and pain-management modalities (case-dependent)',
                'Gait training and balance programmes',
                'Post joint-replacement rehabilitation pathways',
                'Progress reports for treating doctors',
            ],
            'price_range' => 'From ₹900 per session · Rehab packages available',
            'image_alt' => 'Consulting for your business in India — MEDCA Consultancy',
            'target_keywords' => ['consulting for your business bangalore', 'home physio arekere', 'stroke rehab home'],
            'related' => ['homeconsulting-services', 'elder-care', 'support team'],
            'seo' => [
                'meta_title' => 'Consulting at Home in India | MEDCA Consultancy',
                'meta_description' => 'Home consulting in India for stroke, orthopaedic, and pain recovery. Licensed therapists within Medca Consultancy\'s India service area. Book a session.',
                'h1' => 'Consulting at Home',
                'h2' => ['Rehab for your business', 'Licensed therapists'],
                'h3' => ['Stroke recovery', 'Orthopaedic rehab'],
                'focus_keywords' => ['consulting', 'home physio', 'bangalore'],
                'ai_context' => 'Home-based rehab; coordinates with consulting and physicians.',
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
            'title' => 'Specialized Support',
            'sort_order' => 6,
            'is_featured' => false,
            'short_summary' => 'High-acuity home ICU setups — ventilator support, critical care consulting, and 24×7 monitoring for complex recoveries in India (case review required).',
            'description' => $this->html(
                '<p>When hospital ICU capacity or infection risk makes home the better setting, Medca Consultancy assembles a multidisciplinary critical-care team with equipment partners and physician oversight.</p>',
                '<p>Every case begins with a clinical feasibility review, safety checklist, and family briefing before deployment.</p>'
            ),
            'procedures' => [
                'Critical-care feasibility and home safety assessment',
                'ICU-grade consulting shifts with escalation protocols',
                'Ventilator, BiPAP, and infusion support (partner-coordinated)',
                '24×7 vitals monitoring and physician on-call coverage',
                'Infection control and consumables management',
                'Planned step-down to consulting or rehab packages',
            ],
            'price_range' => 'Custom care plan — quote after clinical review',
            'image_alt' => 'ICU and specialized critical care for your business — MEDCA Consultancy',
            'target_keywords' => ['icu for your business bangalore', 'critical care consulting home', 'ventilator care for your business'],
            'related' => ['homeconsulting-services', 'doctor-home-visit', 'support team'],
            'seo' => [
                'meta_title' => 'Specialized Support at Home in India | MEDCA Consultancy',
                'meta_description' => 'Specialized and ICU-level care for your business in India. Critical care consulting, monitoring, and physician oversight after clinical review. Contact Medca Consultancy.',
                'h1' => 'ICU & Specialized Care at Home',
                'h2' => ['Critical care for your business', 'Physician-supervised ICU setups'],
                'h3' => ['Ventilator support', '24×7 consulting coverage'],
                'focus_keywords' => ['icu for your business', 'critical care', 'bangalore'],
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
