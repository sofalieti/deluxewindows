<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\CrmNote;
use App\Models\PhoneClick;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ContactFromPhoneClickService
{
    public function createOrAttach(PhoneClick $click, ?int $userId = null): Contact
    {
        return DB::transaction(function () use ($click, $userId): Contact {
            /** @var PhoneClick $locked */
            $locked = PhoneClick::query()->lockForUpdate()->findOrFail($click->id);

            if ($locked->contact_id !== null) {
                return Contact::query()->findOrFail($locked->contact_id);
            }

            $contact = $this->resolveContact($locked);
            if ($contact === null) {
                $phone = $this->bestPhone($locked);
                $contact = Contact::query()->create([
                    'full_name' => $phone ?: 'Phone click #'.$locked->id,
                    'phone' => $phone,
                    'created_by_user_id' => $userId,
                ]);
            }

            $this->linkClick($locked, $contact, $userId);
            $this->linkMatchingClicks($contact, $userId);
            app(RingCentralContactBinder::class)->rebindContact($contact);

            return $contact->fresh(['phoneClicks']) ?? $contact;
        });
    }

    public function attachNewClick(PhoneClick $click): ?Contact
    {
        if ($click->isSpam() || $click->contact_id !== null) {
            return $click->contact;
        }

        $contact = $this->resolveContact($click);
        if ($contact === null) {
            return null;
        }

        $this->linkClick($click, $contact);

        return $contact;
    }

    public function attachToContact(PhoneClick $click, Contact $contact, ?int $userId = null): Contact
    {
        return DB::transaction(function () use ($click, $contact, $userId): Contact {
            /** @var PhoneClick $locked */
            $locked = PhoneClick::query()->lockForUpdate()->findOrFail($click->id);
            $this->linkClick($locked, $contact, $userId);

            return $contact;
        });
    }

    public function attachExistingMatches(Contact $contact, ?int $userId = null): void
    {
        DB::transaction(fn () => $this->linkMatchingClicks($contact->fresh() ?? $contact, $userId));
    }

    public function resolveContact(PhoneClick $click): ?Contact
    {
        $phone = $this->normalizedPhone($click);
        if ($phone === null) {
            return null;
        }

        $contacts = Contact::query()
            ->where('normalized_phone', $phone)
            ->get()
            ->unique('id');

        return $contacts->count() === 1 ? $contacts->first() : null;
    }

    private function linkMatchingClicks(Contact $contact, ?int $userId): void
    {
        $phone = $contact->normalized_phone;
        if ($phone === null) {
            return;
        }

        PhoneClick::query()
            ->whereNull('contact_id')
            ->notSpam()
            ->where('normalized_phone', $phone)
            ->orderBy('id')
            ->get()
            ->each(function (PhoneClick $click) use ($contact, $userId): void {
                if ($this->resolveContact($click)?->is($contact)) {
                    $this->linkClick($click, $contact, $userId);
                }
            });
    }

    private function linkClick(PhoneClick $click, Contact $contact, ?int $userId = null): void
    {
        $oldContactId = $click->contact_id;
        if ((int) $oldContactId === (int) $contact->id) {
            return;
        }

        $click->contact()->associate($contact);
        $click->save();

        $noteUserId = $userId ?? Auth::id();
        if ($noteUserId) {
            CrmNote::query()->create([
                'subject_type' => $click->getMorphClass(),
                'subject_id' => $click->id,
                'user_id' => $noteUserId,
                'body' => $oldContactId === null
                    ? 'Linked to contact #'.$contact->id
                    : 'Contact changed from #'.$oldContactId.' to #'.$contact->id,
            ]);
        }
    }

    private function bestPhone(PhoneClick $click): ?string
    {
        return $click->ringCentralClientPhone()
            ?: (trim((string) ($click->phone ?? '')) !== '' ? (string) $click->phone : null);
    }

    private function normalizedPhone(PhoneClick $click): ?string
    {
        return $click->normalized_phone
            ?: Contact::normalizePhone($this->bestPhone($click));
    }
}
