@if(isset($message) && $message->attachments->isNotEmpty())
<div class="bg-white rounded shadow-sm p-4 mb-3">
  <h5 class="mb-3">Attachments</h5>
  <ul class="mb-0 list-unstyled">
    @foreach($message->attachments as $attachment)
      <li class="mb-2">
        {!! \Orchid\Screen\Actions\Button::make($attachment->filename.($attachment->size ? ' ('.number_format($attachment->size / 1024, 1).' KB)' : ''))
            ->icon('bs.download')
            ->method('downloadAttachment', ['attachment' => $attachment->id]) !!}
      </li>
    @endforeach
  </ul>
</div>
@endif
