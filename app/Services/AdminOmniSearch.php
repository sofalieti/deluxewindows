<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Suggestions for the search box that stays in the admin header.
 */
final class AdminOmniSearch
{
    public function __construct(private readonly CrmSmartSearch $search) {}

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
            $groups[] = $this->calls($term);
        }
        if ($user->hasAccess('platform.contacts')) {
            $groups[] = $this->contacts($term);
        }
        if ($user->hasAccess('platform.phone-clicks')) {
            $groups[] = $this->phones($user, $term);
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
                'label' => 'All leads',
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
                'label' => 'All contacts',
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
                'label' => 'All phone clicks',
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
            'label' => 'RingCentral calls',
            'more' => [
                'label' => 'All calls',
                'url' => route('platform.ringcentral-calls', ['q' => $term]),
            ],
            'items' => $items,
        ];
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
