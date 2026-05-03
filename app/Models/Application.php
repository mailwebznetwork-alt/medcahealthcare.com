<?php

namespace App\Models;

use App\Enums\ApplicationPipelineStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(ApplicationFactory::class)]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'full_name',
        'email',
        'phone',
        'pin_code',
        'city',
        'cover_message',
        'source',
        'whatsapp_clicked_at',
        'pipeline_status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pipeline_status' => ApplicationPipelineStatus::class,
            'whatsapp_clicked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }
}
