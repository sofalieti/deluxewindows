<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralApplication;
use App\Services\ReferralPartnerService;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ReferralApplicationViewScreen extends Screen
{
    public ?ReferralApplication $application = null;

    public function query(ReferralApplication $application): iterable
    {
        $application->load(['reviewer', 'partner']);
        $this->application = $application;

        return [
            'application' => $application,
        ];
    }

    public function name(): ?string
    {
        return $this->application
            ? 'Application #'.$this->application->id.': '.$this->application->full_name
            : 'Referral application';
    }

    public function description(): ?string
    {
        return 'What this person wrote on the referral application.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_ADMIN];
    }

    public function commandBar(): iterable
    {
        $actions = [
            Link::make('Back to applications')
                ->icon('bs.arrow-left')
                ->route('platform.referral.applications'),
        ];

        if ($this->application?->isPending()) {
            $actions[] = Button::make('Approve')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->method('approve')
                ->confirm('Create partner account and email credentials will be shown once.');

            $actions[] = Button::make('Reject')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->method('reject')
                ->confirm('Reject this application?');
        }

        return $actions;
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('application', [
                Sight::make('created_at', 'Submitted')
                    ->render(fn (ReferralApplication $app) => e(optional($app->created_at)->format('Y-m-d H:i'))),
                Sight::make('status', 'Status')
                    ->render(fn (ReferralApplication $app) => e(ReferralApplication::STATUSES[$app->status] ?? $app->status)),
                Sight::make('full_name', 'Full name'),
                Sight::make('email', 'Email'),
                Sight::make('phone', 'Phone')
                    ->render(fn (ReferralApplication $app) => e(trim((string) $app->phone) !== '' ? (string) $app->phone : '—')),
                Sight::make('message', 'How will you share?')
                    ->render(function (ReferralApplication $app): string {
                        $message = trim((string) $app->message);
                        if ($message === '') {
                            return '<span class="text-muted">Nothing written.</span>';
                        }

                        return '<div class="text-break" style="white-space: pre-wrap;">'.e($message).'</div>';
                    }),
                Sight::make('partner', 'Partner')
                    ->render(function (ReferralApplication $app): string {
                        $partner = $app->partner;
                        if ($partner === null) {
                            return '<span class="text-muted">—</span>';
                        }

                        return e($partner->name).' · '.e($partner->code);
                    }),
                Sight::make('reviewed_at', 'Reviewed')
                    ->render(function (ReferralApplication $app): string {
                        if ($app->reviewed_at === null) {
                            return '<span class="text-muted">—</span>';
                        }

                        $who = $app->reviewer?->name;

                        return e($app->reviewed_at->format('Y-m-d H:i'))
                            .($who ? ' · '.e($who) : '');
                    }),
            ]),
        ];
    }

    public function approve(ReferralApplication $application, ReferralPartnerService $partners): void
    {
        $admin = Auth::user();
        abort_unless($admin !== null, 403);

        if (! $application->isPending()) {
            Toast::warning('Application already reviewed.');

            return;
        }

        $result = $partners->approveApplication($application, $admin);
        Toast::success(sprintf(
            'Partner approved. Code: %s. Temp password: %s (email: %s)',
            $result['partner']->code,
            $result['plain_password'],
            $result['user']->email
        ));
    }

    public function reject(ReferralApplication $application, ReferralPartnerService $partners): void
    {
        $admin = Auth::user();
        abort_unless($admin !== null, 403);

        $partners->rejectApplication($application, $admin);
        Toast::info('Application rejected.');
    }
}
