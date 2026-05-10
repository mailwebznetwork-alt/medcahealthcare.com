<?php

namespace App\Http\Controllers;

use App\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ModuleSurfaceController extends Controller
{
    public function show(Request $request): View
    {
        /** @var string $key */
        $key = $request->route('momModule');

        if (! is_string($key) || ! ModuleAccess::isValidKey($key)) {
            abort(404);
        }

        $meta = ModuleAccess::navigation()[$key];
        $securityMetrics = null;
        $recentSecurityEvents = [];
        $failedLoginByIp = [];
        $auditLogPreview = collect();
        $firewallRulesList = [];

        if ($key === ModuleAccess::SECURITY) {
            $securityMetrics = [
                'failed_login_attempts' => 0,
                'role_denials' => 0,
                'session_timeouts' => 0,
                'upload_validation_failures' => 0,
            ];

            try {
                if (Schema::hasTable('activity_logs')) {
                    $securityMetrics['failed_login_attempts'] = DB::table('activity_logs')
                        ->where('action', 'login_failure')
                        ->count();
                    $securityMetrics['role_denials'] = DB::table('activity_logs')
                        ->where('action', 'role_violation')
                        ->count();
                    $securityMetrics['session_timeouts'] = DB::table('activity_logs')
                        ->where('action', 'session_timeout')
                        ->count();
                    $securityMetrics['upload_validation_failures'] = DB::table('activity_logs')
                        ->where('action', 'upload_validation_failure')
                        ->count();

                    $recentSecurityEvents = DB::table('activity_logs')
                        ->select('action', 'module', 'description', 'ip_address', 'created_at')
                        ->whereIn('action', [
                            'login_success',
                            'login_failure',
                            'logout',
                            'user_create',
                            'user_update',
                            'user_delete',
                            'unauthorized_access_attempt',
                            'role_violation',
                            'session_timeout',
                            'upload_validation_failure',
                        ])
                        ->orderByDesc('id')
                        ->limit(15)
                        ->get();

                    $failedLoginByIp = DB::table('activity_logs')
                        ->select('ip_address', DB::raw('count(*) as total'))
                        ->where('action', 'login_failure')
                        ->whereNotNull('ip_address')
                        ->groupBy('ip_address')
                        ->orderByDesc('total')
                        ->limit(10)
                        ->get();
                }

                $auditLogPreview = $this->auditLogs();
                $firewallRulesList = $this->firewallRules();
            } catch (Throwable) {
            }
        }

        return view('modules.surface', [
            'title' => $meta['label'],
            'moduleKey' => $key,
            'securityMetrics' => $securityMetrics,
            'recentSecurityEvents' => $recentSecurityEvents,
            'failedLoginByIp' => $failedLoginByIp,
            'auditLogPreview' => $auditLogPreview,
            'firewallRules' => $firewallRulesList,
        ]);
    }

    /**
     * Recent audit rows for the Security workspace (activity_logs).
     *
     * @return Collection<int, object>
     */
    public function auditLogs(): Collection
    {
        if (! Schema::hasTable('activity_logs')) {
            return collect();
        }

        return DB::table('activity_logs')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * Declarative edge/firewall posture surfaced to operators (see config/security.php).
     *
     * @return list<array{name: string, scope: string, rule: string, status: string}>
     */
    public function firewallRules(): array
    {
        /** @var list<array{name: string, scope: string, rule: string, status: string}> $rules */
        $rules = config('security.firewall_rules', []);

        return is_array($rules) ? $rules : [];
    }
}
