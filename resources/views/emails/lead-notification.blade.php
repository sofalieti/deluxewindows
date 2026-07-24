@php
    $fullName = trim((string) ($lead->full_name ?? '')) ?: 'Website visitor';
    $email = trim((string) ($lead->email ?? ''));
    $phone = trim((string) ($lead->phone ?? ''));
    $city = trim((string) ($lead->city ?? ''));
    $message = trim((string) ($lead->message ?? ''));
    $pageUrl = trim((string) ($lead->page_url ?? ''));
    $createdAt = optional($lead->created_at)->format('M j, Y \a\t g:i A');

    $formId = trim((string) ($meta['form_id'] ?? ''));
    $device = trim((string) ($meta['device'] ?? ''));
    $referrer = trim((string) ($meta['referrer'] ?? ''));
    $landingPage = trim((string) ($meta['landing_page'] ?? ''));
    $geoLocation = trim((string) ($meta['geo_location'] ?? ''));

    $utmRows = array_filter([
        'Source' => $lead->utm_source ?? null,
        'Medium' => $lead->utm_medium ?? null,
        'Campaign' => $lead->utm_campaign ?? null,
        'Content' => $meta['utm_content'] ?? null,
        'Term' => $meta['utm_term'] ?? null,
        'Match type' => $meta['matchtype'] ?? null,
        'Creative' => $meta['creative'] ?? null,
        'Gclid' => $meta['gclid'] ?? null,
        'Fbclid' => $meta['fbclid'] ?? null,
        'Msclkid' => $meta['msclkid'] ?? null,
    ], fn ($v) => trim((string) $v) !== '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New lead — {{ $fullName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f6;padding:24px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(15,35,60,0.08);">

        {{-- Header --}}
        <tr>
          <td style="background-color:#0b2e4f;padding:24px 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="color:#ffffff;font-size:18px;font-weight:600;">Deluxe Windows &amp; Doors</td>
              </tr>
              <tr>
                <td style="color:#9fb6cc;font-size:13px;padding-top:4px;">New website lead{{ $createdAt ? ' · '.$createdAt : '' }}</td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Lead name banner --}}
        <tr>
          <td style="padding:24px 28px 8px 28px;">
            <div style="font-size:20px;font-weight:700;color:#0b2e4f;">{{ $fullName }}</div>
            @if($city !== '')
              <div style="font-size:13px;color:#5a6a7c;margin-top:2px;">{{ $city }}</div>
            @endif
          </td>
        </tr>

        {{-- Contact details --}}
        <tr>
          <td style="padding:8px 28px 4px 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              @if($phone !== '')
              <tr>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:13px;color:#8a97a6;width:120px;">Phone</td>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:14px;">
                  <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="color:#0b2e4f;text-decoration:none;font-weight:600;">{{ $phone }}</a>
                </td>
              </tr>
              @endif
              @if($email !== '')
              <tr>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:13px;color:#8a97a6;">Email</td>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:14px;">
                  <a href="mailto:{{ $email }}" style="color:#2f6fed;text-decoration:none;">{{ $email }}</a>
                </td>
              </tr>
              @endif
              @if($formId !== '')
              <tr>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:13px;color:#8a97a6;">Form</td>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:14px;color:#33404d;">{{ $formId }}</td>
              </tr>
              @endif
              @if($pageUrl !== '')
              <tr>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:13px;color:#8a97a6;">Page</td>
                <td style="padding:10px 0;border-top:1px solid #eef1f4;font-size:14px;">
                  <a href="{{ $pageUrl }}" style="color:#2f6fed;text-decoration:none;word-break:break-all;" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($pageUrl, 70) }}</a>
                </td>
              </tr>
              @endif
            </table>
          </td>
        </tr>

        {{-- Message --}}
        @if($message !== '')
        <tr>
          <td style="padding:16px 28px 8px 28px;">
            <div style="font-size:13px;color:#8a97a6;margin-bottom:6px;">Message</div>
            <div style="background-color:#f5f7fa;border-radius:8px;padding:14px 16px;font-size:14px;line-height:1.55;color:#33404d;white-space:pre-line;">{{ $message }}</div>
          </td>
        </tr>
        @endif

        {{-- CTA --}}
        <tr>
          <td style="padding:22px 28px 4px 28px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
              <tr>
                <td style="border-radius:8px;background-color:#2f6fed;">
                  <a href="{{ $adminUrl }}" target="_blank" rel="noopener" style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                    View lead in admin panel &rarr;
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Marketing / tracking details --}}
        @if(!empty($utmRows) || $device !== '' || $referrer !== '' || $landingPage !== '' || $geoLocation !== '')
        <tr>
          <td style="padding:24px 28px 4px 28px;">
            <div style="border-top:1px solid #eef1f4;padding-top:16px;">
              <div style="font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#8a97a6;margin-bottom:10px;">Marketing details</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                @if($device !== '')
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;width:120px;">Device</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;">{{ $device }}</td>
                </tr>
                @endif
                @foreach($utmRows as $label => $value)
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;width:120px;">{{ $label }}</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;word-break:break-all;">{{ $value }}</td>
                </tr>
                @endforeach
                @if($landingPage !== '')
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;">Landing page</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;word-break:break-all;">{{ $landingPage }}</td>
                </tr>
                @endif
                @if($referrer !== '')
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;">Referrer</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;word-break:break-all;">{{ $referrer }}</td>
                </tr>
                @endif
                @if($geoLocation !== '')
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;">Geo</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;">{{ $geoLocation }}</td>
                </tr>
                @endif
                @if(!empty($lead->ip_address))
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#8a97a6;">IP address</td>
                  <td style="padding:4px 0;font-size:12px;color:#5a6a7c;">{{ $lead->ip_address }}</td>
                </tr>
                @endif
              </table>
            </div>
          </td>
        </tr>
        @endif

        {{-- Footer --}}
        <tr>
          <td style="padding:24px 28px 26px 28px;">
            <div style="border-top:1px solid #eef1f4;padding-top:14px;font-size:11px;color:#a7b1bc;">
              Automated notification from deluxewindows.com &middot; Lead #{{ $lead->id }}
            </div>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
