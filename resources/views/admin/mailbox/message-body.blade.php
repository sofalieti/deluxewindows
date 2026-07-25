<div class="bg-white rounded shadow-sm p-4 mb-3">
  <h5 class="mb-3">Message</h5>
  @if(!empty($body_html))
    <div class="mailbox-body-html" style="max-width:100%;overflow-x:auto;line-height:1.45;">
      {!! $body_html !!}
    </div>
  @elseif(!empty($body_text))
    <pre style="white-space:pre-wrap;font-family:inherit;margin:0;">{{ $body_text }}</pre>
  @else
    <p class="text-muted mb-0">No message body.</p>
  @endif
</div>
