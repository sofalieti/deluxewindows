<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Lead;
use App\Models\LeadComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LeadEditScreen extends Screen
{
    public ?Lead $lead = null;

    public function query(Lead $lead): iterable
    {
        $lead->load(['comments.user']);

        $this->lead = $lead;

        return [
            'lead' => $lead,
            'comments' => $lead->comments,
        ];
    }

    public function name(): ?string
    {
        return $this->lead
            ? 'Lead #'.$this->lead->id.': '.$this->lead->full_name
            : 'Lead';
    }

    public function description(): ?string
    {
        return 'Update status and leave comments for your team.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to list')
                ->icon('bs.arrow-left')
                ->route('platform.leads'),

            Button::make('Save status')
                ->icon('bs.check-circle')
                ->method('saveStatus'),

            Button::make('Add comment')
                ->icon('bs.chat-left-text')
                ->method('addComment'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.leads.assets'),

            Layout::columns([
                Layout::legend('lead', [
                    Sight::make('id', 'ID'),
                    Sight::make('created_at', 'Received')
                        ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),
                    Sight::make('full_name', 'Name'),
                    Sight::make('phone', 'Phone')
                        ->render(function (Lead $lead): string {
                            $phone = trim((string) $lead->phone);
                            if ($phone === '') {
                                return '-';
                            }

                            return '<a href="tel:'.e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'.e($phone).'</a>';
                        }),
                    Sight::make('email', 'Email')
                        ->render(function (Lead $lead): string {
                            $email = trim((string) $lead->email);
                            if ($email === '') {
                                return '-';
                            }

                            return '<a href="mailto:'.e($email).'">'.e($email).'</a>';
                        }),
                    Sight::make('city', 'City')
                        ->render(fn (Lead $lead) => e((string) ($lead->city ?? '-'))),
                    Sight::make('page_url', 'Page')
                        ->render(function (Lead $lead): string {
                            $url = trim((string) ($lead->page_url ?? ''));
                            if ($url === '') {
                                return '-';
                            }

                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($url).'</a>';
                        }),
                    Sight::make('message', 'Message')
                        ->render(fn (Lead $lead) => nl2br(e((string) ($lead->message ?? '')))),
                ]),

                Layout::legend('lead', [
                    Sight::make('utm_source', 'UTM source')
                        ->render(fn (Lead $lead) => e((string) ($lead->utm_source ?? '-'))),
                    Sight::make('utm_medium', 'UTM medium')
                        ->render(fn (Lead $lead) => e((string) ($lead->utm_medium ?? '-'))),
                    Sight::make('utm_campaign', 'UTM campaign')
                        ->render(fn (Lead $lead) => e((string) ($lead->utm_campaign ?? '-'))),
                    Sight::make('ip_address', 'IP')
                        ->render(fn (Lead $lead) => e((string) ($lead->ip_address ?? '-'))),
                    Sight::make('status', 'Current status')
                        ->render(fn (Lead $lead) => '<span class="lead-status-badge lead-status-badge--'.e($lead->statusColor()).'">'.e($lead->statusLabel()).'</span>'),
                ]),
            ]),

            Layout::rows([
                Select::make('lead.status')
                    ->title('Status')
                    ->options(Lead::STATUSES)
                    ->required(),
            ])->title('Change status'),

            Layout::rows([
                TextArea::make('comment')
                    ->title('New comment')
                    ->rows(4)
                    ->placeholder('Write a note for your team…'),
            ])->title('Comments'),

            Layout::view('admin.leads.comments'),
        ];
    }

    public function saveStatus(Lead $lead, Request $request)
    {
        $validated = $request->validate([
            'lead.status' => ['required', 'string', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $lead->status = $validated['lead']['status'];
        $lead->save();

        Toast::info('Status updated.');

        return redirect()->route('platform.leads.edit', $lead);
    }

    public function addComment(Lead $lead, Request $request)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
            'lead.status' => ['nullable', 'string', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        // Persist status if it was changed in the same form before posting a comment.
        if (! empty($validated['lead']['status'] ?? null)) {
            $lead->status = $validated['lead']['status'];
            $lead->save();
        }

        LeadComment::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'body' => trim($validated['comment']),
        ]);

        Toast::info('Comment added.');

        return redirect()->route('platform.leads.edit', $lead);
    }
}
