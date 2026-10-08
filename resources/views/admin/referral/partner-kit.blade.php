<style>
  .rf-kit-admin { display: grid; gap: 1.25rem; margin-bottom: 1.5rem; }
  .rf-kit-admin .rf-k-offer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.5rem; border-radius: .75rem; background: linear-gradient(135deg, #08446f, #0b5a92); color: #fff; }
  .rf-kit-admin .rf-k-offer h4 { margin: 0 0 .25rem; color: #fff; font-weight: 800; }
  .rf-kit-admin .rf-k-offer p { margin: 0; opacity: .85; }
  .rf-kit-admin .rf-k-link { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
  .rf-kit-admin .rf-k-link code { padding: .6rem .9rem; border-radius: .5rem; background: rgba(255,255,255,.14); color: #fff; font-size: 1rem; word-break: break-all; }
  .rf-kit-admin .rf-k-grid { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 1.25rem; }
  .rf-kit-admin .rf-k-card { padding: 1.5rem; border-radius: .75rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
  .rf-kit-admin .rf-k-card h5 { margin: 0 0 .35rem; font-weight: 700; }
  .rf-kit-admin .rf-k-qr { width: 100%; max-width: 260px; aspect-ratio: 1; margin: 1rem auto; }
  .rf-kit-admin .rf-k-qr svg { display: block; width: 100%; height: 100%; }
  .rf-kit-admin .rf-k-qr-url { display: block; margin-bottom: .75rem; font-size: .8rem; color: #6c757d; text-align: center; word-break: break-all; }
  .rf-kit-admin .rf-k-print { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem; }
  .rf-kit-admin .rf-k-print a { display: grid; gap: .25rem; padding: 1rem; border: 1px solid #e3e8ee; border-radius: .6rem; color: inherit; text-decoration: none; transition: border-color .15s, box-shadow .15s; }
  .rf-kit-admin .rf-k-print a:hover { border-color: #0b5a92; box-shadow: 0 6px 18px -10px rgba(8,68,111,.5); }
  .rf-kit-admin .rf-k-print b { color: #08446f; }
  .rf-kit-admin .rf-k-print small { color: #6c757d; }
  .rf-kit-admin .rf-k-msgs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
  .rf-kit-admin .rf-k-msgs textarea { width: 100%; min-height: 150px; padding: .75rem; border: 1px solid #e3e8ee; border-radius: .5rem; background: #f8fafc; font-size: .9rem; resize: vertical; }
  .rf-kit-admin .rf-k-table td { vertical-align: middle; }
  .rf-kit-admin .rf-k-table code { word-break: break-all; }
  .rf-kit-admin .rf-k-tips { margin: 0; padding-left: 1.1rem; color: #495057; }
  .rf-kit-admin .rf-k-tips li + li { margin-top: .35rem; }
  @media (max-width: 991px) {
    .rf-kit-admin .rf-k-grid, .rf-kit-admin .rf-k-msgs, .rf-kit-admin .rf-k-print { grid-template-columns: minmax(0, 1fr); }
  }
</style>

<div class="rf-kit-admin" id="rf-kit-admin">
  <div class="rf-k-offer">
    <div>
      <h4>Give ${{ $kit['credit'] }}. Get ${{ $kit['reward'] }}.</h4>
      <p>Your neighbor gets ${{ $kit['credit'] }} off their project. You get ${{ $kit['reward'] }} via Zelle/Venmo {{ $kit['payout_window'] }} after their installation.</p>
    </div>
    <div class="rf-k-link">
      <code>{{ $kit['link'] }}</code>
      <button type="button" class="btn btn-light" data-rf-copy="{{ $kit['link'] }}">Copy link</button>
      <a class="btn btn-outline-light" href="{{ $kit['link'] }}" target="_blank" rel="noopener">Preview</a>
    </div>
  </div>

  <div class="rf-k-grid">
    <div class="rf-k-card">
      <h5>QR code</h5>
      <p class="text-muted small mb-2">Pick where you'll use it — scans are tracked per channel.</p>
      <select class="form-select" data-rf-qr-select="#rf-k-qr">
        @foreach ($kit['channels'] as $ch)
          <option value="{{ $ch['key'] }}" data-url="{{ $ch['url'] }}" @selected($ch['key'] === 'qr')>{{ $ch['label'] }}</option>
        @endforeach
      </select>
      <div class="rf-k-qr" id="rf-k-qr" data-rf-qr="{{ $kit['qr_default'] }}"></div>
      <span class="rf-k-qr-url" data-rf-qr-link>{{ $kit['qr_default'] }}</span>
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <button type="button" class="btn btn-primary" data-rf-download="png" data-rf-url="{{ $kit['qr_default'] }}" data-rf-name="deluxe-referral-qr">Download PNG</button>
        <button type="button" class="btn btn-outline-primary" data-rf-download="svg" data-rf-url="{{ $kit['qr_default'] }}" data-rf-name="deluxe-referral-qr">SVG (for print shops)</button>
        <button type="button" class="btn btn-link" data-rf-qr-copy data-rf-copy="{{ $kit['qr_default'] }}">Copy URL</button>
      </div>
    </div>

    <div class="rf-k-card">
      <h5>Print-ready materials</h5>
      <p class="text-muted small mb-0">Opens a print page with your personal QR code. Print at home or save as PDF and send to any print shop (Staples, FedEx Office, Vistaprint).</p>
      <div class="rf-k-print">
        @foreach ($kit['print'] as $item)
          <a href="{{ route('platform.referral.print', ['format' => $item['format']]) }}" target="_blank" rel="noopener">
            <b>{{ $item['title'] }}</b>
            <span>{{ $item['size'] }}</span>
            <small>{{ $item['hint'] }}</small>
          </a>
        @endforeach
      </div>
      <h5 class="mt-4">Where it works best</h5>
      <ul class="rf-k-tips">
        <li><b>Nextdoor:</b> post a real recommendation with a photo of your new windows — not an ad.</li>
        <li><b>Building lobby / mail room:</b> ask the manager, use the poster.</li>
        <li><b>Coffee shops, Ace / hardware stores, gyms:</b> community boards — use the poster or flyers.</li>
        <li><b>In person:</b> keep a couple of business cards in your wallet.</li>
        <li><b>Your car or truck:</b> download the SVG QR for a magnet or decal.</li>
      </ul>
    </div>
  </div>

  <div class="rf-k-card">
    <h5>Ready-to-send messages</h5>
    <p class="text-muted small">Edit freely — your personal tracked link is already inside.</p>
    <div class="rf-k-msgs">
      @foreach ($kit['messages'] as $msg)
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <b>{{ $msg['label'] }}</b>
            <button type="button" class="btn btn-sm btn-outline-primary" data-rf-copy-from="#rf-msg-{{ $msg['key'] }}">Copy</button>
          </div>
          <textarea id="rf-msg-{{ $msg['key'] }}">{{ $msg['text'] }}</textarea>
        </div>
      @endforeach
    </div>
  </div>

  <div class="rf-k-card">
    <h5>All tracked links</h5>
    <p class="text-muted small">Same offer, different tag — see which channel brings leads on your dashboard. Partner code: <code>{{ $kit['code'] }}</code></p>
    <div class="table-responsive">
      <table class="table table-sm rf-k-table mb-0">
        <tbody>
          @foreach ($kit['channels'] as $ch)
            <tr>
              <td class="text-nowrap fw-semibold">{{ $ch['label'] }}</td>
              <td><code>{{ $ch['url'] }}</code></td>
              <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-rf-copy="{{ $ch['url'] }}">Copy</button></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  (function () {
    var root = document.getElementById('rf-kit-admin');
    if (!root) return;
    var start = function () { window.DeluxeReferralQr.mount(root); };
    if (window.DeluxeReferralQr) {
      start();
      return;
    }
    var s = document.createElement('script');
    s.src = @json(asset('js/referral-qr.js').'?v='.filemtime(public_path('js/referral-qr.js')));
    s.onload = start;
    document.head.appendChild(s);
  })();
</script>
