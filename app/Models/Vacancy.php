<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UseFactory(VacancyFactory::class)]
class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'department',
        'city',
        'area',
        'pin_code',
        'country_code',
        'employment_type',
        'salary_min',
        'salary_max',
        'salary_currency',
        'closing_date',
        'summary',
        'description',
        'requirements',
        'whatsapp_apply_url',
        'seo_title',
        'seo_description',
        'focus_keywords',
        'ai_context',
        'schema_json',
        'visibility',
        'workflow_status',
        'is_active',
        'sort_order',
        'detail_page_id',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'visibility' => VacancyVisibility::class,
            'workflow_status' => VacancyWorkflowStatus::class,
            'closing_date' => 'date',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'detail_page_id' => 'integer',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'schema_json' => 'array',
        ];
    }

    /**
     * @param  Builder<Vacancy>  $query
     * @return Builder<Vacancy>
     */
    public function scopeCareersListing(Builder $query): Builder
    {
        return $query
            ->where('workflow_status', VacancyWorkflowStatus::Published)
            ->where('visibility', VacancyVisibility::Public)
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', now()->toDateString());
            })
            ->orderBy('sort_order')
            ->orderByDesc('published_at');
    }

    /**
     * @param  Builder<Vacancy>  $query
     * @return Builder<Vacancy>
     */
    public function scopeActivePublished(Builder $query): Builder
    {
        return $query
            ->where('workflow_status', VacancyWorkflowStatus::Published)
            ->where('is_active', true);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function detailPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'detail_page_id');
    }

    public static function generateUniqueSlug(string $title, ?string $city, ?string $pinCode): string
    {
        $parts = array_filter([
            Str::slug($title),
            $city ? Str::slug($city) : null,
            $pinCode ? Str::slug($pinCode) : null,
        ]);
        $base = implode('-', $parts) ?: Str::slug($title) ?: 'vacancy';
        $slug = $base;
        $i = 1;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function duplicateAsDraft(): Vacancy
    {
        $copy = $this->replicate([
            'id',
            'slug',
            'published_at',
            'created_at',
            'updated_at',
        ]);
        $copy->title = $this->title.' ('.__('Copy').')';
        $copy->slug = static::generateUniqueSlug($copy->title, $copy->city, $copy->pin_code);
        $copy->workflow_status = VacancyWorkflowStatus::Draft;
        $copy->published_at = null;
        $copy->is_active = true;
        $copy->save();

        return $copy;
    }
}
