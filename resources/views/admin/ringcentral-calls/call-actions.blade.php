<div class="d-flex flex-column gap-1">
    {!! \Orchid\Screen\Actions\ModalToggle::make('Staff')
        ->icon('bs.person-badge')
        ->modal('markStaffModal')
        ->modalTitle('Mark '.($call->external_phone ?: 'number').' as staff')
        ->method('markStaff')
        ->asyncParameters(['call' => $call->id]) !!}
    {!! \Orchid\Screen\Actions\Button::make('Exclude')
        ->icon('bs.eye-slash')
        ->method('excludeNumber', ['call' => $call->id])
        ->confirm('Hide all calls to and from '.$call->external_phone.'?') !!}
</div>
