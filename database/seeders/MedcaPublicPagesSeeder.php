<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the public marketing surface (Home, About Us, Services, Locations, Contact Us)
 * as Page rows composed of editable Block records, plus the corresponding header
 * navigation entries. Idempotent — safe to re-run; existing rows are updated.
 */
class MedcaPublicPagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBlocks();
            $pages = $this->seedPages();
            $this->seedHeaderNavigation($pages);
        });
    }

    private function seedBlocks(): void
    {
        foreach ($this->blockDefinitions() as $definition) {
            Block::query()->updateOrCreate(
                ['block_slug' => $definition['block_slug']],
                [
                    'block_name' => $definition['block_name'],
                    'description' => $definition['description'],
                    'block_type' => $definition['block_type'],
                    'code' => $definition['code'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, Page>
     */
    private function seedPages(): array
    {
        $pages = [];

        foreach ($this->pageDefinitions() as $definition) {
            $page = Page::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $definition['title'],
                    'content' => $this->buildContent($definition['blocks']),
                    'meta_title' => $definition['meta_title'],
                    'meta_description' => $definition['meta_description'],
                    'h1' => $definition['h1'],
                    'is_active' => true,
                ]
            );

            $pages[$definition['slug']] = $page;
        }

        return $pages;
    }

    /**
     * @param  array<string, Page>  $pages
     */
    private function seedHeaderNavigation(array $pages): void
    {
        $order = 0;
        foreach ($this->headerNavigationOrder() as $slug => $label) {
            $page = $pages[$slug] ?? null;
            if (! $page instanceof Page) {
                continue;
            }

            SiteNavigationItem::query()->updateOrCreate(
                [
                    'zone' => SiteNavigationItem::ZONE_HEADER,
                    'page_id' => $page->id,
                ],
                [
                    'sort_order' => $order,
                    'custom_label' => $label,
                ]
            );

            $order++;
        }
    }

    /**
     * @param  list<string>  $blockSlugs
     */
    private function buildContent(array $blockSlugs): string
    {
        return Page::buildContentFromParts(
            array_map(static fn (string $slug): array => ['type' => 'block', 'slug' => $slug], $blockSlugs)
        );
    }

    /**
     * @return list<array{slug: string, title: string, meta_title: string, meta_description: string, h1: string, blocks: list<string>}>
     */
    private function pageDefinitions(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Home',
                'meta_title' => 'Medca Health Care — Premium Home Healthcare in Bangalore',
                'meta_description' => 'Trusted, doctor-led home healthcare across Bangalore: nursing, physiotherapy, lab collection, and 24×7 medical support within 25 km of Arekere.',
                'h1' => 'Premium home healthcare, delivered to your doorstep in Bangalore.',
                'blocks' => [
                    'hero-home',
                    'services-overview-home',
                    'locations-overview-home',
                    'cta-home',
                ],
            ],
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_title' => 'About Medca Health Care — Our Mission, Doctors, and Care Philosophy',
                'meta_description' => 'Medca Health Care is a Bangalore-based premium home healthcare provider, built around a doctor-led care model and a 25 km service belt around Arekere.',
                'h1' => 'About Medca Health Care.',
                'blocks' => [
                    'hero-about',
                    'body-about',
                ],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'meta_title' => 'Medca Services — Nursing, Physiotherapy, Diagnostics & 24×7 Medical Support',
                'meta_description' => 'Explore Medca Health Care services: in-home nursing, physiotherapy, lab sample collection, post-surgical recovery, geriatric care, and 24×7 medical support.',
                'h1' => 'Services that bring the hospital home.',
                'blocks' => [
                    'hero-services',
                    'services-grid-full',
                    'cta-services',
                ],
            ],
            [
                'slug' => 'locations',
                'title' => 'Locations',
                'meta_title' => 'Service Areas — Medca Home Healthcare Across Bangalore',
                'meta_description' => 'We serve a 25 km belt around Arekere — including Bannerghatta Road, BTM, Jayanagar, JP Nagar, Electronic City, HSR, Koramangala and more.',
                'h1' => 'Where Medca cares — across Bangalore.',
                'blocks' => [
                    'hero-locations',
                    'locations-coverage',
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'meta_title' => 'Contact Medca Health Care — Bangalore Home Healthcare',
                'meta_description' => 'Reach Medca Health Care for home nursing, physiotherapy, diagnostics, and medical support across Bangalore. Call, WhatsApp, or request a callback.',
                'h1' => 'Talk to a Medca care advisor.',
                'blocks' => [
                    'hero-contact',
                    'contact-info',
                ],
            ],
        ];
    }

    /**
     * Map of page slug → header label override (null means use the page title).
     *
     * @return array<string, string|null>
     */
    private function headerNavigationOrder(): array
    {
        return [
            'home' => 'Home',
            'about-us' => 'About Us',
            'services' => 'Services',
            'locations' => 'Locations',
            'contact' => 'Contact Us',
        ];
    }

    /**
     * @return list<array{block_slug: string, block_name: string, description: string, block_type: string, code: string}>
     */
    private function blockDefinitions(): array
    {
        return [
            [
                'block_slug' => 'hero-home',
                'block_name' => 'Home — Hero',
                'description' => 'Marketing hero for the public home page.',
                'block_type' => 'Hero',
                'code' => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-gradient-to-br from-[#001f5c] via-[#012a7d] to-[#0046ad] px-6 py-12 text-white shadow-lg md:px-12 md:py-16">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">Premium Home Healthcare · Bangalore</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight md:text-5xl">Premium home healthcare, delivered to your doorstep in Bangalore.</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">Doctor-led nursing, physiotherapy, diagnostics and 24×7 medical support — built for families across a 25 km belt around Arekere.</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="tel:+918884999002" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-[#001f5c] shadow hover:bg-slate-100">Call +91 88849 99002</a>
        <a href="https://wa.me/918884999002" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold text-white hover:bg-white/20">WhatsApp Us</a>
    </div>
</section>
BLADE,
            ],
            [
                'block_slug' => 'services-overview-home',
                'block_name' => 'Home — Services Overview',
                'description' => 'Three-card teaser for the four flagship Medca services on the home page.',
                'block_type' => 'Service Grid',
                'code' => <<<'BLADE'
<section id="services" class="mt-12 scroll-mt-32">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">Services</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900 md:text-3xl">Care that travels to your living room.</h2>
        </div>
        <a href="/p/services" class="hidden text-sm font-semibold text-[#0046ad] hover:underline md:inline-flex">View all services →</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Home Nursing</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">Trained nurses for wound care, IV therapy, post-surgical recovery and elderly care.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Physiotherapy</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">Personalised in-home physiotherapy plans for recovery, mobility and chronic pain.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Diagnostics at Home</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">NABL-accredited lab sample collection from the comfort of your home.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">24×7 Medical Support</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">Doctor-on-call, urgent nursing visits, and continuous care coordination.</p>
        </article>
    </div>
</section>
BLADE,
            ],
            [
                'block_slug' => 'locations-overview-home',
                'block_name' => 'Home — Locations Overview',
                'description' => 'Coverage strip for the home page with pin-code belt around Arekere.',
                'block_type' => 'Sections',
                'code' => <<<'BLADE'
<section id="locations" class="mt-12 scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">Service Belt</p>
    <h2 class="mt-2 text-2xl font-semibold text-slate-900 md:text-3xl">A 25 km belt around Arekere.</h2>
    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 md:text-base">We focus our care depth across Bannerghatta Road, BTM, Jayanagar, JP Nagar, Electronic City, HSR, Koramangala and surrounding pin codes — so a Medca clinician is always close.</p>
    <a href="/p/locations" class="mt-5 inline-flex items-center text-sm font-semibold text-[#0046ad] hover:underline">See all coverage areas →</a>
</section>
BLADE,
            ],
            [
                'block_slug' => 'cta-home',
                'block_name' => 'Home — CTA',
                'description' => 'Closing call-to-action on the home page.',
                'block_type' => 'CTA',
                'code' => <<<'BLADE'
<section id="contact" class="mt-12 scroll-mt-32 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center shadow-sm md:p-10">
    <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">Need care today? We're a call away.</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">Speak to a Medca care advisor and we'll plan a doctor-led visit at home, often within hours.</p>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <a href="tel:+918884999002" class="inline-flex items-center justify-center rounded-xl bg-[#0046ad] px-5 py-3 text-sm font-semibold text-white hover:bg-[#001e5c]">Call +91 88849 99002</a>
        <a href="/p/contact" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">Request Callback</a>
    </div>
</section>
BLADE,
            ],
            [
                'block_slug' => 'hero-about',
                'block_name' => 'About — Hero',
                'description' => 'Hero block for the About Us page.',
                'block_type' => 'Hero',
                'code' => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-white px-6 py-10 shadow-sm md:px-10 md:py-14">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">About Medca</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">Doctor-led, family-centred home healthcare.</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">Medca Health Care is a Bangalore-based premium home healthcare provider serving a 25 km belt around Arekere — built around qualified clinicians, transparent pricing, and quiet, dignified service.</p>
</section>
BLADE,
            ],
            [
                'block_slug' => 'body-about',
                'block_name' => 'About — Body',
                'description' => 'Mission, story and care philosophy for About Us.',
                'block_type' => 'Text',
                'code' => <<<'BLADE'
<section class="mt-8 grid gap-6 md:grid-cols-2">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Our mission</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">Bring hospital-grade care into the home with compassion and clinical rigour, so families never have to choose between safety and comfort.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Our care model</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">Every Medca plan is supervised by a doctor, executed by trained nurses or physiotherapists, and tracked through a single point of accountability.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
        <h2 class="text-xl font-semibold text-slate-900">Why Bangalore families trust us</h2>
        <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600">
            <li>• Doctor-led care plans — not just task-based visits.</li>
            <li>• Verified, trained clinicians with regular audits.</li>
            <li>• Transparent pricing and clear escalation paths.</li>
            <li>• Tight 25 km service belt for fast, reliable response.</li>
        </ul>
    </article>
</section>
BLADE,
            ],
            [
                'block_slug' => 'hero-services',
                'block_name' => 'Services — Hero',
                'description' => 'Hero block for the Services page.',
                'block_type' => 'Hero',
                'code' => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-white px-6 py-10 shadow-sm md:px-10 md:py-14">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">Services</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">Hospital-grade care at home.</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">From short-term recovery to long-term elderly support, every Medca service is doctor-supervised and executed by trained clinicians.</p>
</section>
BLADE,
            ],
            [
                'block_slug' => 'services-grid-full',
                'block_name' => 'Services — Full Grid',
                'description' => 'Full-width service grid covering Medca\'s flagship offerings.',
                'block_type' => 'Service Grid',
                'code' => <<<'BLADE'
<section class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Home Nursing</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">IV therapy, wound dressing, catheter and tracheostomy care, post-surgical recovery and palliative support.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Physiotherapy</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Stroke rehabilitation, orthopaedic recovery, geriatric mobility, neuro and chronic-pain plans.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Diagnostics at Home</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">NABL-accredited lab partners. Phlebotomy, ECG and routine blood/urine collection at your doorstep.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Doctor Visits</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">In-home consultation by general physicians and specialists with prescriptions and follow-up.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Geriatric Care</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Long-term elderly companions, dementia-aware support, daily-living assistance and family reporting.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">24×7 Support</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Doctor-on-call escalation, urgent visits, and continuous coordination with your hospital, if any.</p>
    </article>
</section>
BLADE,
            ],
            [
                'block_slug' => 'cta-services',
                'block_name' => 'Services — CTA',
                'description' => 'Closing CTA for Services page.',
                'block_type' => 'CTA',
                'code' => <<<'BLADE'
<section class="mt-10 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center shadow-sm md:p-8">
    <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">Not sure which service fits?</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">A Medca advisor will speak with your family physician and design a plan that fits your needs and budget.</p>
    <a href="/p/contact" class="mt-5 inline-flex items-center justify-center rounded-xl bg-[#0046ad] px-5 py-3 text-sm font-semibold text-white hover:bg-[#001e5c]">Talk to an advisor</a>
</section>
BLADE,
            ],
            [
                'block_slug' => 'hero-locations',
                'block_name' => 'Locations — Hero',
                'description' => 'Hero block for the Locations page.',
                'block_type' => 'Hero',
                'code' => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-white px-6 py-10 shadow-sm md:px-10 md:py-14">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">Service Areas</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">Where Medca cares — across Bangalore.</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">A focused 25 km belt around Arekere lets us keep response times short and clinician quality high.</p>
</section>
BLADE,
            ],
            [
                'block_slug' => 'locations-coverage',
                'block_name' => 'Locations — Coverage Areas',
                'description' => 'Pin-code/area grid for Locations page.',
                'block_type' => 'Sections',
                'code' => <<<'BLADE'
<section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
    <h2 class="text-xl font-semibold text-slate-900">Areas we cover</h2>
    <div class="mt-5 grid gap-3 text-sm leading-relaxed text-slate-700 md:grid-cols-3">
        <ul class="space-y-1.5">
            <li>Arekere</li>
            <li>Bannerghatta Road</li>
            <li>BTM Layout</li>
            <li>Jayanagar</li>
            <li>JP Nagar</li>
        </ul>
        <ul class="space-y-1.5">
            <li>Koramangala</li>
            <li>HSR Layout</li>
            <li>Electronic City</li>
            <li>Bommanahalli</li>
            <li>Begur</li>
        </ul>
        <ul class="space-y-1.5">
            <li>Hulimavu</li>
            <li>Gottigere</li>
            <li>Hongasandra</li>
            <li>Kudlu Gate</li>
            <li>Singasandra</li>
        </ul>
    </div>
    <p class="mt-5 text-xs leading-relaxed text-slate-500">Don't see your locality? Call us — we extend on request when clinical safety allows.</p>
</section>
BLADE,
            ],
            [
                'block_slug' => 'hero-contact',
                'block_name' => 'Contact — Hero',
                'description' => 'Hero block for the Contact Us page.',
                'block_type' => 'Hero',
                'code' => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-white px-6 py-10 shadow-sm md:px-10 md:py-14">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0046ad]">Contact</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">Talk to a Medca care advisor.</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">Tell us about the care you need and we'll plan a doctor-led visit at home, often within hours.</p>
</section>
BLADE,
            ],
            [
                'block_slug' => 'contact-info',
                'block_name' => 'Contact — Info',
                'description' => 'Contact channels (call, WhatsApp, email, hours) for Contact Us page.',
                'block_type' => 'Text',
                'code' => <<<'BLADE'
<section class="mt-8 grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Call</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">For urgent care or to plan a visit.</p>
        <a href="tel:+918884999002" class="mt-3 inline-flex text-sm font-semibold text-[#0046ad] hover:underline">+91 88849 99002</a>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">WhatsApp</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Send a message and we'll respond fast.</p>
        <a href="https://wa.me/918884999002" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex text-sm font-semibold text-emerald-700 hover:underline">Chat on WhatsApp</a>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Hours</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Care coordination 7 AM – 10 PM. Doctor-on-call escalation 24×7.</p>
    </article>
</section>
BLADE,
            ],
        ];
    }
}
