@php
    /** @var \App\Models\CrmTask $task */
@endphp
<div class="d-inline-flex gap-1 flex-wrap">
    @if ($task->isOpen())
        {!! \Orchid\Screen\Actions\Button::make('Done')
            ->icon('bs.check-lg')
            ->type(\Orchid\Support\Color::SUCCESS)
            ->method('complete', ['task' => $task->id])
            ->render() !!}
        {!! \Orchid\Screen\Actions\Button::make('+1 day')
            ->icon('bs.clock')
            ->method('snoozeDay', ['task' => $task->id])
            ->render() !!}
    @endif
    @if ($task->isClosed())
        {!! \Orchid\Screen\Actions\Button::make('Reopen')
            ->icon('bs.arrow-counterclockwise')
            ->type(\Orchid\Support\Color::PRIMARY)
            ->method('reopen', ['task' => $task->id])
            ->render() !!}
    @endif
    {!! \Orchid\Screen\Actions\Link::make('')
        ->icon('bs.pencil')
        ->route('platform.crm.tasks.edit', $task)
        ->render() !!}
</div>
