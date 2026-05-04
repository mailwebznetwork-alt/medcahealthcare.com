<?php

namespace App\Services;

use App\Models\Block;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class ContentParser
{
    public static function parse(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $content = preg_replace_callback('/\{\{module:(.*?)\}\}/', function (array $matches): string {
            $slug = trim($matches[1]);
            $class = config('modules.'.$slug);

            if (! is_string($class) || $class === '' || ! class_exists($class)) {
                return '';
            }

            return Livewire::mount($class);
        }, $content);

        $content = preg_replace_callback('/\{\{block:(.*?)\}\}/', function (array $matches): string {
            $slug = trim($matches[1]);
            $block = Block::query()->where('slug', $slug)->first();

            if ($block === null || ! is_string($block->blade_html) || $block->blade_html === '') {
                return '';
            }

            return Blade::render($block->blade_html, []);
        }, $content);

        return $content ?? '';
    }
}
