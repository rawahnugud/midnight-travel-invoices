<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — {{ __('messages.app_name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page">
  <div class="login-card">
    <h1>404</h1>
    <p>{{ __('messages.not_found') }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary">{{ __('messages.go_to_dashboard') }}</a>
  </div>
</body>
</html>
