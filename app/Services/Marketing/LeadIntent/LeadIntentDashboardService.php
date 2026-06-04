<?php

namespace App\Services\Marketing\LeadIntent;

use App\Enums\LeadAttributionBucket;
use App\Enums\LeadIntentChannel;
use App\Models\Lead;
use App\Models\LeadIntentEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadIntentDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function report(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(28)->startOfDay();
        $to = $to ?? now()->endOfDay();

        if (! Schema::hasTable('lead_intent_events')) {
            return $this->emptyReport($from, $to);
        }

        $base = LeadIntentEvent::query()->whereBetween('occurred_at', [$from, $to]);

        $totals = [
            'calls' => (clone $base)->where('channel', LeadIntentChannel::Calls)->count(),
            'whatsapp' => (clone $base)->where('channel', LeadIntentChannel::WhatsApp)->count(),
            'forms' => (clone $base)->where('channel', LeadIntentChannel::Forms)->count(),
        ];
        $totals['total'] = $totals['calls'] + $totals['whatsapp'] + $totals['forms'];

        $byBucket = (clone $base)
            ->select('attribution_bucket', DB::raw('count(*) as total'))
            ->groupBy('attribution_bucket')
            ->pluck('total', 'attribution_bucket')
            ->map(fn ($v) => (int) $v)
            ->all();

        $sourceBreakdown = $this->bucketMatrix($base, 'attribution_bucket');
        $channelBreakdown = $this->channelMatrix($base);
        $campaignBreakdown = $this->campaignMatrix($base);

        $leadsCaptured = 0;
        if (Schema::hasTable('leads')) {
            $leadsCaptured = Lead::query()->whereBetween('created_at', [$from, $to])->count();
        }

        return [
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'label' => $from->format('M j').' – '.$to->format('M j, Y'),
            ],
            'totals' => $totals,
            'leads_captured' => $leadsCaptured,
            'by_bucket' => $this->fillBuckets($byBucket),
            'source_breakdown' => $sourceBreakdown,
            'channel_breakdown' => $channelBreakdown,
            'campaign_breakdown' => $campaignBreakdown,
        ];
    }

    /**
     * @param  Builder<LeadIntentEvent>  $base
     * @return list<array{bucket: string, label: string, calls: int, whatsapp: int, forms: int, total: int}>
     */
    private function bucketMatrix($base, string $column): array
    {
        $rows = [];
        foreach (LeadAttributionBucket::cases() as $bucket) {
            $q = (clone $base)->where($column, $bucket->value);
            $rows[] = [
                'bucket' => $bucket->value,
                'label' => $bucket->label(),
                'calls' => (clone $q)->where('channel', LeadIntentChannel::Calls)->count(),
                'whatsapp' => (clone $q)->where('channel', LeadIntentChannel::WhatsApp)->count(),
                'forms' => (clone $q)->where('channel', LeadIntentChannel::Forms)->count(),
                'total' => (clone $q)->count(),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $rows;
    }

    /**
     * @param  Builder<LeadIntentEvent>  $base
     * @return list<array{channel: string, label: string, total: int}>
     */
    private function channelMatrix($base): array
    {
        $out = [];
        foreach (LeadIntentChannel::cases() as $channel) {
            $out[] = [
                'channel' => $channel->value,
                'label' => $channel->label(),
                'total' => (clone $base)->where('channel', $channel)->count(),
            ];
        }

        return $out;
    }

    /**
     * @param  Builder<LeadIntentEvent>  $base
     * @return list<array{campaign: string, calls: int, whatsapp: int, forms: int, total: int}>
     */
    private function campaignMatrix($base): array
    {
        $campaigns = (clone $base)
            ->whereNotNull('campaign')
            ->where('campaign', '!=', '')
            ->select('campaign')
            ->distinct()
            ->limit(25)
            ->pluck('campaign');

        $rows = [];
        foreach ($campaigns as $campaign) {
            $q = (clone $base)->where('campaign', $campaign);
            $rows[] = [
                'campaign' => (string) $campaign,
                'calls' => (clone $q)->where('channel', LeadIntentChannel::Calls)->count(),
                'whatsapp' => (clone $q)->where('channel', LeadIntentChannel::WhatsApp)->count(),
                'forms' => (clone $q)->where('channel', LeadIntentChannel::Forms)->count(),
                'total' => (clone $q)->count(),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return array_slice($rows, 0, 15);
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function fillBuckets(array $counts): array
    {
        $out = [];
        foreach (LeadAttributionBucket::cases() as $bucket) {
            $out[$bucket->value] = (int) ($counts[$bucket->value] ?? 0);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(Carbon $from, Carbon $to): array
    {
        return [
            'period' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String(), 'label' => ''],
            'totals' => ['calls' => 0, 'whatsapp' => 0, 'forms' => 0, 'total' => 0],
            'leads_captured' => 0,
            'by_bucket' => [],
            'source_breakdown' => [],
            'channel_breakdown' => [],
            'campaign_breakdown' => [],
        ];
    }
}
