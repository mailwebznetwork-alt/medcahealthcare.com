<?php

namespace App\Services\Marketing\Attribution;

use App\Enums\CallEventStatus;
use App\Models\Admission;
use App\Models\CallEvent;
use App\Models\Lead;
use App\Models\RevenueEvent;
use App\Models\MarketingClickEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttributionReportingService
{
    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topServices(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topLeadDimension('service_id', 'services', 'title', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topPincodes(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topLeadDimension('pin_code_id', 'pin_codes', 'pincode', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topCampaigns(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        if (! Schema::hasColumn('leads', 'utm_campaign')) {
            return collect();
        }

        $query = Lead::query()
            ->whereNotNull('utm_campaign')
            ->select('utm_campaign as label', DB::raw('count(*) as total'))
            ->groupBy('utm_campaign')
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'created_at');

        return $query->get();
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topLandingPages(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        $query = Lead::query()
            ->whereNotNull('landing_page')
            ->select('landing_page as label', DB::raw('count(*) as total'))
            ->groupBy('landing_page')
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'created_at');

        return $query->get();
    }

    /**
     * @return array{clicks: int, leads: int, stitched_clicks: int}
     */
    public function clickToLeadSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('marketing_click_events')) {
            return ['clicks' => 0, 'leads' => 0, 'stitched_clicks' => 0];
        }

        $clickQuery = MarketingClickEvent::query()
            ->whereIn('event_type', config('marketing_attribution.phone_click_event_types', ['phone_click', 'whatsapp_click']));

        $this->applyDateRange($clickQuery, $from, $to, 'occurred_at');

        $leadQuery = Lead::query();
        $this->applyDateRange($leadQuery, $from, $to, 'created_at');

        $stitchedQuery = (clone $clickQuery)->whereNotNull('lead_id');

        return [
            'clicks' => $clickQuery->count(),
            'leads' => $leadQuery->count(),
            'stitched_clicks' => $stitchedQuery->count(),
        ];
    }

    /**
     * @return array{total: int, admitted: int, discharged: int, pending: int}
     */
    public function admissionSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('admissions')) {
            return ['total' => 0, 'admitted' => 0, 'discharged' => 0, 'pending' => 0];
        }

        $base = Admission::query();
        $this->applyDateRange($base, $from, $to, 'created_at');

        return [
            'total' => (clone $base)->count(),
            'admitted' => (clone $base)->where('status', 'admitted')->count(),
            'discharged' => (clone $base)->where('status', 'discharged')->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
        ];
    }

    /**
     * @return array{total_revenue: float, events: int, currency: string}
     */
    public function revenueSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('revenue_events')) {
            return ['total_revenue' => 0.0, 'events' => 0, 'currency' => 'INR'];
        }

        $query = RevenueEvent::query();
        $this->applyDateRange($query, $from, $to, 'recorded_at');

        return [
            'total_revenue' => (float) (clone $query)->sum('amount'),
            'events' => (clone $query)->count(),
            'currency' => 'INR',
        ];
    }

    /**
     * @return Collection<int, object{label: string, total: float}>
     */
    public function topRevenueServices(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topRevenueDimension('service_id', 'services', 'title', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: float}>
     */
    public function topRevenuePincodes(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topRevenueDimension('pin_code_id', 'pin_codes', 'pincode', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: float}>
     */
    public function topRevenueCampaigns(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        if (! Schema::hasTable('revenue_events') || ! Schema::hasTable('marketing_attribution_sessions')) {
            return collect();
        }

        $query = RevenueEvent::query()
            ->join('marketing_attribution_sessions', 'revenue_events.marketing_attribution_session_id', '=', 'marketing_attribution_sessions.id')
            ->whereNotNull('marketing_attribution_sessions.utm_campaign')
            ->select('marketing_attribution_sessions.utm_campaign as label', DB::raw('sum(revenue_events.amount) as total'))
            ->groupBy('marketing_attribution_sessions.utm_campaign')
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'revenue_events.recorded_at');

        return $query->get();
    }

    /**
     * @return Collection<int, object{label: string, total: float}>
     */
    private function topRevenueDimension(
        string $foreignKey,
        string $table,
        string $labelColumn,
        ?Carbon $from,
        ?Carbon $to,
        int $limit,
    ): Collection {
        if (! Schema::hasTable('revenue_events') || ! Schema::hasColumn('revenue_events', $foreignKey)) {
            return collect();
        }

        $query = RevenueEvent::query()
            ->join($table, "revenue_events.{$foreignKey}", '=', "{$table}.id")
            ->whereNotNull("revenue_events.{$foreignKey}")
            ->select("{$table}.{$labelColumn} as label", DB::raw('sum(revenue_events.amount) as total'))
            ->groupBy("{$table}.{$labelColumn}")
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'revenue_events.recorded_at');

        return $query->get();
    }

    /**
     * @return array{total: int, connected: int, missed: int, busy: int, failed: int, stitched: int}
     */
    public function callSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('call_events')) {
            return [
                'total' => 0,
                'connected' => 0,
                'missed' => 0,
                'busy' => 0,
                'failed' => 0,
                'stitched' => 0,
            ];
        }

        $base = CallEvent::query();
        $this->applyDateRange($base, $from, $to, 'occurred_at');

        return [
            'total' => (clone $base)->count(),
            'connected' => (clone $base)->whereIn('status', [
                CallEventStatus::Connected->value,
                CallEventStatus::Completed->value,
            ])->count(),
            'missed' => (clone $base)->where('status', CallEventStatus::Missed->value)->count(),
            'busy' => (clone $base)->where('status', CallEventStatus::Busy->value)->count(),
            'failed' => (clone $base)->where('status', CallEventStatus::Failed->value)->count(),
            'stitched' => (clone $base)->whereNotNull('marketing_click_event_id')->count(),
        ];
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topCallServices(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topCallDimension('service_id', 'services', 'title', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topCallPincodes(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): Collection
    {
        return $this->topCallDimension('pin_code_id', 'pin_codes', 'pincode', $from, $to, $limit);
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    private function topCallDimension(
        string $foreignKey,
        string $table,
        string $labelColumn,
        ?Carbon $from,
        ?Carbon $to,
        int $limit,
    ): Collection {
        if (! Schema::hasTable('call_events') || ! Schema::hasColumn('call_events', $foreignKey)) {
            return collect();
        }

        $query = CallEvent::query()
            ->join($table, "call_events.{$foreignKey}", '=', "{$table}.id")
            ->whereNotNull("call_events.{$foreignKey}")
            ->select("{$table}.{$labelColumn} as label", DB::raw('count(*) as total'))
            ->groupBy("{$table}.{$labelColumn}")
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'call_events.occurred_at');

        return $query->get();
    }

    /**
     * @return Collection<int, object{label: string, total: int}>
     */
    private function topLeadDimension(
        string $foreignKey,
        string $table,
        string $labelColumn,
        ?Carbon $from,
        ?Carbon $to,
        int $limit,
    ): Collection {
        if (! Schema::hasColumn('leads', $foreignKey)) {
            return collect();
        }

        $query = Lead::query()
            ->join($table, "leads.{$foreignKey}", '=', "{$table}.id")
            ->whereNotNull("leads.{$foreignKey}")
            ->select("{$table}.{$labelColumn} as label", DB::raw('count(*) as total'))
            ->groupBy("{$table}.{$labelColumn}")
            ->orderByDesc('total')
            ->limit($limit);

        $this->applyDateRange($query, $from, $to, 'leads.created_at');

        return $query->get();
    }

    private function applyDateRange($query, ?Carbon $from, ?Carbon $to, string $column): void
    {
        if ($from !== null) {
            $query->where($column, '>=', $from);
        }
        if ($to !== null) {
            $query->where($column, '<=', $to);
        }
    }
}
