@extends('layout')
@section('title', __('messages.design_title'))
@section('content')
@php $activePage = 'settings'; $pageTitle = __('messages.design_title'); @endphp
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.design_title') }}</h2>
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <p class="text-muted" style="margin-bottom:1.25rem;">{{ __('messages.design_intro') }}</p>
    <form method="post" action="{{ route('settings.design.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <h3 style="margin-top:0;">{{ __('messages.app_login') }}</h3>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="primary_color">{{ __('messages.primary_color') }}</label>
          <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $business->primary_color) }}" style="height:38px; padding:2px; cursor:pointer;">
          <small class="text-muted">{{ __('messages.primary_color_hint') }}</small>
        </div>
        <div class="form-group">
          <label for="accent_color">{{ __('messages.accent_color') }}</label>
          <input type="color" id="accent_color" name="accent_color" value="{{ old('accent_color', $business->accent_color) }}" style="height:38px; padding:2px; cursor:pointer;">
          <small class="text-muted">{{ __('messages.accent_color_hint') }}</small>
        </div>
      </div>
      <div class="form-group">
        <label for="login_logo">{{ __('messages.login_logo') }}</label>
        @if($business->login_logo_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->login_logo_url }}" alt="Login logo" style="max-height:56px; max-width:180px; object-fit:contain;" loading="lazy">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">{{ __('messages.login_logo_hint') }}</p>
        </div>
        @else
        <p class="text-muted" style="font-size:0.875rem; margin-bottom:0.5rem;">{{ __('messages.login_logo_optional') }}</p>
        @endif
        <input type="file" id="login_logo" name="login_logo" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">{{ __('messages.login_logo_formats') }}</small>
      </div>

      <h3 style="margin-top:1.5rem;">{{ __('messages.printed_invoice') }}</h3>
      <div class="form-group">
        <label for="invoice_header_color">{{ __('messages.invoice_header_color') }}</label>
        <input type="color" id="invoice_header_color" name="invoice_header_color" value="{{ old('invoice_header_color', $business->invoice_header_color) }}" style="height:38px; padding:2px; cursor:pointer;">
        <small class="text-muted">{{ __('messages.invoice_header_hint') }}</small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('messages.save_design') }}</button>
        <a href="{{ route('settings') }}" class="btn btn-outline">{{ __('messages.back_to_settings') }}</a>
      </div>
    </form>
  </div>
</div>
@endsection
