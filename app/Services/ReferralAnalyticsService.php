<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\ReferralPartner;
use App\Models\ReferralReward;
use App\Models\SiteVisit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReferralAnalyticsService
{
    /**
     * @return array<string, int|float|string>
     */
    public function adminMetrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now()->endOfDay();

        $partnersActive = ReferralPartner::query()->where('status', ReferralPartner::STATUS_ACTIVE)->count();
        $visits = SiteVisit::query()
            ->whereNotNull('referral_partner_id')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $clicks = PhoneClick::query()
            ->whereNotNull('referral_partner_id')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $leads = Lead::query()
            ->whereNotNull('referral_partner_id')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->count();
        $sold = Lead::query()
            ->whereNotNull('referral_partner_id')
            ->where('status', Lead::STATUS_SOLD)
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $eligibleCents = (int) ReferralReward::query()
            ->whereIn('status', [ReferralReward::STATUS_ELIGIBLE, ReferralReward::STATUS_APPROVED])
            ->sum('amount_cents');
        $paidYtdCents = (int) ReferralReward::query()
            ->where('status', ReferralReward::STATUS_PAID)
            ->whereYear('paid_at', now()->year)
            ->sum('amount_cents');

        return [
            'partners_active' => $partnersActive,
            'visits' => $visits,
            'phone_clicks' => $clicks,
            'leads' => $leads,
            'sold' => $sold,
            'lead_rate' => $visits > 0 ? round(($leads / $visits) * 100, 1) : 0.0,
            'liability' => '$'.number_format($eligibleCents / 100, 2),
            'paid_ytd' => '$'.number_format($paidYtdCents / 100, 2),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function topPartners(int $limit = 10): Collection
    {
        return ReferralPartner::query()
            ->withCount([
                'leads as leads_count' => fn ($q) => $q->where('status', '!=', Lead::STATUS_SPAM),
                'visits as visits_count',
                'rewards as rewards_count',
            ])
            ->orderByDesc('leads_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function dailyLeads(int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = Lead::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('referral_partner_id')
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $labels[] = $day;
            $values[] = (int) ($rows[$day] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array<string, int|float|string>
     */
    public function partnerMetrics(ReferralPartner $partner): array
    {
        $visits = $partner->visits()->count();
        $clicks = $partner->phoneClicks()->count();
        $leads = $partner->leads()->where('status', '!=', Lead::STATUS_SPAM)->count();
        $byStatus = $partner->leads()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $eligible = (int) $partner->rewards()->where('status', ReferralReward::STATUS_ELIGIBLE)->sum('amount_cents');
        $approved = (int) $partner->rewards()->where('status', ReferralReward::STATUS_APPROVED)->sum('amount_cents');
        $paid = (int) $partner->rewards()->where('status', ReferralReward::STATUS_PAID)->sum('amount_cents');

        return [
            'visits' => $visits,
            'phone_clicks' => $clicks,
            'leads' => $leads,
            'sold' => (int) ($byStatus[Lead::STATUS_SOLD] ?? 0),
            'new' => (int) ($byStatus[Lead::STATUS_NEW] ?? 0),
            'quoted' => (int) ($byStatus[Lead::STATUS_QUOTED] ?? 0),
            'lost' => (int) ($byStatus[Lead::STATUS_LOST] ?? 0),
            'eligible' => '$'.number_format($eligible / 100, 2),
            'approved' => '$'.number_format($approved / 100, 2),
            'paid' => '$'.number_format($paid / 100, 2),
            'estimated' => '$'.number_format(($eligible + $approved) / 100, 2),
        ];
    }
}
