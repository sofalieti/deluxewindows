<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\ReferralPartner;
use App\Models\SiteVisit;
use Illuminate\Database\Eloquent\Model;

class ReferralAttributionService
{
    /**
     * Resolve an active partner and stamp referral_partner_id on the model.
     * Last-touch UTM wins; first-touch partner stored in meta when different.
     *
     * @param  array<string, mixed>  $attribution
     */
    public function attribute(Model $model, array $attribution = []): ?ReferralPartner
    {
        $partner = $this->resolvePartner($attribution, $model);
        if ($partner === null) {
            return null;
        }

        $meta = is_array($model->getAttribute('meta')) ? $model->getAttribute('meta') : [];
        $firstPartner = $this->resolvePartnerFromTouch(
            (string) ($attribution['first_utm_source'] ?? data_get($meta, 'first_touch.utm_source', '')),
            (string) ($attribution['first_utm_campaign'] ?? data_get($meta, 'first_touch.utm_campaign', '')),
        );

        if ($firstPartner !== null && $firstPartner->id !== $partner->id) {
            $meta['referral_first_partner_id'] = $firstPartner->id;
            $meta['referral_first_partner_code'] = $firstPartner->code;
        }

        $meta['referral_partner_code'] = $partner->code;
        $model->forceFill([
            'referral_partner_id' => $partner->id,
            'meta' => $meta,
        ])->save();

        return $partner;
    }

    /**
     * @param  array<string, mixed>  $attribution
     */
    public function resolvePartner(array $attribution, ?Model $model = null): ?ReferralPartner
    {
        $source = (string) ($attribution['utm_source']
            ?? $model?->getAttribute('utm_source')
            ?? '');
        $campaign = (string) ($attribution['utm_campaign']
            ?? $model?->getAttribute('utm_campaign')
            ?? '');

        return $this->resolvePartnerFromTouch($source, $campaign);
    }

    public function resolvePartnerFromTouch(string $source, string $campaign): ?ReferralPartner
    {
        $source = strtolower(trim($source));
        $campaign = trim($campaign);

        if ($campaign === '') {
            return null;
        }

        $isReferralSource = $source === 'referral' || str_contains($source, 'referral');

        // Prefer explicit referral source; otherwise match campaign to an active partner code.
        if ($isReferralSource || $source === '') {
            return $this->findActiveByCode($campaign);
        }

        return $this->findActiveByCode($campaign);
    }

    public function findActiveByCode(string $code): ?ReferralPartner
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return null;
        }

        return ReferralPartner::query()
            ->whereRaw('LOWER(code) = ?', [$code])
            ->where('status', ReferralPartner::STATUS_ACTIVE)
            ->first();
    }

    public function attributeLead(Lead $lead): ?ReferralPartner
    {
        $meta = is_array($lead->meta) ? $lead->meta : [];
        $first = is_array($meta['first_touch'] ?? null) ? $meta['first_touch'] : [];

        return $this->attribute($lead, [
            'utm_source' => $lead->utm_source,
            'utm_campaign' => $lead->utm_campaign,
            'first_utm_source' => $first['utm_source'] ?? '',
            'first_utm_campaign' => $first['utm_campaign'] ?? '',
        ]);
    }

    public function attributePhoneClick(PhoneClick $click): ?ReferralPartner
    {
        return $this->attribute($click, [
            'utm_source' => $click->utm_source,
            'utm_campaign' => $click->utm_campaign,
            'first_utm_source' => $click->first_utm_source,
            'first_utm_campaign' => $click->first_utm_campaign,
        ]);
    }

    public function attributeVisit(SiteVisit $visit): ?ReferralPartner
    {
        return $this->attribute($visit, [
            'utm_source' => $visit->utm_source,
            'utm_campaign' => $visit->utm_campaign,
            'first_utm_source' => $visit->first_utm_source,
            'first_utm_campaign' => $visit->first_utm_campaign,
        ]);
    }
}
