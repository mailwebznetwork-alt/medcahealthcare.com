<?php

namespace App\Services;

use App\Models\Block;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class ContentParser
{
    public static function parse(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        $result = preg_replace_callback(
            '/\{\{\s*(block|module)\s*:\s*([^}]+?)\s*\}\}/',
            function (array $matches): string {
                $type = strtolower(trim($matches[1]));
                $slug = trim($matches[2]);

                if ($type === 'module') {
                    $class = config('modules.'.$slug);

                    if (! is_string($class) || $class === '' || ! class_exists($class)) {
                        return '';
                    }

                    return Livewire::mount($class);
                }

                if ($type === 'block') {
                    $block = Block::query()
                        ->where('block_slug', $slug)
                        ->where('is_active', true)
                        ->first();

                    if ($block === null || ! is_string($block->code) || $block->code === '') {
                        return '';
                    }

                    return Blade::render($block->code, []);
                }

                return '';
            },
            $content
        );

        return $result ?? '';
    }
}
