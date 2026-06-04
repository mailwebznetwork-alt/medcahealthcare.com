<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

final class ArchitectSaveBypass
{
    public static function eligible(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isRootSuperAdmin()) {
            return true;
        }

        $names = config('architect_save.bypass_operator_names', []);

        return $names !== []
            && in_array(strtolower(trim((string) $user->name)), $names, true);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function findConflict(
        string $modelClass,
        string $column,
        mixed $value,
        ?int $exceptId = null,
    ): ?Model {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $query = $modelClass::query()->where($column, $normalized);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->first();
    }

    public static function releaseUniqueValue(Model $conflict, string $column): void
    {
        $current = trim((string) $conflict->getAttribute($column));
        if ($current === '') {
            return;
        }

        $suffix = '-reassigned-'.$conflict->getKey();
        $base = Str::limit($current, 200, '');
        $next = $base.$suffix;

        if (strlen($next) > 255) {
            $next = Str::limit($base, 255 - strlen($suffix), '').$suffix;
        }

        $conflict->forceFill([$column => $next])->save();
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function stripUniqueRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (is_array($rule)) {
                $rules[$key] = array_values(array_filter(
                    $rule,
                    static fn (mixed $r): bool => ! $r instanceof Unique
                ));
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function relaxRequiredRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rules[$key] = array_values(array_filter(
                $rule,
                static fn (mixed $r): bool => $r !== 'required'
            ));
        }

        return $rules;
    }

    public static function defaultBlockName(string $fallback = ''): string
    {
        $fallback = trim($fallback);

        return $fallback !== '' ? $fallback : __('Untitled block');
    }

    public static function defaultBlockSlug(string $name, string $fallback = ''): string
    {
        $slug = Str::slug($name);
        if ($slug !== '') {
            return $slug;
        }

        $fallback = trim($fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        return 'block-'.Str::lower(Str::random(8));
    }
}
