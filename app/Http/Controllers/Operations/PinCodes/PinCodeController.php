<?php

namespace App\Http\Controllers\Operations\PinCodes;

use App\Http\Controllers\Concerns\InteractsWithLegacyManagedModules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\PinCodes\StorePinCodeRequest;
use App\Http\Requests\Operations\PinCodes\UpdatePinCodeRequest;
use App\Models\PinCode;
use App\Models\PinCodeImportLog;
use App\Services\DynamicModules\LegacyManagedModuleRegistry;
use App\Services\Governance\PinCodeCreationGuard;
use App\Services\Governance\PinCodeMasterDataAudit;
use App\Services\Operations\PinCodeDeletionService;
use App\Services\Operations\PinCodeGeoDataSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PinCodeController extends Controller
{
    use InteractsWithLegacyManagedModules;

    public function overview(): View
    {
        $this->authorize('viewAny', PinCode::class);

        $metrics = $this->aggregateMetrics();

        $seo = [
            'geo_page_ready' => PinCode::query()->where('geo_page_ready', true)->count(),
            'with_meta_title' => PinCode::query()->whereNotNull('meta_title')->where('meta_title', '!=', '')->count(),
            'with_meta_description' => PinCode::query()->whereNotNull('meta_description')->where('meta_description', '!=', '')->count(),
            'with_seo_keywords' => PinCode::query()->whereNotNull('seo_keywords')->where('seo_keywords', '!=', '')->count(),
        ];

        $importLogs = PinCodeImportLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return view('operations.pin-codes.overview', compact('metrics', 'seo', 'importLogs'));
    }

    public function directory(): View
    {
        $this->authorize('viewAny', PinCode::class);

        return view('operations.pin-codes.directory');
    }

    public function create(): View
    {
        $this->authorize('create', PinCode::class);

        $pinCode = new PinCode([
            'is_serviceable' => true,
            'is_active' => true,
            'geo_page_ready' => false,
        ]);

        return view('operations.pin-codes.create', array_merge(
            compact('pinCode'),
            $this->legacyModuleContext(LegacyManagedModuleRegistry::PIN_CODES),
        ));
    }

    public function store(StorePinCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (($data['slug'] ?? null) === '') {
            $data['slug'] = null;
        }

        $guard = app(PinCodeCreationGuard::class);
        $pincode = $guard->normalizePincode($data['pincode'] ?? null);
        if ($pincode === null || ! $guard->canCreatePincode($pincode, 'ui')) {
            return redirect()->back()->withInput()->with('error', __('This pincode cannot be created.'));
        }

        $payload = collect($data)->except(['landmarks', 'hospitals', 'location_faqs', 'nearby_areas'])->all();
        $restored = $guard->resolveForExplicitRecreate($pincode, 'ui');
        if ($restored !== null) {
            $restored->update($payload);
            $pinCode = $restored->fresh();
        } else {
            $pinCode = PinCode::query()->create($payload);
        }

        app(PinCodeGeoDataSyncService::class)->sync($pinCode, $data);
        $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::PIN_CODES, $pinCode);
        app(PinCodeMasterDataAudit::class)->created($pinCode, 'ui');

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-created');
    }

    public function edit(PinCode $pin_code): View
    {
        $this->authorize('update', $pin_code);

        $pin_code->load(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);

        return view('operations.pin-codes.edit', array_merge(
            ['pinCode' => $pin_code],
            $this->legacyModuleContext(LegacyManagedModuleRegistry::PIN_CODES, $pin_code),
        ));
    }

    public function update(UpdatePinCodeRequest $request, PinCode $pin_code): RedirectResponse
    {
        $data = $request->validated();
        if (($data['slug'] ?? null) === '') {
            $data['slug'] = null;
        }

        $pin_code->update(collect($data)->except(['landmarks', 'hospitals', 'location_faqs', 'nearby_areas'])->all());

        app(PinCodeGeoDataSyncService::class)->sync($pin_code, $data);
        $this->persistLegacyCustomFields($request, LegacyManagedModuleRegistry::PIN_CODES, $pin_code);
        app(PinCodeMasterDataAudit::class)->updated($pin_code, 'ui');

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-updated');
    }

    public function destroy(PinCode $pin_code): RedirectResponse
    {
        $this->authorize('delete', $pin_code);

        app(PinCodeDeletionService::class)->delete($pin_code, 'ui');

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-deleted');
    }

    public function activate(PinCode $pin_code): RedirectResponse
    {
        $this->authorize('changeActiveState', $pin_code);

        $pin_code->update(['is_active' => true]);

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-activated');
    }

    public function deactivate(PinCode $pin_code): RedirectResponse
    {
        $this->authorize('changeActiveState', $pin_code);

        $pin_code->update(['is_active' => false]);

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-deactivated');
    }

    /**
     * @return array{total: int, serviceable: int, non_serviceable: int, active: int}
     */
    private function aggregateMetrics(): array
    {
        return [
            'total' => PinCode::query()->count(),
            'serviceable' => PinCode::query()->where('is_serviceable', true)->count(),
            'non_serviceable' => PinCode::query()->where('is_serviceable', false)->count(),
            'active' => PinCode::query()->where('is_active', true)->count(),
        ];
    }

}
