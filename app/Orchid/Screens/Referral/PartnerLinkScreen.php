<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralPartner;
use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralPartnerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PartnerLinkScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(): iterable
    {
        $partner = $this->currentPartnerOrAbort();
        [$method, $handle] = self::parsePayout($partner->payout_details);

        return [
            'partner' => $partner,
            'kit' => self::kit($partner),
            'payout' => [
                'method' => $method,
                'handle' => $handle,
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Share kit & QR codes';
    }

    public function description(): ?string
    {
        return 'Your links, QR codes, printable posters and ready-to-send messages. Every channel is tracked separately.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function layout(): iterable
    {
        $methods = (array) config('referral.payout_methods', ['Zelle', 'Venmo', 'Check']);

        return [
            Layout::view('admin.referral.partner-kit'),
            Layout::rows([
                Select::make('payout.method')
                    ->title('Payout method')
                    ->options(array_combine($methods, $methods))
                    ->empty('Choose…')
                    ->required(),
                Input::make('payout.handle')
                    ->title('Zelle phone/email, Venmo @username, or mailing address for checks')
                    ->maxlength(160)
                    ->required(),
                Button::make('Save payout details')
                    ->icon('bs.check2')
                    ->method('savePayout')
                    ->type(Color::PRIMARY),
            ])->title('How should we pay you?'),
        ];
    }

    public function savePayout(Request $request): void
    {
        $partner = $this->currentPartnerOrAbort();
        $methods = (array) config('referral.payout_methods', ['Zelle', 'Venmo', 'Check']);

        $data = $request->validate([
            'payout.method' => ['required', 'string', Rule::in($methods)],
            'payout.handle' => ['required', 'string', 'max:160'],
        ]);

        $partner->forceFill([
            'payout_details' => $data['payout']['method'].': '.trim($data['payout']['handle']),
        ])->save();

        Toast::success('Payout details saved.');
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    public static function parsePayout(?string $details): array
    {
        $details = trim((string) $details);
        if ($details === '') {
            return [null, null];
        }

        foreach ((array) config('referral.payout_methods', []) as $method) {
            if (str_starts_with($details, $method.':')) {
                return [$method, trim(substr($details, strlen($method) + 1))];
            }
        }

        return [null, $details];
    }

    /**
     * @return array<string, mixed>
     */
    public static function kit(ReferralPartner $partner): array
    {
        $reward = (int) config('referral.reward_amount', 150);
        $credit = (int) config('referral.friend_credit_amount', 150);
        $first = $partner->firstName();

        $channels = [];
        foreach ((array) config('referral.channels', []) as $key => $label) {
            $channels[] = [
                'key' => (string) $key,
                'label' => (string) $label,
                'url' => $partner->shareUrl((string) $key),
            ];
        }

        $sms = $partner->shareUrl('sms');
        $nextdoor = $partner->shareUrl('nextdoor');
        $instagram = $partner->shareUrl('instagram');
        $email = $partner->shareUrl('email');
        $phone = site_phone_display();

        return [
            'reward' => $reward,
            'credit' => $credit,
            'code' => $partner->code,
            'link' => $partner->referralUrl(),
            'channels' => $channels,
            'qr_default' => $partner->shareUrl('qr'),
            'payout_window' => (string) config('referral.payout_window', 'within 14 days'),
            'messages' => [
                [
                    'key' => 'sms',
                    'label' => 'Text message',
                    'text' => "Hey! We just did our windows with Deluxe Windows and they were great — fair price, done in a day. If you're thinking about new windows or doors, this link gets you \${$credit} off: {$sms}",
                ],
                [
                    'key' => 'nextdoor',
                    'label' => 'Nextdoor post',
                    'text' => "Window/door installer recommendation 🏡\n\nWe used Deluxe Windows (Burlingame, 30+ years, employee-owned) and I'd hire them again: honest itemized quote, their own crew, clean install in 1–2 days.\n\nIf you're getting quotes, use my link for \${$credit} off your project: {$nextdoor}\n\nHappy to answer questions — feel free to DM me.",
                ],
                [
                    'key' => 'instagram',
                    'label' => 'Instagram / Facebook caption',
                    'text' => "New windows day ✨ Quieter, warmer, and the light is unreal. Shout-out to @deluxewindows — 1-day install, zero mess.\n\nThinking about upgrading? My link gets you \${$credit} off 👉 {$instagram}\n\n#bayarea #homeupgrade #newwindows",
                ],
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'text' => "Subject: \${$credit} off new windows (from {$first})\n\nHi,\n\nWe replaced our windows with Deluxe Windows and I've been really happy with them — honest pricing, their own installers, and they finished in a day.\n\nIf you've been thinking about new windows or doors, here's my referral link. You'll get \${$credit} off your project and a free in-home estimate:\n{$email}\n\nOr call {$phone} and mention my name.\n\n{$first}",
                ],
            ],
            'print' => [
                ['format' => 'poster', 'title' => 'Poster', 'size' => 'US Letter · 8.5×11"', 'hint' => 'Lobby, laundry room, coffee shop or hardware store boards.'],
                ['format' => 'flyer', 'title' => 'Flyers (2 per page)', 'size' => 'Half-letter · 5.5×8.5"', 'hint' => 'Mailboxes, HOA meetings, front desks.'],
                ['format' => 'cards', 'title' => 'Business cards (10 per page)', 'size' => '3.5×2" · Avery 8371 / 5371', 'hint' => 'Keep in your wallet — hand one over when someone asks.'],
            ],
        ];
    }
}
