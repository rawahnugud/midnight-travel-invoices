<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>@yield('title', __('messages.app_name')) — {{ __('messages.invoice_system') }}</title>
  @if(optional($business)->primary_color ?? null)
  <style>:root { --mt-accent: {{ $business->primary_color }}; --mt-gold: {{ $business->accent_color }}; }</style>
  @endif
  <link rel="preload" href="{{ asset('css/style.css') }}" as="style">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="app">
  @unless(isset($noSidebar) && $noSidebar)
  <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>
  @include('partials.sidebar')
  @endunless
  <main class="main">
    @unless(isset($noSidebar) && $noSidebar)
    @include('partials.header')
    @endunless
    <div class="content">
      @yield('content')
    </div>
  </main>
  @unless(isset($noSidebar) && $noSidebar)
  @if(isset($user) && in_array($user->role ?? '', ['admin', 'staff']))
  <a href="{{ route('invoices.create') }}" class="fab" aria-label="{{ __('messages.create_invoice') }}" title="{{ __('messages.new_invoice') }}">
    <span class="fab-icon" aria-hidden="true">+</span>
  </a>
  @endif
  @endunless
  <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
