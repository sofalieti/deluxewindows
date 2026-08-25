<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Contacts;

use App\Models\Contact;
use App\Models\ContactChange;
use App\Models\ContactComment;
use App\Models\MailboxMessage;
use App\Orchid\Screens\Concerns\QueuesCallTranscripts;
use App\Services\ContactFromLeadService;
use App\Services\ContactFromPhoneClickService;
use App\Services\CrmTimelineService;
use App\Services\RingCentralContactBinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ContactEditScreen extends Screen
{
    use QueuesCallTranscripts;

    public ?Contact $contact = null;

    public function query(Contact $contact): iterable
    {
        $contact->load(['leads.comments.user', 'createdBy', 'comments.user', 'phoneClicks', 'tasks.assignee']);
        if ($contact->exists && Schema::hasTable('contact_changes')) {
            $contact->load(['changes.user']);
        }
        $this->contact = $contact;

        $comments = $contact->exists
            ? $contact->timelineComments()
            : collect();

        return [
            'contact' => $contact,
            'leads' => $contact->leads,
            'comments' => $comments,
            'trafficSummary' => $contact->trafficSummary(),
            'changes' => $contact->exists && Schema::hasTable('contact_changes')
                ? $contact->changes
                : collect(),
            'calls' => $contact->exists
                ? $contact->ringCentralCallsForPhone()
                : collect(),
            'phoneClicks' => $contact->exists ? $contact->phoneClicks : collect(),
            'tasks' => $contact->exists ? $contact->tasks : collect(),
            'timeline' => $contact->exists
                ? app(CrmTimelineService::class)->forContact($contact)
                : collect(),
            'mailboxMessages' => $contact->exists
                ? MailboxMessage::query()
                    ->forParticipant($contact->email)
                    ->orderByDesc('sent_at')
                    ->limit(100)
                    ->get()
                : collect(),
        ];
    }

    public function name(): ?string
    {
        return $this->contact?->exists
            ? 'Contact #'.$this->contact->id.': '.$this->contact->full_name
            : 'Add contact';
    }

    public function description(): ?string
    {
        return 'Client details with comments, linked leads, calls, and change history.';
    }

    public function permission(): ?iterable
    {
        return ['platform.contacts'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to contacts')
                ->icon('bs.arrow-left')
                ->route('platform.contacts'),
        ];
    }

    public function layout(): iterable
    {
        $detailsLeft = Layout::blank([
            Layout::rows([
                Input::make('contact.full_name')
                    ->title('Name')
                    ->required()
                    ->maxlength(255),
                Input::make('contact.phone')
                    ->title('Phone')
                    ->type('tel')
                    ->maxlength(50),
                Input::make('contact.email')
                    ->title('Email')
                    ->type('email')
                    ->maxlength(255),
                Input::make('contact.city')
                    ->title('City')
                    ->maxlength(100),
                TextArea::make('contact.address')
                    ->title('Address')
                    ->rows(3)
                    ->maxlength(2000),
                TextArea::make('contact.additional_information')
                    ->title('Additional information about client')
                    ->rows(8)
                    ->maxlength(10000),
            ])->title('Contact details'),
            Layout::legend('contact', [
                Sight::make('id', 'ID')
                    ->render(fn (Contact $contact): string => $contact->exists ? (string) $contact->id : 'New'),
                Sight::make('created_at', 'Created')
                    ->render(fn (Contact $contact): string => optional($contact->created_at)->format('Y-m-d H:i') ?? '—'),
                Sight::make('createdBy.name', 'Created by')
                    ->render(fn (Contact $contact): string => e($contact->createdBy?->name ?? '—')),
                Sight::make('leads_count', 'Linked leads')
                    ->render(fn (Contact $contact): string => (string) $contact->leads->count()),
            ]),
        ]);

        $commentsRight = $this->contact?->exists
            ? Layout::blank([
                Layout::rows([
                    TextArea::make('comment')
                        ->title('New comment')
                        ->rows(4)
                        ->placeholder('Write a note about this client…'),
                ])->title('Comments'),
                Layout::view('admin.partials.comment-actions'),
                Layout::view('admin.contacts.comments'),
            ])
            : Layout::view('admin.contacts.comments-placeholder');

        $tabs = [
            'Details' => Layout::columns([
                $detailsLeft,
                $commentsRight,
            ]),
        ];

        if ($this->contact?->exists) {
            $tabs['Calls'] = Layout::view('admin.contacts.calls');
            $tabs['Leads'] = Layout::view('admin.contacts.leads');
            $tabs['Phone clicks'] = Layout::view('admin.contacts.phone-clicks');
            $tabs['Emails'] = Layout::view('admin.mailbox.client-messages');
            $tabs['Tasks'] = Layout::view('admin.crm.tasks-table');
            $tabs['Activity'] = Layout::view('admin.contacts.timeline');
            $tabs['History'] = Layout::view('admin.contacts.history');
            $tabs['Traffic summary'] = Layout::view('admin.contacts.traffic-summary');
        }

        return [
            Layout::view('admin.contacts.assets'),
            Layout::tabs($tabs),
            Layout::view('admin.partials.sticky-save', [
                'label' => 'Save contact',
                'method' => 'save',
            ]),
        ];
    }

    public function save(Contact $contact, Request $request, ContactFromLeadService $service, ContactFromPhoneClickService $clickService)
    {
        $callBinder = app(RingCentralContactBinder::class);

        $validated = $request->validate([
            'contact.full_name' => ['required', 'string', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:50', 'required_without:contact.email'],
            'contact.email' => ['nullable', 'email', 'max:255', 'required_without:contact.phone'],
            'contact.city' => ['nullable', 'string', 'max:100'],
            'contact.address' => ['nullable', 'string', 'max:2000'],
            'contact.additional_information' => ['nullable', 'string', 'max:10000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $values = collect($validated['contact'])
            ->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();

        $wasNew = ! $contact->exists;
        $labels = [
            'full_name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'city' => 'City',
            'address' => 'Address',
            'additional_information' => 'Additional information',
        ];

        $changes = [];
        if (! $wasNew) {
            foreach ($labels as $field => $label) {
                $oldValue = trim((string) ($contact->{$field} ?? ''));
                $newValue = trim((string) ($values[$field] ?? ''));
                if ($oldValue !== $newValue) {
                    $changes[$field] = [$oldValue, $newValue, $label];
                }
            }

            if ($changes === []) {
                Toast::info('No changes to save.');

                return redirect()->route('platform.contacts.edit', $contact);
            }
        }

        $phoneChanged = $wasNew || trim((string) ($contact->phone ?? '')) !== trim((string) ($values['phone'] ?? ''));

        DB::transaction(function () use ($contact, $values, $wasNew, $changes, $service, $clickService, $user, $callBinder, $phoneChanged): void {
            $contact->fill($values);
            if ($wasNew) {
                $contact->created_by_user_id = $user->id;
            }
            $contact->save();

            if ($wasNew) {
                $service->attachExistingMatches($contact, $user->id);
                $clickService->attachExistingMatches($contact, $user->id);
                if (Schema::hasTable('contact_changes')) {
                    ContactChange::record(
                        $contact,
                        'created',
                        null,
                        null,
                        'Contact created',
                        (int) $user->id,
                    );
                }
                $callBinder->rebindContact($contact);

                return;
            }

            if (Schema::hasTable('contact_changes')) {
                foreach ($changes as $field => [$oldValue, $newValue, $label]) {
                    ContactChange::record(
                        $contact,
                        $field,
                        $oldValue !== '' ? $oldValue : null,
                        $newValue !== '' ? $newValue : null,
                        $label.' updated',
                        (int) $user->id,
                    );
                }
            }

            if ($phoneChanged) {
                $callBinder->rebindContact($contact);
                $clickService->attachExistingMatches($contact, $user->id);
            }
        });

        Toast::info($wasNew
            ? 'Contact saved.'
            : (count($changes) === 1 ? 'Contact updated.' : count($changes).' contact fields updated.'));

        return redirect()->route('platform.contacts.edit', $contact);
    }

    public function addComment(Contact $contact, Request $request)
    {
        abort_unless($contact->exists, 404);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $body = trim($validated['comment']);

        DB::transaction(function () use ($contact, $user, $body): void {
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
        });

        Toast::info('Comment added.');

        return redirect()->route('platform.contacts.edit', $contact);
    }
}
