<aside class="sidebar" id="sidebar" role="navigation">
  <div class="sidebar-brand">
    @if(optional($business)->logo_url)
    <img src="{{ $business->logo_url }}" alt="{{ optional($business)->company_name }}" class="sidebar-logo-img" style="max-height:40px; max-width:120px; object-fit:contain;" loading="lazy">
    @else
    <span class="sidebar-logo">MT</span>
    @endif
    <div>
      <span class="sidebar-title">{{ optional($business)->company_name ?? __('messages.app_name') }}</span>
      <span class="sidebar-subtitle">{{ __('messages.invoice_system') }}</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="{{ route('dashboard') }}" class="sidebar-link {{ ($activePage ?? '') === 'dashboard' ? 'active' : '' }}">{{ __('messages.nav_dashboard') }}</a>
    <a href="{{ route('invoices.index') }}" class="sidebar-link {{ ($activePage ?? '') === 'invoices' ? 'active' : '' }}">{{ __('messages.nav_invoices') }}</a>
    @if($user && $user->role === 'admin')
    <a href="{{ route('users.index') }}" class="sidebar-link {{ ($activePage ?? '') === 'users' ? 'active' : '' }}">{{ __('messages.nav_users') }}</a>
    @endif
    <a href="{{ route('settings') }}" class="sidebar-link {{ ($activePage ?? '') === 'settings' ? 'active' : '' }}">{{ __('messages.nav_settings') }}</a>
  </nav>
  <div class="sidebar-footer">
    <span class="sidebar-user">{{ $user?->username ?? '' }} <small>({{ $user?->role ? __("messages.{$user->role}") : '' }})</small></span>
    <form action="{{ route('logout') }}" method="post" style="display:inline;">
      @csrf
      <button type="submit" class="btn btn-text">{{ __('messages.logout') }}</button>
    </form>
  </div>
</aside>
