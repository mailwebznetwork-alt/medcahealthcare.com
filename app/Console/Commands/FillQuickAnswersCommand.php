<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\MasterSpec\QuickAnswerGenerator;
use Illuminate\Console\Command;

class FillQuickAnswersCommand extends Command
{
    protected $signature = 'medca:fill-quick-answers {--dry-run : Preview without saving}';

    protected $description = 'Auto-generate quick_answer fields from catalog source content (AEO)';

    public function handle(QuickAnswerGenerator $generator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        ServiceCategory::query()->whereNull('quick_answer')->each(function (ServiceCategory $c) use ($generator, $dryRun, &$count): void {
            $generator->fillIfEmpty($c);
            if (filled($c->quick_answer)) {
                $count++;
                if (! $dryRun) {
                    $c->saveQuietly();
                }
            }
        });

        Service::query()->whereNull('quick_answer')->each(function (Service $s) use ($generator, $dryRun, &$count): void {
            $generator->fillIfEmpty($s);
            if (filled($s->quick_answer)) {
                $count++;
                if (! $dryRun) {
                    $s->saveQuietly();
                }
            }
        });

        SubService::query()->whereNull('quick_answer')->each(function (SubService $sub) use ($generator, $dryRun, &$count): void {
            $generator->fillIfEmpty($sub);
            if (filled($sub->quick_answer)) {
                $count++;
                if (! $dryRun) {
                    $sub->saveQuietly();
                }
            }
        });

        $this->info($dryRun ? "Would fill {$count} quick answers" : "Filled {$count} quick answers");

        return self::SUCCESS;
    }
}
