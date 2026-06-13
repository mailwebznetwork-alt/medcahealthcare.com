<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Throwable;

class CacheHealthReportCommand extends Command
{
    protected $signature = 'medca:cache-health-report {--output= : Markdown path}';

    protected $description = 'Report Redis/cache store connectivity and public cache configuration';

    public function handle(): int
    {
        $defaultStore = (string) config('cache.default');
        $publicStore = (string) config('public_cache.store');
        $publicEnabled = filter_var(config('public_cache.enabled', true), FILTER_VALIDATE_BOOLEAN);

        $lines = [
            '# Cache Health Report',
            '',
            'Generated: '.now()->timezone('Asia/Kolkata')->toDateTimeString().' IST',
            '',
            '## Configuration',
            '- Default cache store: `'.$defaultStore.'`',
            '- Public catalog cache store: `'.$publicStore.'`',
            '- Public catalog cache enabled: '.($publicEnabled ? 'yes' : 'no'),
            '- Public cache TTL: '.(int) config('public_cache.ttl', 3600).'s',
            '',
            '## Connectivity',
        ];

        $defaultProbe = $this->probeStore($defaultStore);
        $lines[] = '- Default store (`'.$defaultStore.'`): '.($defaultProbe['ok'] ? 'OK' : 'FAIL — '.$defaultProbe['error']);

        if ($publicStore !== $defaultStore) {
            $publicProbe = $this->probeStore($publicStore);
            $lines[] = '- Public store (`'.$publicStore.'`): '.($publicProbe['ok'] ? 'OK' : 'FAIL — '.$publicProbe['error']);
        }

        if ($defaultStore === 'redis' || $publicStore === 'redis') {
            $redisProbe = $this->probeRedis();
            $lines[] = '- Redis ping: '.($redisProbe['ok'] ? 'OK' : 'FAIL — '.$redisProbe['error']);
        }

        $lines[] = '';
        $lines[] = '## Recommendations';

        if (! $defaultProbe['ok']) {
            $lines[] = '- Fix default cache store connectivity before production traffic.';
        }
        if ($publicEnabled && $publicStore !== $defaultStore) {
            $publicProbe = $this->probeStore($publicStore);
            if (! $publicProbe['ok']) {
                $lines[] = '- Align `PUBLIC_CACHE_STORE` with a working store or disable `PUBLIC_CACHE_ENABLED`.';
            }
        }

        $markdown = implode("\n", $lines)."\n";
        $output = $this->option('output') ?: base_path('docs/CACHE-HEALTH-REPORT.md');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, $markdown);

        $this->info("Cache health report: {$output}");

        return $defaultProbe['ok'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function probeStore(string $store): array
    {
        try {
            $key = 'medca_cache_health_probe';
            Cache::store($store)->put($key, 'ok', 10);
            $value = Cache::store($store)->get($key);
            Cache::store($store)->forget($key);

            if ($value !== 'ok') {
                return ['ok' => false, 'error' => 'read/write mismatch'];
            }

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function probeRedis(): array
    {
        try {
            $pong = Redis::connection()->ping();

            return ['ok' => (string) $pong === 'PONG' || $pong === true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
