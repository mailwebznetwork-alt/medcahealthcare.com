<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\StoreInterceptRequest;
use App\Models\Intercept;
use App\Services\Growth\WarRoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WarRoomController extends Controller
{
    public function __construct(private readonly WarRoomService $warRoomService) {}

    public function dashboard(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => $this->warRoomService->getDashboard()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'war-room']);
    }

    public function intercepts(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response(['data' => Intercept::query()->latest('id')->limit(100)->get()]);
        }

        return redirect()->route('growth-center.competitors.index', ['tab' => 'war-room']);
    }

    public function storeIntercept(StoreInterceptRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        $this->warRoomService->createIntercept($data);

        return redirect()->route('growth-center.competitors.index', ['tab' => 'war-room'])
            ->with('status', __('War room intercept created.'));
    }

    public function updateIntercept(StoreInterceptRequest $request, int $id): RedirectResponse
    {
        $this->warRoomService->updateStatus($id, $request->validated());

        return redirect()->route('growth-center.competitors.index', ['tab' => 'war-room'])
            ->with('status', __('War room intercept updated.'));
    }
}
