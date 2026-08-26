<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Contacts;

use App\Models\Contact;
use App\Models\ContactChange;
use App\Models\ContactComment;
use App\Services\Mailbox\MailboxEmailStatsService;
use App\Services\RingCentralPhoneCallStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ContactListScreen extends Screen
{
    /** @var array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}> */
    private array $callStatsByPhone = [];

    /** @var array<string, array{inbound: int, outbound: int, last_direction: ?string, last_at: ?int}> */
    private array $mailStatsByEmail = [];

    public function query(): iterable
    {
        $contacts = Contact::filters()
            ->with(['leads', 'emailAddresses', 'latestComment.user'])
            ->withCount('leads')
            ->withMax('leads', 'created_at')
            ->defaultSort('id', 'desc')
            ->paginate(50);

        $pageContacts = collect($contacts->items());

        $this->callStatsByPhone = app(RingCentralPhoneCallStatsService::class)->statsForPhones(
            $pageContacts->pluck('phone')->all()
        );

        $emails = $pageContacts
            ->flatMap(fn (Contact $contact) => $contact->allNormalizedEmails())
            ->all();
        $this->mailStatsByEmail = app(MailboxEmailStatsService::class)->statsForEmails($emails);

        return [
            'contacts' => $contacts,
        ];
    }

    public function name(): ?string
    {
        return 'Contacts';
    }

    public function description(): ?string
    {
        return 'Clients and every website lead linked to them.';
    }

    public function permission(): ?iterable
    {
        return ['platform.contacts'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Add contact')
                ->icon('bs.person-plus')
                ->route('platform.contacts.create'),
        ];
    }

    public function layout(): iterable
    {
        $callStats = app(RingCentralPhoneCallStatsService::class);
        $mailStats = app(MailboxEmailStatsService::class);

        return [
            Layout::view('admin.contacts.assets'),
            Layout::table('contacts', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->width('140px')
                    ->render(fn (Contact $contact) => view('admin.contacts.date-cell', [
                        'contact' => $contact,
                    ])),

                TD::make('leads_count', 'Leads')
                    ->width('120px')
                    ->render(fn (Contact $contact) => view('admin.contacts.leads-cell', [
                        'contact' => $contact,
                    ])),

                TD::make('full_name', 'Name')
                    ->sort()
                    ->width('200px')
                    ->render(function (Contact $contact): string {
                        $name = trim((string) $contact->full_name);
                        $phone = trim((string) $contact->phone);
                        $emails = $contact->allNormalizedEmails();

                        $html = '<a class="fw-semibold" href="'.e(route('platform.contacts.edit', $contact)).'">'
                            .e($name !== '' ? $name : 'Contact #'.$contact->id)
                            .'</a>';

                        if ($phone !== '') {
                            $html .= '<div class="small mt-1"><a href="tel:'
                                .e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'
                                .e($phone)
                                .'</a></div>';
                        }
                        foreach ($emails as $email) {
                            $html .= '<div class="small mt-1"><a href="mailto:'.e($email).'">'.e($email).'</a></div>';
                        }

                        return $html;
                    }),

                TD::make('calls', 'Calls')
                    ->width('110px')
                    ->render(fn (Contact $contact) => view('admin.partials.call-stats-cell', [
                        'stats' => $callStats->lookup($this->callStatsByPhone, $contact->phone),
                    ])),

                TD::make('mail', 'Mail')
                    ->width('100px')
                    ->render(fn (Contact $contact) => view('admin.leads.mail-stats-cell', [
                        'stats' => $mailStats->aggregate(
                            $this->mailStatsByEmail,
                            $contact->allNormalizedEmails(),
                        ),
                    ])),

                TD::make('note', 'Note')
                    ->width('220px')
                    ->render(fn (Contact $contact) => view('admin.contacts.note-cell', [
                        'contact' => $contact,
                    ])),

                TD::make('additional_information', 'Info')
                    ->render(function (Contact $contact): string {
                        $info = trim((string) ($contact->additional_information ?? ''));
                        if ($info === '') {
                            return '-';
                        }

                        return e(Str::words($info, 5, '…'));
                    }),
            ]),
        ];
    }

    public function addNote(Request $request): void
    {
        $validated = $request->validate([
            'contact' => ['required', 'integer', 'exists:contacts,id'],
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $contact = Contact::query()->findOrFail((int) $validated['contact']);
        $body = trim($validated['note']);

        ContactComment::query()->create([
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);

        if (Schema::hasTable('contact_changes')) {
            ContactChange::record(
                $contact,
                'comment',
                null,
                $body,
                'Comment added',
                (int) $user->id,
            );
        }

        Toast::info('Note saved.');
    }
}
