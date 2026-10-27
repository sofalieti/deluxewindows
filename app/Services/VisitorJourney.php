<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SiteVisit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VisitorJourney
{
    /**
     * Visits for this visitor up to the conversion. Later visits stay out of the path.
     *
     * @return Collection<int, SiteVisit>
     */
    public function visits(?string $visitorId, ?CarbonInterface $before = null): Collection
    {
        $visitorId = trim((string) $visitorId);
        if ($visitorId === '') {
            return collect();
        }

        return SiteVisit::query()
            ->where('visitor_id', $visitorId)
            ->when($before !== null, function ($query) use ($before): void {
                $query->where('created_at', '<=', $before);
            })
            ->orderBy('created_at')
            ->limit(50)
            ->get();
    }
}
