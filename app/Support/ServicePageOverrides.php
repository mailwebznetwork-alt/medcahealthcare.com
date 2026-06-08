<?php

namespace App\Support;

use App\Models\Page;

/**
 * Page-level admin authority flags — automated sync must not overwrite protected fields.
 */
final class ServicePageOverrides
{
    public static function titleOverride(Page $page): bool
    {
        return self::flag($page, 'title_override');
    }

    public static function contentOverride(Page $page): bool
    {
        return self::flag($page, 'content_override');
    }

    public static function layoutOverride(Page $page): bool
    {
        return self::flag($page, 'layout_override');
    }

    public static function themeOverride(Page $page): bool
    {
        return self::flag($page, 'theme_override');
    }

    public static function seoOverride(Page $page): bool
    {
        return self::flag($page, 'seo_override');
    }

    public static function aeoOverride(Page $page): bool
    {
        return self::flag($page, 'aeo_override');
    }

    public static function geoOverride(Page $page): bool
    {
        return self::flag($page, 'geo_override');
    }

    public static function adminAuthorityActive(Page $page): bool
    {
        return self::titleOverride($page)
            || self::contentOverride($page)
            || self::layoutOverride($page)
            || self::themeOverride($page)
            || self::seoOverride($page)
            || self::aeoOverride($page)
            || self::geoOverride($page);
    }

    public static function countPagesWithAdminAuthority(): int
    {
        $count = 0;

        Page::query()
            ->select(['id', 'deployment_meta_json'])
            ->orderBy('id')
            ->chunkById(100, function ($pages) use (&$count): void {
                foreach ($pages as $page) {
                    if (self::adminAuthorityActive($page)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Mark all content fields as admin-owned after Site Architect save.
     */
    public static function markAdminSave(Page $page): Page
    {
        $meta = is_array($page->deployment_meta_json) ? $page->deployment_meta_json : [];
        $master = is_array($meta['service_master'] ?? null) ? $meta['service_master'] : [];

        $meta['service_master'] = array_merge($master, [
            'title_override' => true,
            'content_override' => true,
            'layout_override' => true,
            'theme_override' => true,
            'seo_override' => true,
            'aeo_override' => true,
            'geo_override' => true,
            'admin_saved_at' => now()->toIso8601String(),
        ]);

        $page->forceFill(['deployment_meta_json' => $meta])->saveQuietly();

        return $page;
    }

    /**
     * Strip attributes that admin authority protects from automated writes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function filterAutomatedAttributes(Page $page, array $attributes): array
    {
        $protected = [];

        if (self::titleOverride($page)) {
            $protected = array_merge($protected, ['title', 'h1', 'heading_h2', 'heading_h3']);
        }

        if (self::contentOverride($page)) {
            $protected[] = 'content';
        }

        if (self::layoutOverride($page)) {
            $protected[] = 'layout_mode';
        }

        if (self::seoOverride($page)) {
            $protected = array_merge($protected, [
                'meta_title', 'meta_description', 'canonical_url', 'robots_meta',
                'og_title', 'og_description', 'og_image', 'twitter_card',
                'keywords', 'focus_keywords',
            ]);
        }

        if (self::aeoOverride($page)) {
            $protected = array_merge($protected, [
                'aeo_question', 'aeo_answer', 'ai_context', 'search_intent',
            ]);
        }

        if (self::geoOverride($page)) {
            $protected[] = 'entity_tags';
        }

        foreach (array_unique($protected) as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function filterThemeMeta(Page $page, array $meta): array
    {
        if (! self::themeOverride($page)) {
            return $meta;
        }

        unset($meta['style_pack'], $meta['theme_pack'], $meta['style_pack_id']);

        return $meta;
    }

    private static function flag(Page $page, string $key): bool
    {
        $meta = $page->deployment_meta_json;
        if (! is_array($meta)) {
            return false;
        }

        $master = $meta['service_master'] ?? null;
        if (! is_array($master)) {
            return false;
        }

        return (bool) ($master[$key] ?? false);
    }
}
