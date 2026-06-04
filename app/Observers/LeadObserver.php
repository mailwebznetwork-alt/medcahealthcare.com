<?php

namespace App\Observers;

use App\Enums\LeadPipelineStage;
use App\Models\Lead;
use App\Services\Marketing\LeadIntent\LeadIntentRecorder;
use App\Services\Marketing\Pipeline\LeadPipelineService;
use Illuminate\Support\Facades\Schema;

class LeadObserver
{
    public function __construct(
        private readonly LeadPipelineService $pipelineService,
        private readonly LeadIntentRecorder $leadIntentRecorder,
    ) {}

    public function created(Lead $lead): void
    {
        if (! config('marketing_automation.enabled', true)) {
            return;
        }

        if (Schema::hasColumn('leads', 'pipeline_stage')) {
            $this->pipelineService->initialize($lead);
        }

        $this->leadIntentRecorder->recordFromLead($lead);
    }

    public function updated(Lead $lead): void
    {
        if (! config('marketing_automation.enabled', true) || ! Schema::hasColumn('leads', 'pipeline_stage')) {
            return;
        }

        if ($lead->wasChanged('status') && ! $lead->wasChanged('pipeline_stage')) {
            $stage = LeadPipelineStage::fromLegacyStatus($lead->status);
            $current = $lead->pipeline_stage instanceof LeadPipelineStage
                ? $lead->pipeline_stage
                : null;
            if ($current !== $stage) {
                $this->pipelineService->moveStage($lead, $stage, auth()->user());
            }
        }
    }
}
