<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadChange;
use Illuminate\Support\Facades\DB;

final class ContactFromLeadService
{
    public function createOrAttach(Lead $lead, ?int $userId = null): Contact
    {
        return DB::transaction(function () use ($lead, $userId): Contact {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);

            if ($lockedLead->contact_id !== null) {
                return Contact::query()->findOrFail($lockedLead->contact_id);
            }

            $contact = $this->resolveContact($lockedLead);
            if ($contact === null) {
                $contact = Contact::query()->create([
                    'full_name' => $lockedLead->full_name,
                    'email' => $lockedLead->email,
                    'phone' => $lockedLead->phone,
                    'city' => $lockedLead->city,
                    'source_lead_id' => $lockedLead->id,
                    'created_by_user_id' => $userId,
                ]);
            }

            $this->linkLead($lockedLead, $contact, $userId);
            $this->linkMatchingLeads($contact, $userId);
            app(RingCentralContactBinder::class)->rebindContact($contact);

            return $contact->fresh(['leads']) ?? $contact;
        });
    }

    public function attachNewLead(Lead $lead): ?Contact
    {
        if ($lead->isSpam() || $lead->contact_id !== null) {
            return $lead->contact;
        }

        $contact = $this->resolveContact($lead);
        if ($contact === null) {
            return null;
        }

        $this->linkLead($lead, $contact);

        return $contact;
    }

    public function attachToContact(Lead $lead, Contact $contact, ?int $userId = null): Contact
    {
        return DB::transaction(function () use ($lead, $contact, $userId): Contact {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $this->linkLead($lockedLead, $contact, $userId);

            return $contact;
        });
    }

    public function attachExistingMatches(Contact $contact, ?int $userId = null): void
    {
        DB::transaction(fn () => $this->linkMatchingLeads($contact->fresh() ?? $contact, $userId));
    }

    public function resolveContact(Lead $lead): ?Contact
    {
        $email = $lead->normalized_email ?: Contact::normalizeEmail($lead->email);
        $phone = $lead->normalized_phone ?: Contact::normalizePhone($lead->phone);

        if ($email === null && $phone === null) {
            return null;
        }

        $contacts = Contact::query()
            ->where(function ($query) use ($email, $phone): void {
                if ($email !== null) {
                    $query->where('normalized_email', $email);
                }
                if ($phone !== null) {
                    $method = $email !== null ? 'orWhere' : 'where';
                    $query->{$method}('normalized_phone', $phone);
                }
            })
            ->get()
            ->unique('id');

        return $contacts->count() === 1 ? $contacts->first() : null;
    }

    private function linkMatchingLeads(Contact $contact, ?int $userId): void
    {
        $email = $contact->normalized_email;
        $phone = $contact->normalized_phone;

        if ($email === null && $phone === null) {
            return;
        }

        Lead::query()
            ->whereNull('contact_id')
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where(function ($query) use ($email, $phone): void {
                if ($email !== null) {
                    $query->where('normalized_email', $email);
                }
                if ($phone !== null) {
                    $method = $email !== null ? 'orWhere' : 'where';
                    $query->{$method}('normalized_phone', $phone);
                }
            })
            ->orderBy('id')
            ->get()
            ->each(function (Lead $lead) use ($contact, $userId): void {
                if ($this->resolveContact($lead)?->is($contact)) {
                    $this->linkLead($lead, $contact, $userId);
                }
            });
    }

    private function linkLead(Lead $lead, Contact $contact, ?int $userId = null): void
    {
        $oldContactId = $lead->contact_id;
        if ((int) $oldContactId === (int) $contact->id) {
            return;
        }

        $lead->contact()->associate($contact);
        $lead->save();

        LeadChange::record(
            $lead,
            'contact_id',
            $oldContactId !== null ? (string) $oldContactId : null,
            (string) $contact->id,
            'Linked to contact #'.$contact->id,
            $userId,
        );
    }
}
