<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-logo">MT</span>
    <div>
      <span class="sidebar-title">Midnight Travel</span>
      <span class="sidebar-subtitle">Invoice System</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="{{ route('dashboard') }}" class="sidebar-link {{ ($activePage ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('invoices.index') }}" class="sidebar-link {{ ($activePage ?? '') === 'invoices' ? 'active' : '' }}">Invoices</a>
    @if($user && $user->role === 'admin')
    <a href="{{ route('users.index') }}" class="sidebar-link {{ ($activePage ?? '') === 'users' ? 'active' : '' }}">Users</a>
    @endif
    <a href="{{ route('settings') }}" class="sidebar-link {{ ($activePage ?? '') === 'settings' ? 'active' : '' }}">Settings</a>
  </nav>
  <div class="sidebar-footer">
    <span class="sidebar-user">{{ $user?->username ?? '' }} <small>({{ $user?->role ?? '' }})</small></span>
    <form action="{{ route('logout') }}" method="post" style="display:inline;">
      @csrf
      <button type="submit" class="btn btn-text">Logout</button>
    </form>
  </div>
</aside>
