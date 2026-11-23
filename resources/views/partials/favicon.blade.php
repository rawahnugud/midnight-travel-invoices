@if($iconUrl = ($business->browser_icon_url ?? null))
  <link rel="icon" href="{{ $iconUrl }}">
@endif
