<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SiteEvent;
use App\Models\SiteVisit;
use App\Services\BotDetector;
use App\Services\VisitsSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BehaviorEventController extends Controller
{
    public function store(Request $request, VisitsSettingsService $settings, BotDetector $bots): Response
    {
        if (! $settings->behaviorEnabled() || $bots->isBot($request->userAgent())) {
            return response()->noContent();
        }

        $validated = $request->validate([
            'visitor_id' => ['required', 'uuid'],
            'session_id' => ['required', 'uuid'],
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in(SiteEvent::TYPES)],
            'events.*.path' => ['nullable', 'string', 'max:500'],
            'events.*.target' => ['nullable', 'string', 'max:255'],
            'events.*.value' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'events.*.meta' => ['nullable', 'array'],
        ]);

        $now = now();
        $visitorId = strtolower($validated['visitor_id']);
        $sessionId = strtolower($validated['session_id']);
        $rows = [];

        foreach ($validated['events'] as $event) {
            $meta = $this->cleanMeta($event['meta'] ?? null);
            $rows[] = [
                'session_id' => $sessionId,
                'visitor_id' => $visitorId,
                'type' => $event['type'],
                'path' => $this->cleanPath($event['path'] ?? null),
                'target' => $this->cleanTarget($event['target'] ?? null),
                'value' => isset($event['value']) ? (int) $event['value'] : null,
                'meta' => $meta === null ? null : json_encode($meta),
                'created_at' => $now,
            ];
        }

        try {
            DB::transaction(function () use ($rows, $sessionId): void {
                SiteEvent::query()->insert($rows);
                $this->updateSummary($sessionId, $rows);
            });
        } catch (\Throwable $e) {
            Log::warning('Behavior events save failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->noContent(500);
        }

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, int|string>|null
     */
    private function cleanMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        $clean = [];

        if (isset($meta['title']) && is_string($meta['title'])) {
            $title = mb_substr(trim($meta['title']), 0, 200);
            if ($title !== '') {
                $clean['title'] = $title;
            }
        }

        if (isset($meta['referrer']) && is_string($meta['referrer'])) {
            $referrer = mb_substr(trim($meta['referrer']), 0, 200);
            if ($referrer !== '' && ! str_contains($referrer, '://')) {
                $clean['referrer'] = $referrer;
            }
        }

        if (isset($meta['y']) && is_numeric($meta['y'])) {
            $clean['y'] = max(0, min(100, (int) $meta['y']));
        }

        if (isset($meta['field']) && is_string($meta['field']) && preg_match('/^[A-Za-z0-9_\-\[\]]{1,80}$/', $meta['field']) === 1) {
            $clean['field'] = $meta['field'];
        }

        return $clean === [] ? null : $clean;
    }

    private function cleanPath(mixed $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        return mb_substr($path, 0, 500);
    }

    private function cleanTarget(mixed $target): ?string
    {
        $target = trim((string) $target);
        if ($target === '') {
            return null;
        }

        $target = preg_replace('/\d[\d\s().+-]{6,}\d/u', '#', $target) ?? $target;

        return mb_substr($target, 0, 255);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function updateSummary(string $sessionId, array $rows): void
    {
        $visit = SiteVisit::query()
            ->where('session_id', $sessionId)
            ->latest('id')
            ->first();

        if ($visit === null) {
            return;
        }

        $pageviews = 0;
        $engaged = 0;
        $maxScroll = (int) $visit->max_scroll;
        $rage = 0;

        foreach ($rows as $row) {
            $value = $row['value'];
            if ($row['type'] === 'page_view') {
                $pageviews++;
            } elseif ($row['type'] === 'engaged_time' && is_int($value)) {
                $engaged += $value;
            } elseif ($row['type'] === 'scroll_depth' && is_int($value)) {
                $maxScroll = max($maxScroll, min(100, $value));
            } elseif ($row['type'] === 'rage_click') {
                $rage++;
            }
        }

        $visit->pageviews = (int) $visit->pageviews + $pageviews;
        $visit->engaged_seconds = (int) $visit->engaged_seconds + $engaged;
        $visit->max_scroll = $maxScroll;
        $visit->events_count = (int) $visit->events_count + count($rows);
        $visit->rage_clicks = (int) $visit->rage_clicks + $rage;
        $visit->last_seen_at = now();
        $visit->save();
    }
}
