<?php

namespace App\Http\Controllers\Operations\PinCodes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\PinCodes\StorePinCodeRequest;
use App\Http\Requests\Operations\PinCodes\UpdatePinCodeRequest;
use App\Models\PinCode;
use App\Models\PinCodeImportLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PinCodeController extends Controller
{
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

    public function directory(Request $request): View
    {
        $this->authorize('viewAny', PinCode::class);

        $query = $this->filteredPinCodeQuery($request);

        /** @var LengthAwarePaginator<int, PinCode> $pinCodes */
        $pinCodes = $query->paginate(20)->withQueryString();

        $cities = PinCode::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('operations.pin-codes.directory', compact('pinCodes', 'cities'));
    }

    public function create(): View
    {
        $this->authorize('create', PinCode::class);

        $pinCode = new PinCode([
            'is_serviceable' => true,
            'is_active' => true,
            'geo_page_ready' => false,
        ]);

        return view('operations.pin-codes.create', compact('pinCode'));
    }

    public function store(StorePinCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (($data['slug'] ?? null) === '') {
            $data['slug'] = null;
        }

        PinCode::query()->create($data);

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-created');
    }

    public function edit(PinCode $pin_code): View
    {
        $this->authorize('update', $pin_code);

        return view('operations.pin-codes.edit', ['pinCode' => $pin_code]);
    }

    public function update(UpdatePinCodeRequest $request, PinCode $pin_code): RedirectResponse
    {
        $data = $request->validated();
        if (($data['slug'] ?? null) === '') {
            $data['slug'] = null;
        }

        $pin_code->update($data);

        return redirect()->route('operations.pin-codes.directory')->with('status', 'pin-code-updated');
    }

    public function destroy(PinCode $pin_code): RedirectResponse
    {
        $this->authorize('delete', $pin_code);

        $pin_code->delete();

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

    private function filteredPinCodeQuery(Request $request): Builder
    {
        $query = PinCode::query()->orderBy('city')->orderBy('pincode');

        if ($q = trim((string) $request->query('q', ''))) {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('pincode', 'like', $like)
                    ->orWhere('area_name', 'like', $like)
                    ->orWhere('locality', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        }

        if ($city = trim((string) $request->query('city', ''))) {
            $query->where('city', $city);
        }

        $serviceable = (string) $request->query('serviceable', '');
        if ($serviceable === '1') {
            $query->where('is_serviceable', true);
        } elseif ($serviceable === '0') {
            $query->where('is_serviceable', false);
        }

        $active = (string) $request->query('active', '');
        if ($active === '1') {
            $query->where('is_active', true);
        } elseif ($active === '0') {
            $query->where('is_active', false);
        }

        return $query;
    }
}
