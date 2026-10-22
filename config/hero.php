<?php

declare(strict_types=1);

return [
    /*
     * Which hero block the site serves.
     *
     *   new — redesigned consultation-first hero (no discount badge)
     *   old — previous promo hero with the % OFF badge
     *
     * The admin dashboard switch (hero_settings table) wins over this value,
     * which only acts as the fallback before anything is saved there.
     * Appending ?hero=old or ?hero=new to any URL overrides both for that
     * visitor (stored in a cookie); ?hero=default clears that override.
     */
    'variant' => env('HERO_VARIANT', 'new'),

    'variants' => ['old', 'new'],

    'cookie' => 'hero_variant',

    /*
     * SMS / WhatsApp options in the phone-choice modal. Off for now: every CTA
     * should lead to a call or a consultation. Set PHONE_MODAL_SMS_ENABLED=true
     * to bring the texting options back.
     */
    'phone_modal_sms' => (bool) env('PHONE_MODAL_SMS_ENABLED', false),
];
