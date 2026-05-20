<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_id',
    'sort_order',
    'question',
    'answer',
])]
class PageFaq extends Model
{
    protected $table = 'page_faqs';

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
