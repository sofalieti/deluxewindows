<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Suggestions for the search box that stays in the admin header.
 */
final class AdminOmniSearch
{
    public function __construct(private readonly CrmSmartSearch $search) {}

    /**
     * @return array{completion: ?string, suggestions: list<array{value: string, kind: string}>, groups: list<array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}>}
     */
    public function payload(?Authenticatable $user, string $term): array
    {
        $term = trim($term);
        $suggestions = $user instanceof User ? $this->suggestions($user, $term) : [];

        return [
            'completion' => $suggestions[0]['value'] ?? null,
            'suggestions' => $suggestions,
            'groups' => $this->groups($user, $term),
        ];
    }

    /**
     * @return list<array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}>
     */
    public function groups(?Authenticatable $user, string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2 || ! $user instanceof User) {
            return [];
        }

        $groups = [];
        if ($user->hasAccess('platform.leads')) {
            $groups[] = $this->leads($user, $term);
        }
        if ($user->hasAccess('platform.contacts')) {
            $groups[] = $this->contacts($term);
        }
        if ($user->hasAccess('platform.phone-clicks')) {
            $groups[] = $this->phones($user, $term);
        }
        if ($user->hasAccess('platform.leads')) {
            $groups[] = $this->calls($term);
        }

        return array_values(array_filter(
            $groups,
            fn (array $group): bool => $group['items'] !== [],
        ));
    }

    /**
     * @return array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}
     */
    private function leads(User $user, string $term): array
    {
        $items = Lead::query()
            ->visibleTo($user)
            ->tap(fn ($query) => $this->search->apply(
                $query,
                ['full_name', 'email', 'phone', 'city', 'message'],
                ['phone', 'normalized_phone'],
                $term,
            ))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (Lead $lead): array {
                $name = trim((string) $lead->full_name);

                return [
                    'title' => $name !== '' ? $name : 'Lead #'.$lead->id,
                    'subtitle' => $this->join([
                        $lead->phone,
                        $lead->email,
                        $lead->city,
                        $lead->statusLabel(),
                    ]),
                    'url' => route('platform.leads.edit', $lead),
                    'badge' => $lead->status === Lead::STATUS_SPAM ? 'Spam' : null,
                ];
            })
            ->all();

        return [
            'label' => 'Leads',
            'more' => [
                'label' => 'Full list',
                'url' => route('platform.leads', ['q' => $term]),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}
     */
    private function contacts(string $term): array
    {
        $items = Contact::query()
            ->tap(fn ($query) => $this->search->apply(
                $query,
                ['full_name', 'email', 'phone', 'city', 'address'],
                ['phone', 'normalized_phone'],
                $term,
                function ($inner, string $like, string $digits = ''): void {
                    $inner->orWhereHas('emailAddresses', function ($emails) use ($like): void {
                        $emails->where('email', 'like', $like)
                            ->orWhere('normalized_email', 'like', $like);
                    });
                },
            ))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (Contact $contact): array {
                $name = trim((string) $contact->full_name);

                return [
                    'title' => $name !== '' ? $name : 'Contact #'.$contact->id,
                    'subtitle' => $this->join([$contact->phone, $contact->email, $contact->city]),
                    'url' => route('platform.contacts.edit', $contact),
                    'badge' => 'Client',
                ];
            })
            ->all();

        return [
            'label' => 'Contacts',
            'more' => [
                'label' => 'Full list',
                'url' => route('platform.contacts', ['q' => $term]),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}
     */
    private function phones(User $user, string $term): array
    {
        $items = PhoneClick::query()
            ->visibleTo($user)
            ->tap(fn ($query) => $this->search->apply(
                $query,
                ['phone', 'page_url', 'utm_source', 'utm_campaign', 'utm_city', 'source_label'],
                ['phone', 'normalized_phone', 'ringcentral_from_phone', 'ringcentral_to_phone'],
                $term,
            ))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (PhoneClick $click): array {
                return [
                    'title' => trim((string) $click->phone) !== '' ? (string) $click->phone : 'Phone click #'.$click->id,
                    'subtitle' => $this->join([
                        $click->utm_city,
                        $click->source_label,
                        $click->page_url,
                    ]),
                    'url' => route('platform.phone-clicks.view', $click),
                    'badge' => $click->isSpam() ? 'Spam' : null,
                ];
            })
            ->all();

        return [
            'label' => 'Phone clicks',
            'more' => [
                'label' => 'Full list',
                'url' => route('platform.phone-clicks', ['q' => $term]),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, more: array{label: string, url: string}, items: list<array{title: string, subtitle: string, url: string, badge: ?string}>}
     */
    private function calls(string $term): array
    {
        $items = RingCentralCall::query()
            ->visible()
            ->tap(fn ($query) => $this->search->apply(
                $query,
                ['external_phone', 'from_phone', 'to_phone', 'from_name', 'to_name'],
                ['external_phone', 'from_phone', 'to_phone'],
                $term,
                function ($inner, string $like, string $digits = ''): void {
                    $inner->orWhereHas('contact', function ($contact) use ($like): void {
                        $contact->where('full_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })->orWhereHas('lead', function ($lead) use ($like): void {
                        $lead->where('full_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    });
                },
            ))
            ->orderByDesc('started_at')
            ->limit(5)
            ->get()
            ->map(function (RingCentralCall $call): array {
                $phone = trim((string) $call->external_phone);

                return [
                    'title' => $phone !== '' ? $phone : 'Call #'.$call->id,
                    'subtitle' => $this->join([
                        $call->direction,
                        $call->result,
                        $call->from_name ?: $call->to_name,
                    ]),
                    'url' => route('platform.ringcentral-calls', ['q' => $call->external_phone]),
                    'badge' => $call->isClient() ? 'Client' : null,
                ];
            })
            ->all();

        return [
            'label' => 'RingCentral',
            'more' => [
                'label' => 'Full list',
                'url' => route('platform.ringcentral-calls', ['q' => $term]),
            ],
            'items' => $items,
        ];
    }

    /**
     * Names, emails and phones already stored, which continue what was typed.
     *
     * @return list<array{value: string, kind: string}>
     */
    private function suggestions(User $user, string $term): array
    {
        if (mb_strlen($term) < 2) {
            return [];
        }

        $found = [];
        $fields = ['full_name' => 'Name', 'email' => 'Email', 'phone' => 'Phone'];

        if ($user->hasAccess('platform.leads')) {
            $this->pullSuggestions(
                $found,
                Lead::query()->visibleTo($user)->where('status', '!=', Lead::STATUS_SPAM),
                $term,
                $fields,
            );
        }
        if ($user->hasAccess('platform.contacts')) {
            $this->pullSuggestions($found, Contact::query(), $term, $fields);
        }
        if ($user->hasAccess('platform.phone-clicks')) {
            $this->pullSuggestions(
                $found,
                PhoneClick::query()->visibleTo($user)->notSpam(),
                $term,
                ['phone' => 'Phone'],
            );
        }

        return $this->rankSuggestions($found, $term);
    }

    /**
     * @param  array<string, array{value: string, kind: string, order: int}>  $found
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, string>  $fields
     */
    private function pullSuggestions(array &$found, Builder $query, string $term, array $fields): void
    {
        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $textColumns = array_values(array_filter(
            array_keys($fields),
            fn (string $column): bool => $column !== 'phone',
        ));
        $needles = $this->phoneNeedles($digits);
        if ($textColumns === [] && $needles === []) {
            return;
        }

        $prefix = str_replace(['%', '_'], '', $term).'%';
        $rows = $query
            ->where(function (Builder $inner) use ($textColumns, $prefix, $needles): void {
                foreach ($textColumns as $column) {
                    $inner->orWhere($column, 'like', $prefix);
                }
                foreach ($needles as $needle) {
                    $inner->orWhere('normalized_phone', 'like', $needle.'%');
                }
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get(array_keys($fields));

        foreach ($rows as $row) {
            foreach ($fields as $column => $kind) {
                $value = trim((string) $row->{$column});
                if ($value === '') {
                    continue;
                }
                $key = $kind.'|'.mb_strtolower($value);
                if (isset($found[$key])) {
                    continue;
                }
                $found[$key] = [
                    'value' => $value,
                    'kind' => $kind,
                    'order' => count($found),
                ];
            }
        }
    }

    /**
     * @param  array<string, array{value: string, kind: string, order: int}>  $found
     * @return list<array{value: string, kind: string}>
     */
    private function rankSuggestions(array $found, string $term): array
    {
        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $items = [];
        foreach ($found as $item) {
            if (! $this->extendsTerm($term, $digits, $item['value'])) {
                continue;
            }
            $item['score'] = $this->suggestionScore($term, $item['value']);
            $items[] = $item;
        }

        usort($items, function (array $a, array $b): int {
            return $a['score'] <=> $b['score'] ?: $a['order'] <=> $b['order'];
        });

        return array_map(
            fn (array $item): array => ['value' => $item['value'], 'kind' => $item['kind']],
            array_slice($items, 0, 6),
        );
    }

    private function extendsTerm(string $term, string $digits, string $value): bool
    {
        if (mb_strlen($value) > mb_strlen($term) && mb_stripos($value, $term) === 0) {
            return true;
        }

        if (strlen($digits) < 3) {
            return false;
        }

        $valueDigits = preg_replace('/\D+/', '', $value) ?? '';
        foreach ($this->phoneNeedles($digits) as $needle) {
            if (str_starts_with($valueDigits, $needle) && strlen($valueDigits) > strlen($digits)) {
                return true;
            }
        }

        return false;
    }

    private function suggestionScore(string $term, string $value): int
    {
        if (mb_stripos($value, $term) === 0) {
            return mb_strlen($value);
        }

        return 10000 + strlen((string) preg_replace('/\D+/', '', $value));
    }

    /**
     * @return list<string>
     */
    private function phoneNeedles(string $digits): array
    {
        if (strlen($digits) < 3) {
            return [];
        }

        $needles = [$digits];
        if (! str_starts_with($digits, '1')) {
            $needles[] = '1'.$digits;
        }

        return $needles;
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function join(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return implode(' · ', $clean);
    }
}
