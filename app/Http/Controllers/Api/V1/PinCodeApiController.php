<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PinCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PinCodeApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 100), 250);

        $query = PinCode::query()->with('bangaloreZone')->where('is_active', true);

        if ($request->filled('zone_code')) {
            $query->whereHas('bangaloreZone', fn ($q) => $q->where('code', $request->string('zone_code')));
        }

        if ($request->boolean('serviceable_only')) {
            $query->where('is_serviceable', true);
        }

        $paginator = $query->orderBy('pincode')->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (PinCode $pin) => [
                'pincode' => $pin->pincode,
                'area_name' => $pin->area_name,
                'slug' => $pin->slug,
                'city' => $pin->city,
                'bangalore_zone' => $pin->bangaloreZone?->only(['code', 'name']),
                'is_serviceable' => $pin->is_serviceable,
                'coverage_text' => $pin->coverage_text,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
