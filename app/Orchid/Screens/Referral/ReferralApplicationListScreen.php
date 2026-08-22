<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralApplication;
use App\Services\ReferralPartnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ReferralApplicationListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'applications' => ReferralApplication::query()
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Referral applications';
    }

    public function description(): ?string
    {
        return 'Pending partner applications from /referrals. Approve to create an Orchid login and referral code.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_ADMIN];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('applications', [
                TD::make('created_at', 'Submitted')
                    ->render(fn (ReferralApplication $app) => e(optional($app->created_at)->format('Y-m-d H:i'))),
                TD::make('full_name', 'Name'),
                TD::make('email', 'Email'),
                TD::make('phone', 'Phone'),
                TD::make('status', 'Status')
                    ->render(fn (ReferralApplication $app) => e(ReferralApplication::STATUSES[$app->status] ?? $app->status)),
                TD::make('message', 'Note')
                    ->render(fn (ReferralApplication $app) => e(\Illuminate\Support\Str::limit((string) $app->message, 80))),
                TD::make('actions', '')
                    ->render(function (ReferralApplication $app) {
                        if (! $app->isPending()) {
                            return '<span class="text-muted">—</span>';
                        }

                        return Button::make('Approve')
                            ->icon('bs.check-lg')
                            ->type(Color::SUCCESS)
                            ->method('approve', ['application' => $app->id])
                            ->confirm('Create partner account and email credentials will be shown once.')
                            ->render()
                            .' '
                            .Button::make('Reject')
                                ->icon('bs.x-lg')
                                ->type(Color::DANGER)
                                ->method('reject', ['application' => $app->id])
                                ->confirm('Reject this application?')
                                ->render();
                    }),
            ]),
        ];
    }

    public function approve(Request $request, ReferralPartnerService $partners): void
    {
        $validated = $request->validate([
            'application' => ['required', 'integer', 'exists:referral_applications,id'],
        ]);
        $admin = Auth::user();
        abort_unless($admin !== null, 403);

        $application = ReferralApplication::query()->findOrFail((int) $validated['application']);
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

    public function reject(Request $request, ReferralPartnerService $partners): void
    {
        $validated = $request->validate([
            'application' => ['required', 'integer', 'exists:referral_applications,id'],
        ]);
        $admin = Auth::user();
        abort_unless($admin !== null, 403);

        $application = ReferralApplication::query()->findOrFail((int) $validated['application']);
        $partners->rejectApplication($application, $admin);
        Toast::info('Application rejected.');
    }
}
