<?php

namespace App\Models;

use Database\Factories\ServiceFaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'question',
    'answer',
])]
class ServiceFaq extends Model
{
    /** @use HasFactory<ServiceFaqFactory> */
    use HasFactory;

    protected $table = 'service_faqs';

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
