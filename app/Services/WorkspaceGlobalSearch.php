<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Block;
use App\Models\Blog;
use App\Models\Competitor;
use App\Models\Lead;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class WorkspaceGlobalSearch
{
    private const int MIN_LENGTH = 2;

    private const int PER_GROUP = 12;

    /**
     * @return array{q: string, groups: list<array{heading: string, items: list<array{title: string, subtitle: string, url: string}>}>}
     */
    public function search(string $rawQuery): array
    {
        $q = trim($rawQuery);
        if (mb_strlen($q) < self::MIN_LENGTH) {
            return ['q' => $q, 'groups' => []];
        }

        $like = '%'.addcslashes($q, '%_\\').'%';

        /** @var list<array{heading: string, items: list<array{title: string, subtitle: string, url: string}>}> $groups */
        $groups = [];

        $hubNav = $this->hubNavigationShortcuts($q);
        if ($hubNav !== []) {
            $groups[] = $this->group('Hub navigation', $hubNav);
        }

        $groups[] = $this->group('Pages', $this->safeSearch(fn () => Page::query()
            ->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Page $p) => [
                'title' => $p->title,
                'subtitle' => 'Site Architect · Preview · '.$p->slug,
                'url' => route('site-architect.pages.preview', $p),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Blogs', $this->safeSearch(fn () => Blog::query()
            ->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderByDesc('updated_at')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Blog $b) => [
                'title' => $b->title,
                'subtitle' => $b->slug,
                'url' => route('site-architect.blogs.preview', $b),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Blocks', $this->safeSearch(fn () => Block::query()
            ->where(function ($w) use ($like): void {
                $w->where('block_name', 'like', $like)->orWhere('block_slug', 'like', $like);
            })
            ->orderBy('block_name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Block $b) => [
                'title' => $b->block_name,
                'subtitle' => 'Blocks Factory · '.$b->block_slug,
                'url' => route('site-architect.block-factory.index'),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Services', $this->safeSearch(fn () => Service::query()
            ->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)->orWhere('service_code', 'like', $like);
            })
            ->orderBy('title')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Service $s) => [
                'title' => $s->title,
                'subtitle' => 'Operations · Services · '.$s->service_code,
                'url' => route('operations.services.edit', $s),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('PIN codes', $this->safeSearch(fn () => PinCode::query()
            ->where(function ($w) use ($like): void {
                $w->where('pincode', 'like', $like)
                    ->orWhere('area_name', 'like', $like)
                    ->orWhere('city', 'like', $like);
            })
            ->orderBy('pincode')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (PinCode $p) => [
                'title' => $p->pincode.' — '.$p->area_name,
                'subtitle' => $p->is_serviceable ? 'Serviceable' : 'Not serviceable',
                'url' => route('operations.pin-codes.edit', $p),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Bookings / leads', $this->safeSearch(fn () => Lead::query()
            ->where(function ($w) use ($like): void {
                $w->where('phone', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('service', 'like', $like)
                    ->orWhere('message', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Lead $lead) => [
                'title' => filled($lead->phone)
                    ? $lead->phone.(filled($lead->name) ? ' — '.$lead->name : '')
                    : (filled($lead->name) ? $lead->name : ($lead->email ?? 'Lead #'.$lead->id)),
                'subtitle' => 'Lead #'.$lead->id,
                'url' => route('operations.bookings.show', $lead),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Job vacancies', $this->safeSearch(fn () => Vacancy::query()
            ->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('department', 'like', $like);
            })
            ->orderBy('title')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Vacancy $v) => [
                'title' => $v->title,
                'subtitle' => $v->slug,
                'url' => route('operations.job-portal.vacancies.edit', $v),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('People', $this->safeSearch(fn () => User::query()
            ->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (User $u) => [
                'title' => $u->name,
                'subtitle' => $u->email,
                'url' => route('user-management.edit', $u),
            ])
            ->values()
            ->all()));

        $groups[] = $this->group('Competitors', $this->safeSearch(fn () => Competitor::query()
            ->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)->orWhere('website', 'like', $like);
            })
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Competitor $c) => [
                'title' => $c->name,
                'subtitle' => (string) ($c->website ?? ''),
                'url' => route('growth-center.competitors.index'),
            ])
            ->values()
            ->all()));

        $groups = array_values(array_filter($groups, fn (array $g) => $g['items'] !== []));

        return ['q' => $q, 'groups' => $groups];
    }

    /**
     * @param  callable(): Collection<int, array{title: string, subtitle: string, url: string}>|array<int, array{title: string, subtitle: string, url: string}>  $callback
     * @return list<array{title: string, subtitle: string, url: string}>
     */
    private function safeSearch(callable $callback): array
    {
        try {
            $result = $callback();

            return $result instanceof Collection ? $result->values()->all() : $result;
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * @param  list<array{title: string, subtitle: string, url: string}>  $items
     * @return array{heading: string, items: list<array{title: string, subtitle: string, url: string}>}
     */
    private function group(string $heading, array $items): array
    {
        return ['heading' => $heading, 'items' => $items];
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string}>
     */
    private function hubNavigationShortcuts(string $q): array
    {
        $n = mb_strtolower($q);
        $seen = [];
        $out = [];

        $push = function (bool $when, string $title, string $subtitle, string $routeName) use (&$out, &$seen): void {
            if (! $when || ! Route::has($routeName)) {
                return;
            }
            $url = route($routeName);
            if (isset($seen[$url])) {
                return;
            }
            $seen[$url] = true;
            $out[] = ['title' => $title, 'subtitle' => $subtitle, 'url' => $url];
        };

        $push(str_contains($n, 'dashboard') || $n === 'home', __('Dashboard'), __('Executive overview'), 'dashboard');
        $push(str_contains($n, 'operation') || str_contains($n, 'ops') || $n === 'bookings', __('Operations hub'), __('Run-state and queues'), 'modules.operations');
        $push(
            str_contains($n, 'architect') || (str_contains($n, 'site') && str_contains($n, 'content')),
            __('Site Architect'),
            __('Pages, blogs, blocks'),
            'modules.site-architect'
        );
        $push(str_contains($n, 'page') && str_contains($n, 'list'), __('Pages workspace'), __('Site Architect pages'), 'site-architect.pages.index');
        $push(str_contains($n, 'nav') || str_contains($n, 'menu'), __('Navigation menus'), __('Header and footer links'), 'site-architect.navigation.index');
        $push(str_contains($n, 'blog') && ! str_contains($n, 'post'), __('Blogs workspace'), __('Site Architect blogs'), 'site-architect.blogs.index');
        $push(str_contains($n, 'block') || str_contains($n, 'factory'), __('Blocks Factory'), __('Reusable blocks'), 'site-architect.block-factory.index');
        $push(str_contains($n, 'studio'), __('Blocks Studio'), __('Block content & media'), 'site-architect.block-studio.index');
        $push(str_contains($n, 'preset') || str_contains($n, 'template'), __('Templates'), __('Saved block styles'), 'site-architect.presets.index');
        $push(str_contains($n, 'media') || str_contains($n, 'library'), __('Media library'), __('Assets'), 'site-architect.media.index');
        $push(str_contains($n, 'marketing'), __('Marketing'), __('Tracking and campaigns'), 'marketing.dashboard');
        $push(str_contains($n, 'setting'), __('Settings'), __('Workspace configuration'), 'settings.appearance');
        $push(str_contains($n, 'system') || str_contains($n, 'integration'), __('System'), __('Integrations and platform'), 'system.overview');
        $push(str_contains($n, 'growth') || str_contains($n, 'competitor'), __('Growth Center'), __('SEO and intelligence'), 'modules.growth-center');
        $push(str_contains($n, 'health hub') || (str_contains($n, 'seo') && str_contains($n, 'health')), __('Growth readiness'), __('Scores and checklist'), 'growth-center.readiness');
        $push(str_contains($n, 'seo') && str_contains($n, 'entity'), __('SEO entity'), __('Structured data'), 'growth-center.seo.entity');
        $push(str_contains($n, 'technical') && str_contains($n, 'seo'), __('SEO technical'), __('Meta and indexing'), 'growth-center.seo.technical');
        $push(str_contains($n, 'aeo') || str_contains($n, 'llm'), __('AEO / discovery'), __('AI and answer engines'), 'growth-center.aeo.index');
        $push(str_contains($n, 'geo') || str_contains($n, 'location') || str_contains($n, 'gmb'), __('GEO & location'), __('Local presence'), 'growth-center.geo.location');
        $push(str_contains($n, 'war') || str_contains($n, 'intercept'), __('War room'), __('Intercepts'), 'growth-center.war-room');
        $push(str_contains($n, 'readiness'), __('Growth readiness'), __('Scores and checklist'), 'growth-center.readiness');
        $push(str_contains($n, 'ga4'), __('GA4'), __('Analytics dashboard'), 'growth-center.ga4.index');
        $push(str_contains($n, 'pulse') || str_contains($n, 'ai pulse'), __('AI Pulse'), __('AI visibility'), 'growth-center.ai-pulse.index');
        $push(str_contains($n, 'user') || str_contains($n, 'people') || str_contains($n, 'team'), __('User management'), __('Directory'), 'user-management.index');
        $push(str_contains($n, 'security'), __('Security'), __('Posture'), 'modules.security');
        $push(str_contains($n, 'service') && ! str_contains($n, 'serviceable'), __('Services'), __('Operations · services'), 'operations.services.index');
        $push(str_contains($n, 'pin') || str_contains($n, 'postal') || str_contains($n, 'coverage'), __('PIN codes'), __('Coverage matrix'), 'operations.pin-codes.overview');
        $push(str_contains($n, 'job') || str_contains($n, 'vacancy') || str_contains($n, 'career') || str_contains($n, 'hiring'), __('Job portal'), __('Vacancies'), 'operations.job-portal.overview');
        $push(str_contains($n, 'application') || str_contains($n, 'candidate'), __('Applications'), __('Job applications'), 'operations.job-portal.applications.index');

        return $out;
    }
}
