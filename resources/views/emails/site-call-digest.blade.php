@php
    use App\Models\PhoneClick;

    $tz = 'America/Los_Angeles';
    $fromLabel = $windowStart->timezone($tz)->format('M j, g:i A');
    $untilLabel = $windowEnd->timezone($tz)->format('M j, g:i A T');
    $matched = $clicks->filter(
        fn (PhoneClick $click) => $click->ringcentral_status === PhoneClick::RINGCENTRAL_FOUND
    )->count();

    $formatPhone = function (?string $raw): string {
        $raw = trim((string) $raw);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return sprintf('+1 (%s) %s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7, 4));
        }
        if (strlen($digits) === 10) {
            return sprintf('+1 (%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4));
        }

        return $raw !== '' ? $raw : 'Not available';
    };

    $pageLabel = function (PhoneClick $click): string {
        $url = trim((string) $click->page_url);
        if ($url === '') {
            return '';
        }
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $url;
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site calls</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f6;padding:24px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(15,35,60,0.08);">
        <tr>
          <td style="background-color:#0b2e4f;padding:24px 28px;">
            <div style="color:#ffffff;font-size:18px;font-weight:600;">Deluxe Windows &amp; Doors</div>
            <div style="color:#9fb6cc;font-size:13px;padding-top:4px;">Website calls · {{ $fromLabel }} – {{ $untilLabel }}</div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 28px 8px 28px;">
            <div style="font-size:20px;font-weight:700;color:#0b2e4f;">{{ $clicks->count() }} {{ $clicks->count() === 1 ? 'call' : 'calls' }} from the site</div>
            <div style="font-size:13px;color:#5a6a7c;margin-top:6px;line-height:1.45;">
              These are taps on a phone number on deluxewindows.com. When RingCentral matched a real call, the caller’s number and a short summary of the conversation are included. {{ $matched }} of {{ $clicks->count() }} matched a call.
            </div>
          </td>
        </tr>
        @foreach ($clicks as $click)
          @php
              $when = ($click->ringcentral_call_started_at ?? $click->created_at)?->timezone($tz);
              $callerRaw = $click->ringCentralClientPhone();
              $caller = $callerRaw ? $formatPhone($callerRaw) : 'No caller number yet';
              $dialed = $formatPhone($click->ringcentral_to_phone ?: $click->phone);
              $page = $pageLabel($click);
              $source = trim((string) $click->source_label);
              $duration = trim((string) $click->ringCentralDurationLabel());
              $result = trim((string) $click->ringCentralStatusLabel());
          @endphp
          <tr>
            <td style="padding:8px 28px 0 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6edf3;border-radius:10px;">
                <tr>
                  <td style="padding:14px 16px 6px 16px;">
                    <div style="font-size:15px;font-weight:700;color:#0b2e4f;">{{ $caller }}</div>
                    <div style="font-size:12px;color:#8a97a6;padding-top:3px;">
                      {{ $when ? $when->format('M j, g:i A') : 'Time unknown' }}
                      @if($result !== '') · {{ $result }} @endif
                      @if($duration !== '') · {{ $duration }} @endif
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:4px 16px 12px 16px;font-size:14px;line-height:1.5;color:#243446;">
                    {{ $descriptions[$click->id] ?? '' }}
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 16px 14px 16px;font-size:12px;line-height:1.5;color:#5a6a7c;">
                    Called {{ $dialed }}
                    @if($page !== '') · {{ $page }} @endif
                    @if($source !== '') · {{ $source }} @endif
                    · <a href="{{ route('platform.phone-clicks.view', $click) }}" style="color:#0b2e4f;">Open</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endforeach
        <tr>
          <td style="padding:18px 28px 24px 28px;font-size:12px;color:#8a97a6;">
            Sent at the end of the day, Pacific time. Calls after 8:00 PM are in the next report.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
