<?php

declare(strict_types=1);

namespace App\Support;

class ClarityDashboard
{
    /**
     * Recordings filtered by the custom user id passed to clarity('identify').
     */
    public static function recordingsUrl(?string $visitorId): ?string
    {
        $projectId = trim((string) config('services.clarity.project_id'));
        $visitorId = trim((string) $visitorId);

        if ($projectId === '' || $visitorId === '' || preg_match('/^[a-zA-Z0-9]+$/', $projectId) !== 1) {
            return null;
        }

        return 'https://clarity.microsoft.com/projects/view/'
            .rawurlencode($projectId)
            .'/impressions?date=Last%2030%20days&CustomUserId=is%3B'
            .rawurlencode($visitorId);
    }
}
