<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RestoreDatabaseBackupRequest;
use App\Services\Settings\MomFullBackupArchive;
use App\Support\BackupOperator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemOperationsController extends Controller
{
    /**
     * Database backup (SQLite snapshot via artisan).
     */
    public function backup(Request $request): RedirectResponse
    {
        $this->authorizeBackupOperator($request);

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
     * Download a freshly generated full-site archive (SQLite + storage trees).
     */
    public function downloadBackup(Request $request): RedirectResponse|BinaryFileResponse
    {
        $this->authorizeBackupOperator($request);

        set_time_limit(0);

        $tmp = tempnam(sys_get_temp_dir(), 'momfb');
        if ($tmp === false) {
            return redirect()
                ->route('settings.backup')
                ->withErrors(['integration' => __('Could not allocate a temporary file for download.')]);
        }

        unlink($tmp);
        $zipPath = $tmp.'.zip';

        try {
            MomFullBackupArchive::fromApplicationDefaults()->createZipAt($zipPath);
            $downloadName = 'medca-full-backup-'.now()->format('Y-m-d-His').'.zip';

            return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
            report($e);

            return redirect()
                ->route('settings.backup')
                ->withErrors(['integration' => $e->getMessage()]);
        }
    }

    /**
     * Restore SQLite, storage trees, and application files from a full-site archive.
     */
    public function restoreBackup(RestoreDatabaseBackupRequest $request): RedirectResponse
    {
        set_time_limit(0);

        $uploaded = $request->file('backup_file');
        if ($uploaded === null) {
            return redirect()
                ->route('settings.backup')
                ->withErrors(['integration' => __('No backup file was uploaded.')]);
        }

        $source = $uploaded->getRealPath() ?: $uploaded->getPathname();

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $safetyZip = $backupDir.'/pre-restore-full-'.now()->format('Y-m-d-His').'.zip';

        try {
            MomFullBackupArchive::fromApplicationDefaults()->restoreFromZipFile($source, $safetyZip);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('settings.backup')
                ->withErrors(['integration' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.backup')
            ->with('status', __('Full site restored from backup. A snapshot of the previous state was saved as a zip under storage/app/backups.'));
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

    protected function authorizeBackupOperator(Request $request): void
    {
        if (! BackupOperator::allows($request->user())) {
            abort(403);
        }
    }
}
