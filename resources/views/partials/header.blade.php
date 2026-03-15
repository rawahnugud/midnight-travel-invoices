<header class="topbar">
  <button type="button" class="mobile-menu-btn" id="sidebar-toggle" aria-label="{{ __('messages.open_menu') }}">
    <span class="mobile-menu-icon" aria-hidden="true"></span>
  </button>
  <h1 class="topbar-title">{{ $pageTitle ?? $title ?? __('messages.dashboard') }}</h1>
  <div class="locale-switcher">
    <a href="{{ route('locale.switch', 'en') }}" class="locale-link {{ app()->getLocale() === 'en' ? 'active' : '' }}" hreflang="en">EN</a>
    <span class="locale-sep" aria-hidden="true">|</span>
    <a href="{{ route('locale.switch', 'ar') }}" class="locale-link {{ app()->getLocale() === 'ar' ? 'active' : '' }}" hreflang="ar">ع</a>
  </div>
</header>
