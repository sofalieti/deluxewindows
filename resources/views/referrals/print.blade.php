<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,nofollow" />
  <title>{{ $formats[$format] }} — Deluxe Windows referral ({{ $partner->code }})</title>
  <style>
    @page { size: letter; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
      color: #0d2236;
      background: #e9eef3;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    :root { --blue: #08446f; --accent: #e87722; --muted: #4a5a6a; }

    .toolbar {
      position: sticky; top: 0; z-index: 5;
      display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
      padding: 12px 20px; background: #0d2236; color: #fff; font-size: 14px;
    }
    .toolbar a, .toolbar button {
      padding: 8px 14px; border: 1px solid rgba(255,255,255,.3); border-radius: 999px;
      background: transparent; color: #fff; font: inherit; text-decoration: none; cursor: pointer;
    }
    .toolbar a.is-active { background: #fff; color: #0d2236; }
    .toolbar .print { margin-left: auto; border-color: var(--accent); background: var(--accent); font-weight: 700; }
    .toolbar small { flex-basis: 100%; opacity: .7; }

    .sheet {
      position: relative; width: 8.5in; height: 11in; margin: 24px auto; overflow: hidden;
      background: #fff; box-shadow: 0 10px 40px rgba(0,0,0,.18);
    }
    .qr svg { display: block; width: 100%; height: 100%; }
    .logo { height: .55in; width: auto; }

    /* Poster */
    .poster { display: flex; flex-direction: column; height: 100%; }
    .poster__top { display: flex; align-items: center; justify-content: space-between; padding: .45in .6in .2in; }
    .poster__from { padding: .08in .18in; border-radius: 999px; background: #fff3e8; color: #b85510; font-weight: 700; font-size: 13pt; }
    .poster__body { flex: 1; display: grid; justify-items: center; align-content: start; gap: .14in; padding: .1in .6in 0; text-align: center; }
    .poster__eyebrow { margin: 0; color: var(--accent); font-weight: 800; font-size: 14pt; letter-spacing: .08em; text-transform: uppercase; }
    .poster__title { margin: 0; color: var(--blue); font-size: 46pt; font-weight: 900; line-height: .98; letter-spacing: -.01em; }
    .poster__lead { margin: 0; max-width: 6.3in; color: var(--muted); font-size: 15pt; line-height: 1.35; }
    .poster__qrwrap { display: grid; justify-items: center; gap: .06in; margin-top: .1in; padding: .16in; border: 4px solid var(--blue); border-radius: .2in; }
    .poster__qr { width: 2.8in; height: 2.8in; }
    .poster__scan { color: var(--blue); font-weight: 800; font-size: 15pt; }
    .poster__url { color: var(--muted); font-size: 11pt; }
    .poster__perks { display: flex; flex-wrap: wrap; justify-content: center; gap: .08in .25in; margin: .12in 0 0; padding: 0; list-style: none; font-size: 11.5pt; font-weight: 600; }
    .poster__perks li::before { content: "✓ "; color: #1f8a5b; font-weight: 900; }
    .poster__band { display: flex; align-items: center; justify-content: space-between; margin-top: .2in; padding: .16in .6in; background: var(--blue); color: #fff; font-size: 13pt; }
    .poster__band b { font-size: 18pt; }
    .poster__tabs { display: grid; grid-template-columns: repeat(8, 1fr); height: 1.4in; border-top: 1px dashed #9aa8b5; }
    .poster__tab { display: flex; align-items: center; justify-content: center; border-right: 1px dashed #9aa8b5; }
    .poster__tab:last-child { border-right: 0; }
    .poster__tab span { transform: rotate(-90deg); white-space: nowrap; font-size: 8.5pt; line-height: 1.25; text-align: center; }
    .poster__tab b { display: block; color: var(--blue); font-size: 9.5pt; }

    /* Flyers: 2 × (8.5 × 5.5in) */
    .flyer { display: grid; grid-template-columns: 1fr 2.55in; gap: .3in; align-items: center; height: 5.5in; padding: .45in .55in; }
    .flyer + .flyer { border-top: 1px dashed #9aa8b5; }
    .flyer__eyebrow { margin: .12in 0 .06in; color: var(--accent); font-weight: 800; font-size: 11pt; letter-spacing: .08em; text-transform: uppercase; }
    .flyer__title { margin: 0; color: var(--blue); font-size: 32pt; font-weight: 900; line-height: 1; }
    .flyer__lead { margin: .12in 0 0; color: var(--muted); font-size: 11.5pt; line-height: 1.4; }
    .flyer__perks { margin: .14in 0 0; padding: 0; list-style: none; font-size: 10.5pt; font-weight: 600; line-height: 1.55; }
    .flyer__perks li::before { content: "✓ "; color: #1f8a5b; font-weight: 900; }
    .flyer__call { margin: .14in 0 0; font-size: 11pt; }
    .flyer__call b { color: var(--blue); font-size: 14pt; }
    .flyer__qrbox { display: grid; justify-items: center; gap: .05in; padding: .12in; border: 3px solid var(--blue); border-radius: .16in; text-align: center; }
    .flyer__qr { width: 2.2in; height: 2.2in; }
    .flyer__qrbox b { color: var(--blue); font-size: 11pt; }
    .flyer__qrbox small { color: var(--muted); font-size: 8.5pt; word-break: break-all; }

    /* Business cards: Avery 8371 / 5371 — 2 × 5 of 3.5 × 2in, 0.5in top, 0.75in side */
    .cards { display: grid; grid-template-columns: repeat(2, 3.5in); grid-auto-rows: 2in; padding: .5in .75in; }
    .card { display: grid; grid-template-columns: 1fr 1.25in; gap: .1in; align-items: center; padding: .16in .18in; outline: 1px dashed #c8d2db; outline-offset: -1px; }
    .card__brand { margin: 0; color: var(--blue); font-size: 7.5pt; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .card__title { margin: .04in 0 0; color: var(--blue); font-size: 15pt; font-weight: 900; line-height: 1.02; }
    .card__lead { margin: .05in 0 0; color: var(--muted); font-size: 7pt; line-height: 1.3; }
    .card__call { margin: .06in 0 0; font-size: 7.5pt; }
    .card__call b { color: var(--accent); }
    .card__qr { width: 1.2in; height: 1.2in; }

    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .sheet { margin: 0; box-shadow: none; page-break-after: always; break-after: page; }
      .sheet:last-child { page-break-after: auto; break-after: auto; }
      .card { outline-color: transparent; }
    }
  </style>
</head>
<body>
  @php
    $query = $adminPartnerId ? ['partner' => $adminPartnerId] : [];
    $logo = '/webflow-assets/images/686acba4611e759fd8169f9d_photo_2025-07-06-22.14.41.avif';
    $perks = ['30+ years in the Bay Area', '100% employee-owned', 'Own certified crews', 'Lifetime warranty'];
  @endphp

  <div class="toolbar">
    <strong>Print kit · {{ $partner->code }}</strong>
    @foreach ($formats as $key => $label)
      <a href="{{ route('platform.referral.print', ['format' => $key] + $query) }}" @class(['is-active' => $key === $format])>{{ $label }}</a>
    @endforeach
    <button type="button" class="print" onclick="window.print()">Print / Save as PDF</button>
    <small>Tip: in the print dialog choose “Letter”, margins “None”, scale 100%, and enable “Background graphics”.</small>
  </div>

  @if ($format === 'poster')
    <div class="sheet">
      <div class="poster">
        <div class="poster__top">
          <img class="logo" src="{{ $logo }}" alt="Deluxe Windows" />
          <span class="poster__from">Recommended by your neighbor {{ $first }}</span>
        </div>
        <div class="poster__body">
          <p class="poster__eyebrow">Thinking about new windows or doors?</p>
          <h1 class="poster__title">Get ${{ $credit }} off<br />your project</h1>
          <p class="poster__lead">Scan for a free in-home estimate from the Bay Area installer your neighbors trust. Your ${{ $credit }} credit is applied automatically.</p>
          <div class="poster__qrwrap">
            <div class="qr poster__qr" data-rf-qr="{{ $qrUrl }}"></div>
            <span class="poster__scan">Scan with your phone camera</span>
            <span class="poster__url">{{ $shortUrl }}</span>
          </div>
          <ul class="poster__perks">
            @foreach ($perks as $perk)
              <li>{{ $perk }}</li>
            @endforeach
          </ul>
        </div>
        <div class="poster__band">
          <span>Or call and mention <b>{{ $first }}</b></span>
          <b>{{ $phone }}</b>
        </div>
        <div class="poster__tabs" aria-label="Tear-off tabs">
          @for ($i = 0; $i < 8; $i++)
            <div class="poster__tab"><span><b>${{ $credit }} off windows</b>{{ $phone }}<br />mention {{ \Illuminate\Support\Str::limit($first, 14, '') }}</span></div>
          @endfor
        </div>
      </div>
    </div>
  @elseif ($format === 'flyer')
    <div class="sheet">
      @for ($i = 0; $i < 2; $i++)
        <div class="flyer">
          <div>
            <img class="logo" src="{{ $logo }}" alt="Deluxe Windows" />
            <p class="flyer__eyebrow">A gift from your neighbor {{ $first }}</p>
            <h2 class="flyer__title">${{ $credit }} off new<br />windows &amp; doors</h2>
            <p class="flyer__lead">Free in-home estimate, honest itemized quote, installed in 1–2 days by our own crew.</p>
            <ul class="flyer__perks">
              @foreach ($perks as $perk)
                <li>{{ $perk }}</li>
              @endforeach
            </ul>
            <p class="flyer__call">Call <b>{{ $phone }}</b> and mention {{ $first }}</p>
          </div>
          <div class="flyer__qrbox">
            <div class="qr flyer__qr" data-rf-qr="{{ $qrUrl }}"></div>
            <b>Scan to claim ${{ $credit }}</b>
            <small>{{ $shortUrl }}</small>
          </div>
        </div>
      @endfor
    </div>
  @else
    <div class="sheet">
      <div class="cards">
        @for ($i = 0; $i < 10; $i++)
          <div class="card">
            <div>
              <p class="card__brand">Deluxe Windows</p>
              <p class="card__title">${{ $credit }} off<br />new windows</p>
              <p class="card__lead">Free estimate · own crews · 30+ yrs in the Bay Area. Referred by {{ $first }}.</p>
              <p class="card__call"><b>{{ $phone }}</b><br />{{ $shortUrl }}</p>
            </div>
            <div class="qr card__qr" data-rf-qr="{{ $qrUrl }}"></div>
          </div>
        @endfor
      </div>
    </div>
  @endif

  <script src="/vendor/qrcode-generator/qrcode.js"></script>
  <script src="/js/referral-qr.js?v={{ filemtime(public_path('js/referral-qr.js')) }}"></script>
  <script>window.DeluxeReferralQr.render(document);</script>
</body>
</html>
