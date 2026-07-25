<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Lead;
use App\Models\LeadChange;
use App\Orchid\Layouts\Leads\LeadFiltersLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LeadListScreen extends Screen
{
    public function query(): iterable
    {
        $statusFilter = trim((string) request()->input('filter.status', ''));

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->defaultSort('id', 'desc');

        // Hide spam unless the status filter explicitly selects Spam (or another status).
        if ($statusFilter === '') {
            $leads->where('status', '!=', Lead::STATUS_SPAM);
        }

        return [
            'leads' => $leads->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Leads';
    }

    public function description(): ?string
    {
        return 'Form submissions from the website. Spam is hidden by default — choose Status → Spam to review it.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.leads.assets'),
            LeadFiltersLayout::class,

            Layout::table('leads', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),

                TD::make('status', 'Status')
                    ->sort()
                    ->cantHide()
                    ->width('180px')
                    ->render(fn (Lead $lead) => view('admin.leads.status-cell', [
                        'lead' => $lead,
                    ])),

                TD::make('full_name', 'Name')
                    ->render(fn (Lead $lead) => Link::make($lead->full_name)
                        ->route('platform.leads.edit', $lead)),

                TD::make('phone', 'Phone')
                    ->render(function (Lead $lead): string {
                        $phone = trim((string) $lead->phone);
                        if ($phone === '') {
                            return '-';
                        }

                        return '<a href="tel:'.e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'.e($phone).'</a>';
                    }),

                TD::make('email', 'Email')
                    ->render(function (Lead $lead): string {
                        $email = trim((string) $lead->email);
                        if ($email === '') {
                            return '-';
                        }

                        return '<a href="mailto:'.e($email).'">'.e($email).'</a>';
                    }),

                TD::make('city', 'City')
                    ->render(fn (Lead $lead) => e((string) ($lead->city ?? '-'))),

                TD::make('message', 'Message')
                    ->render(function (Lead $lead): string {
                        $message = trim((string) ($lead->message ?? ''));
                        if ($message === '') {
                            return '-';
                        }

                        return e(Str::words($message, 5, '…'));
                    }),
            ]),
        ];
    }

    public function changeStatus(Request $request): void
    {
        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
            'status' => ['required', 'string', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);
        $from = (string) $lead->status;
        $to = $validated['status'];

        if ($from === $to) {
            Toast::info('Status unchanged.');

            return;
        }

        $lead->status = $to;
        $lead->save();

        LeadChange::recordStatusChange($lead, $from, $to, (int) $user->id);

        Toast::info('Status updated: '.$lead->statusLabel());
    }
}
