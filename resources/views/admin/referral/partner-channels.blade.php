<div class="bg-white rounded shadow-sm p-4 mb-3">
  <h5 class="mb-1">Results by channel</h5>
  <p class="text-muted small mb-3">Each QR code, poster and message has its own tag, so you can see what actually works.</p>
  @if (empty($channels))
    <p class="mb-0 text-muted">No visits yet. Grab a QR code or message from the <a href="{{ route('platform.referral.my-link') }}">share kit</a> and post it on Nextdoor or a community board.</p>
  @else
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Channel</th>
            <th class="text-end">Visits</th>
            <th class="text-end">Calls</th>
            <th class="text-end">Leads</th>
            <th class="text-end">Sold</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($channels as $row)
            <tr>
              <td>{{ $row['label'] }}</td>
              <td class="text-end">{{ $row['visits'] }}</td>
              <td class="text-end">{{ $row['phone_clicks'] }}</td>
              <td class="text-end">{{ $row['leads'] }}</td>
              <td class="text-end fw-semibold">{{ $row['sold'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
