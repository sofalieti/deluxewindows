<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\RingCentralCall;
use Illuminate\Support\Facades\Schema;

class RingCentralContactBinder
{
    public function __construct(
        private readonly RingCentralCallLogService $callLog,
        private readonly CallTranscriptionQueue $transcriptQueue,
    ) {}

    /**
     * Map last-10 phone digits → contact id (oldest contact wins on duplicates).
     *
     * @return array<string, int>
     */
    public function phoneIndex(): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        $index = [];
        Contact::query()
            ->whereNotNull('normalized_phone')
            ->where('normalized_phone', '!=', '')
            ->orderBy('id')
            ->get(['id', 'normalized_phone', 'phone'])
            ->each(function (Contact $contact) use (&$index): void {
                $key = $this->phoneKey((string) ($contact->phone ?: $contact->normalized_phone));
                if ($key !== null && ! isset($index[$key])) {
                    $index[$key] = (int) $contact->id;
                }
            });

        return $index;
    }

    public function contactIdForPhone(?string $phone, ?array $phoneIndex = null): ?int
    {
        $key = $this->phoneKey($phone);
        if ($key === null) {
            return null;
        }

        $phoneIndex ??= $this->phoneIndex();

        return $phoneIndex[$key] ?? null;
    }

    public function phoneKey(?string $phone): ?string
    {
        $normalized = $this->callLog->normalizePhone((string) ($phone ?? ''));
        if ($normalized === '') {
            return null;
        }

        $last10 = substr(preg_replace('/\D+/', '', $normalized) ?? '', -10);

        return strlen($last10) === 10 ? $last10 : null;
    }

    /**
     * Attach / detach RingCentral calls for a contact after its phone changes.
     */
    public function rebindContact(Contact $contact): void
    {
        if (! Schema::hasTable('ringcentral_calls')) {
            return;
        }

        $contactId = (int) $contact->id;
        $phone = trim((string) ($contact->phone ?? ''));
        $key = $this->phoneKey($phone);

        RingCentralCall::query()
            ->where('contact_id', $contactId)
            ->orderBy('id')
            ->chunkById(200, function ($calls) use ($phone): void {
                foreach ($calls as $call) {
                    if ($phone === '' || ! $this->callLog->phonesMatch($phone, (string) $call->external_phone)) {
                        $call->forceFill(['contact_id' => null])->saveQuietly();
                    }
                }
            });

        if ($key === null || $phone === '') {
            return;
        }

        RingCentralCall::query()
            ->where('external_phone', 'like', '%'.$key)
            ->where(function ($query) use ($contactId): void {
                $query->whereNull('contact_id')
                    ->orWhere('contact_id', $contactId);
            })
            ->orderBy('id')
            ->chunkById(200, function ($calls) use ($phone, $contactId): void {
                foreach ($calls as $call) {
                    if (! $this->callLog->phonesMatch($phone, (string) $call->external_phone)) {
                        continue;
                    }
                    if ((int) $call->contact_id !== $contactId) {
                        $call->forceFill(['contact_id' => $contactId])->saveQuietly();
                    }
                    $this->transcriptQueue->enqueueIfEligible($call->fresh() ?? $call);
                }
            });
    }

    /**
     * Map last-10 phone digits → newest non-spam lead id.
     *
     * @return array<string, int>
     */
    public function leadIndex(): array
    {
        if (! Schema::hasTable('leads')) {
            return [];
        }

        $index = [];
        Lead::query()
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->whereNotNull('normalized_phone')
            ->where('normalized_phone', '!=', '')
            ->orderByDesc('id')
            ->get(['id', 'normalized_phone', 'phone'])
            ->each(function (Lead $lead) use (&$index): void {
                $key = $this->phoneKey((string) ($lead->phone ?: $lead->normalized_phone));
                if ($key !== null && ! isset($index[$key])) {
                    $index[$key] = (int) $lead->id;
                }
            });

        return $index;
    }

    public function leadIdForPhone(?string $phone, ?array $leadIndex = null): ?int
    {
        $key = $this->phoneKey($phone);
        if ($key === null) {
            return null;
        }

        $leadIndex ??= $this->leadIndex();

        return $leadIndex[$key] ?? null;
    }

    /**
     * Point calls for this lead's phone at the current winning lead.
     */
    public function rebindLead(Lead $lead): void
    {
        if (! Schema::hasTable('ringcentral_calls') || ! Schema::hasColumn('ringcentral_calls', 'lead_id')) {
            return;
        }

        $keys = array_values(array_unique(array_filter([
            $this->phoneKey((string) $lead->phone),
            $this->phoneKey((string) $lead->getOriginal('phone')),
        ])));

        if ($keys === []) {
            RingCentralCall::query()
                ->where('lead_id', $lead->id)
                ->update(['lead_id' => null]);

            return;
        }

        $index = $this->leadIndex();
        foreach ($keys as $key) {
            RingCentralCall::query()
                ->where('external_phone', 'like', '%'.$key)
                ->orderBy('id')
                ->chunkById(200, function ($calls) use ($index): void {
                    foreach ($calls as $call) {
                        $leadId = $this->leadIdForPhone((string) $call->external_phone, $index);
                        if ((int) ($call->lead_id ?? 0) !== (int) ($leadId ?? 0)) {
                            $call->forceFill(['lead_id' => $leadId])->saveQuietly();
                        }
                    }
                });
        }
    }

    /**
     * Fill lead_id, and contact_id when it is still empty, for calls already stored.
     */
    public function backfillLinks(): void
    {
        if (! Schema::hasTable('ringcentral_calls') || ! Schema::hasColumn('ringcentral_calls', 'lead_id')) {
            return;
        }

        $leads = $this->leadIndex();
        $contacts = $this->phoneIndex();

        RingCentralCall::query()
            ->orderBy('id')
            ->chunkById(300, function ($calls) use ($leads, $contacts): void {
                foreach ($calls as $call) {
                    $dirty = [];
                    $leadId = $this->leadIdForPhone((string) $call->external_phone, $leads);
                    if ((int) ($call->lead_id ?? 0) !== (int) ($leadId ?? 0)) {
                        $dirty['lead_id'] = $leadId;
                    }
                    if ($call->contact_id === null) {
                        $contactId = $this->contactIdForPhone((string) $call->external_phone, $contacts);
                        if ($contactId !== null) {
                            $dirty['contact_id'] = $contactId;
                        }
                    }
                    if ($dirty !== []) {
                        $call->forceFill($dirty)->saveQuietly();
                    }
                }
            });
    }
}
