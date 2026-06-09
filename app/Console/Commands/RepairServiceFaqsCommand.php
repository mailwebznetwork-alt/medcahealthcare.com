<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceFaq;
use App\Support\FaqPairNormalizer;
use Illuminate\Console\Command;

class RepairServiceFaqsCommand extends Command
{
    protected $signature = 'medca:repair-service-faqs';

    protected $description = 'Normalize legacy Q:/A: combined service FAQ rows into separate question and answer records.';

    public function handle(): int
    {
        $repairedServices = 0;
        $createdFaqs = 0;

        Service::query()->with('faqs')->orderBy('id')->chunkById(50, function ($services) use (&$repairedServices, &$createdFaqs): void {
            foreach ($services as $service) {
                if ($service->faqs->isEmpty()) {
                    continue;
                }

                $expanded = FaqPairNormalizer::expandMany($service->faqs);
                if ($expanded === []) {
                    continue;
                }

                $current = $service->faqs->map(fn (ServiceFaq $faq): string => mb_strtolower(trim($faq->question)).'|'.mb_strtolower(trim($faq->answer)))->all();
                $target = array_map(fn (array $faq): string => mb_strtolower($faq['question']).'|'.mb_strtolower($faq['answer']), $expanded);

                if ($current === $target) {
                    continue;
                }

                $service->faqs()->delete();

                foreach ($expanded as $pair) {
                    ServiceFaq::query()->create([
                        'service_id' => $service->id,
                        'question' => $pair['question'],
                        'answer' => $pair['answer'],
                    ]);
                    $createdFaqs++;
                }

                $repairedServices++;
            }
        });

        $this->info("Repaired {$repairedServices} services ({$createdFaqs} FAQ rows written).");

        return self::SUCCESS;
    }
}
