<?php

namespace App\Enums;

enum PageCategory: string
{
    case Web = 'web';
    case Category = 'category';
    case Service = 'service';
    case SubService = 'sub_service';
    case Location = 'location';
    case Blog = 'blog';
    case Landing = 'landing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Web => __('Web Pages'),
            self::Category => __('Category Pages'),
            self::Service => __('Service Pages'),
            self::SubService => __('Sub Service Pages'),
            self::Location => __('Location Pages'),
            self::Blog => __('Blog Pages'),
            self::Landing => __('Landing Pages'),
            self::Other => __('Other'),
        };
    }
}
