<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Module;
use App\Services\DynamicModules\LegacyCustomFieldService;
use App\Services\DynamicModules\LegacyManagedModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait InteractsWithLegacyManagedModules
{
    /**
     * @return array{managedModule: ?Module, customFieldValues: object}
     */
    protected function legacyModuleContext(string $slug, ?Model $record = null): array
    {
        $managedModule = app(LegacyManagedModuleRegistry::class)->findOrRegister($slug);

        return [
            'managedModule' => $managedModule,
            'customFieldValues' => app(LegacyCustomFieldService::class)->valuesObject($record),
        ];
    }

    protected function persistLegacyCustomFields(Request $request, string $slug, Model $record): void
    {
        $module = app(LegacyManagedModuleRegistry::class)->findOrRegister($slug);

        if ($module === null || $module->fieldDefinitions->isEmpty()) {
            return;
        }

        $values = app(LegacyCustomFieldService::class)->validatedValuesForServiceForm($request, $module);
        app(LegacyCustomFieldService::class)->persistOnModel($record, $module, $values);
    }
}
