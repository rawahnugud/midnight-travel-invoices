<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — {{ optional($business)->company_name ?? 'Midnight Travel' }}</title>
  @if(optional($business)->primary_color ?? null)
  <style>:root { --mt-accent: {{ $business->primary_color }}; --mt-gold: {{ $business->accent_color }}; }</style>
  @endif
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="login-brand">
      @if(optional($business)->login_logo_url)
      <img src="{{ $business->login_logo_url }}" alt="{{ optional($business)->company_name ?? 'Logo' }}" class="login-logo-img" style="max-height:56px; max-width:180px; object-fit:contain; margin-bottom:0.75rem;" loading="lazy">
      @else
      <span class="login-logo">MT</span>
      @endif
      <h1>{{ optional($business)->company_name ?? 'Midnight Travel' }}</h1>
      <p>Invoice System</p>
    </div>
    <form method="post" action="{{ route('login') }}" class="login-form">
      @csrf
      @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>
  </div>
</body>
</html>
