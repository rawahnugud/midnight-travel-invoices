<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Midnight Travel') — Invoices</title>
  @if(optional($business)->primary_color ?? null)
  <style>:root { --mt-accent: {{ $business->primary_color }}; --mt-gold: {{ $business->accent_color }}; }</style>
  @endif
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="app">
  @unless(isset($noSidebar) && $noSidebar)
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
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
