@if($iconUrl = ($business->logo_url ?? $business->login_logo_url ?? null))
  <link rel="icon" href="{{ $iconUrl }}">
@endif
