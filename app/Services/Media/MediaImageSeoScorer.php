<?php

namespace App\Services\Media;

use App\Models\Media;

class MediaImageSeoScorer
{
    public function score(Media $media): int
    {
        if ($media->file_type !== 'image') {
            return 0;
        }

        $score = 10;

        if (filled($media->alt_text)) {
            $score += 35;
        }
        if (filled($media->title)) {
            $score += 15;
        }
        if (filled($media->caption)) {
            $score += 10;
        }
        if (filled($media->description)) {
            $score += 15;
        }
        if (filled($media->tags)) {
            $score += 10;
        }
        if (filled($media->category)) {
            $score += 5;
        }
        if (filled($media->webp_path)) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * @return list<string>
     */
    public function recommendations(Media $media): array
    {
        if ($media->file_type !== 'image') {
            return [];
        }

        $tips = [];
        if (! filled($media->alt_text)) {
            $tips[] = __('Add descriptive alt text for accessibility and image SEO.');
        }
        if (! filled($media->title)) {
            $tips[] = __('Add an image title to reinforce the page topic.');
        }
        if (! filled($media->caption)) {
            $tips[] = __('A short caption helps AI discovery and rich results.');
        }
        if (! filled($media->description)) {
            $tips[] = __('Add a longer description for entity-based search.');
        }

        return $tips;
    }

    public function persist(Media $media): Media
    {
        $media->image_seo_score = $this->score($media);
        $media->save();

        return $media;
    }
}
