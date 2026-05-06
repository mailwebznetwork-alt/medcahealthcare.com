<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemOperationsController extends Controller
{
    /**
     * Database backup (SQLite snapshot via artisan).
     */
    public function backup(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $exit = Artisan::call('mom:backup-database');

        if ($exit !== 0) {
            return redirect()
                ->route('settings.backup')
                ->withErrors(['integration' => __('Backup could not complete — check console output (non-SQLite drivers need manual dumps).')]);
        }

        return redirect()
            ->route('settings.backup')
            ->with('status', __('Database backup file created under storage/app/backups.'));
    }

    /**
     * Maintenance mode (Laravel down / up).
     */
    public function maintenance(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'settings_operations_token' => ['required', 'string'],
            'maintenance_action' => ['required', 'in:down,up'],
        ]);

        $expected = config('settings.operations_token');
        if (! is_string($expected) || $expected === '' || ! hash_equals($expected, $validated['settings_operations_token'])) {
            abort(403, __('Invalid operations token.'));
        }

        if ($validated['maintenance_action'] === 'down') {
            $secret = config('settings.maintenance_bypass_secret');
            if (! is_string($secret) || trim($secret) === '') {
                return redirect()
                    ->route('settings.maintenance')
                    ->withErrors(['integration' => __('Set SETTINGS_MAINTENANCE_BYPASS_SECRET in .env before enabling maintenance (used for /{secret} bypass URL).')]);
            }

            Artisan::call('down', ['--secret' => $secret]);

            return redirect()
                ->route('settings.maintenance')
                ->with('status', __('Maintenance mode enabled. Bypass visitors using your Laravel secret URL pattern.'));
        }

        Artisan::call('up');

        return redirect()
            ->route('settings.maintenance')
            ->with('status', __('Maintenance mode disabled.'));
    }

    protected function authorizeSuperAdmin(Request $request): void
    {
        $user = $request->user();
        if ($user === null || strtolower((string) $user->role) !== 'super_admin') {
            abort(403);
        }
    }
}
