<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageElement extends Model
{
    protected $fillable = [
        'page_slug',
        'section',
        'key',
        'value',
        'type',
    ];
}
