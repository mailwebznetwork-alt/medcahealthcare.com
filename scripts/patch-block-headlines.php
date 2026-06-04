<?php

$root = dirname(__DIR__);
$dirs = [
    $root.'/resources/views/blocks/shared',
    $root.'/resources/views/blocks/careers',
    $root.'/resources/views/blocks/services',
];
$skip = [
    'form-callback', 'contact-split', 'cta-sticky', 'cta-split', 'cta-simple', 'cta-banner',
    'hero-healthcare', 'hero-home', 'hero-contact', 'hero-about', 'hero-services',
    'hero-locations', 'hero-careers', 'cta-home', 'cta-services', 'contact-info',
    'services-overview-home', 'locations-overview-home', 'body-about', 'locations-coverage',
    'services-grid-full', 'service-detail-hero', 'service-detail-related',
];
$updated = 0;
foreach ($dirs as $dir) {
    foreach (glob($dir.'/*.blade.php') ?: [] as $file) {
        $slug = basename($file, '.blade.php');
        if (in_array($slug, $skip, true)) {
            continue;
        }
        $content = file_get_contents($file);
        if (str_contains($content, 'marketing-headline')) {
            continue;
        }
        if (! preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $content, $matches)) {
            continue;
        }
        $headline = $matches[1];
        $replacement = '<x-blocks.marketing-headline class="text-2xl font-semibold" />';
        $patched = preg_replace(
            '/<h2[^>]*>'.preg_quote($headline, '/').'<\/h2>/',
            $replacement,
            $content,
            1
        );
        if (is_string($patched) && $patched !== $content) {
            file_put_contents($file, $patched);
            $updated++;
        }
    }
}

echo "updated: {$updated}\n";
