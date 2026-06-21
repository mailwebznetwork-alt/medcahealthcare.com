<?php

namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Page;
use App\Support\SqliteDatabaseFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateRestorePointCommand extends Command
{
    protected $signature = 'mom:create-restore-point
                            {--label= : Folder name under storage/app/restore-points (default: Y-m-d_His IST)}';

    protected $description = 'Snapshot CMS pages/blocks to JSON and copy the SQLite database for a named restore point.';

    public function handle(): int
    {
        $label = $this->option('label')
            ?: now()->timezone('Asia/Kolkata')->format('Y-m-d_His');

        $dir = storage_path('app/restore-points/'.$label);
        File::ensureDirectoryExists($dir);

        $gitHead = $this->gitHead();

        $manifest = [
            'label' => $label,
            'created_at_utc' => now()->utc()->toIso8601String(),
            'created_at_ist' => now()->timezone('Asia/Kolkata')->toDateTimeString(),
            'git_head' => $gitHead,
            'pages' => Page::query()->orderBy('slug')->get()->map(fn (Page $page): array => [
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'layout_mode' => $page->layout_mode?->value ?? $page->layout_mode,
                'is_active' => $page->is_active,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ])->values()->all(),
            'blocks' => Block::query()->orderBy('block_slug')->get()->map(fn (Block $block): array => [
                'block_slug' => $block->block_slug,
                'block_name' => $block->block_name,
                'code' => $block->code,
                'custom_css' => $block->custom_css,
                'is_active' => $block->is_active,
                'block_type' => $block->block_type,
            ])->values()->all(),
        ];

        $jsonPath = $dir.'/cms-snapshot.json';
        File::put($jsonPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $dbCopyPath = null;
        if (config('database.default') === 'sqlite') {
            $src = SqliteDatabaseFile::defaultConnectionFilesystemPath();
            if ($src !== null && File::exists($src)) {
                $dbCopyPath = $dir.'/database.sqlite';
                File::copy($src, $dbCopyPath);
            }
        }

        $readme = <<<TXT
Medca Consultancy restore point: {$label}
Created (IST): {$manifest['created_at_ist']}
Git HEAD: {$gitHead}

CMS snapshot: cms-snapshot.json
SQLite copy: database.sqlite (if present)

Restore code: git checkout restore-point-{$label}
Restore DB (SQLite): stop app, copy database.sqlite over your live DB file, restart.
Restore CMS only: php artisan mom:restore-restore-point {$label}
TXT;

        File::put($dir.'/README.txt', $readme);

        $this->info(__('Restore point created: :dir', ['dir' => $dir]));
        $this->line('  cms-snapshot.json ('.count($manifest['pages']).' pages, '.count($manifest['blocks']).' blocks)');
        if ($dbCopyPath !== null) {
            $this->line('  database.sqlite');
        } else {
            $this->warn(__('No SQLite file copied — use mysqldump/pg_dump for this connection.'));
        }

        return self::SUCCESS;
    }

    private function gitHead(): ?string
    {
        $headFile = base_path('.git/HEAD');
        if (! File::exists($headFile)) {
            return null;
        }

        $head = trim(File::get($headFile));
        if (! str_starts_with($head, 'ref: ')) {
            return $head;
        }

        $ref = base_path('.git/'.substr($head, 5));

        return File::exists($ref) ? trim(File::get($ref)) : null;
    }
}
