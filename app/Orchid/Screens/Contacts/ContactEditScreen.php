<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Contacts;

use App\Models\Contact;
use App\Models\ContactComment;
use App\Services\ContactFromLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ContactEditScreen extends Screen
{
    public ?Contact $contact = null;

    public function query(Contact $contact): iterable
    {
        $contact->load(['leads.comments.user', 'createdBy', 'comments.user']);
        $this->contact = $contact;

        $comments = $contact->exists
            ? $contact->timelineComments()
            : collect();

        return [
            'contact' => $contact,
            'leads' => $contact->leads,
            'comments' => $comments,
            'trafficSummary' => $contact->trafficSummary(),
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
        return 'Client details, linked leads, comments, and source summary.';
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
            Button::make('Save contact')
                ->icon('bs.check-circle')
                ->method('save'),
            Button::make('Add comment')
                ->icon('bs.chat-left-text')
                ->method('addComment')
                ->canSee($this->contact?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        $tabs = [
            'Details' => Layout::columns([
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
            ]),
        ];

        if ($this->contact?->exists) {
            $tabs['Leads'] = Layout::view('admin.contacts.leads');
            $tabs['Comments'] = Layout::blank([
                Layout::rows([
                    TextArea::make('comment')
                        ->title('New comment')
                        ->rows(4)
                        ->placeholder('Write a note about this client…'),
                ])->title('Add comment'),
                Layout::view('admin.contacts.comments'),
            ]);
            $tabs['Traffic summary'] = Layout::view('admin.contacts.traffic-summary');
        }

        return [
            Layout::view('admin.contacts.assets'),
            Layout::tabs($tabs),
        ];
    }

    public function save(Contact $contact, Request $request, ContactFromLeadService $service)
    {
        $validated = $request->validate([
            'contact.full_name' => ['required', 'string', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:50', 'required_without:contact.email'],
            'contact.email' => ['nullable', 'email', 'max:255', 'required_without:contact.phone'],
            'contact.city' => ['nullable', 'string', 'max:100'],
            'contact.address' => ['nullable', 'string', 'max:2000'],
            'contact.additional_information' => ['nullable', 'string', 'max:10000'],
        ]);

        $values = collect($validated['contact'])
            ->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();

        $wasNew = ! $contact->exists;
        $contact->fill($values);
        if ($wasNew) {
            $contact->created_by_user_id = Auth::id();
        }
        $contact->save();
        if ($wasNew) {
            $service->attachExistingMatches($contact, Auth::id());
        }

        Toast::info('Contact saved.');

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

        ContactComment::query()->create([
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'body' => trim($validated['comment']),
        ]);

        Toast::info('Comment added.');

        return redirect()->route('platform.contacts.edit', $contact);
    }
}
