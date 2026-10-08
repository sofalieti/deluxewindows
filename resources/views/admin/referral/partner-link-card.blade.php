@if (!empty($payoutMissing))
  <div class="alert alert-warning mb-3">
    Add your Zelle / Venmo details so we can pay your rewards —
    <a href="{{ route('platform.referral.my-link') }}" class="alert-link">set payout method</a>.
  </div>
@endif
<div class="bg-white rounded shadow-sm p-4 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h5 class="mb-2">Your referral link</h5>
    <p class="mb-2 text-muted">Share this URL. Your neighbor gets ${{ config('referral.friend_credit_amount', 150) }} off, you get ${{ config('referral.reward_amount', 150) }} after their install.</p>
    <code class="d-inline-block p-2 bg-light rounded" style="word-break: break-all;">{{ $link }}</code>
  </div>
  <a href="{{ route('platform.referral.my-link') }}" class="btn btn-primary">QR codes, posters &amp; messages →</a>
</div>
