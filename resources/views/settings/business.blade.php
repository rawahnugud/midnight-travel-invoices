@extends('layout')
@section('title', __('messages.business_data_title'))
@section('content')
@php $activePage = 'settings'; $pageTitle = __('messages.business_data_title'); @endphp
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.business_data_title') }}</h2>
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('settings.business.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="company_name">{{ __('messages.company_name') }}</label>
          <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $business->company_name ?? '') }}" required>
        </div>
        <div class="form-group">
          <label for="tagline">{{ __('messages.tagline') }}</label>
          <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $business->tagline ?? '') }}" placeholder="{{ __('messages.tagline_placeholder') }}">
        </div>
      </div>
      <div class="form-group">
        <label for="address">{{ __('messages.address') }}</label>
        <textarea id="address" name="address" rows="2">{{ old('address', $business->address ?? '') }}</textarea>
      </div>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="phone">{{ __('messages.phone') }}</label>
          <input type="text" id="phone" name="phone" value="{{ old('phone', $business->phone ?? '') }}">
        </div>
        <div class="form-group">
          <label for="email">{{ __('messages.email') }}</label>
          <input type="email" id="email" name="email" value="{{ old('email', $business->email ?? '') }}">
        </div>
      </div>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="website">{{ __('messages.website') }}</label>
          <input type="url" id="website" name="website" value="{{ old('website', $business->website ?? '') }}" placeholder="{{ __('messages.website_placeholder') }}">
        </div>
        <div class="form-group">
          <label for="default_currency">{{ __('messages.default_currency') }}</label>
          <select id="default_currency" name="default_currency">
            <option value="USD" {{ ($business->default_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ ($business->default_currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR</option>
            <option value="GBP" {{ ($business->default_currency ?? '') === 'GBP' ? 'selected' : '' }}>GBP</option>
            <option value="SDG" {{ ($business->default_currency ?? '') === 'SDG' ? 'selected' : '' }}>SDG</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="tax_id">{{ __('messages.tax_id_optional') }}</label>
        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $business->tax_id ?? '') }}">
      </div>
      <div class="form-group">
        <label for="logo">{{ __('messages.logo_invoice') }}</label>
        @if($business->logo_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->logo_url }}" alt="Logo" style="max-height:80px; max-width:200px; object-fit:contain;" loading="lazy">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">{{ __('messages.logo_upload_hint') }}</p>
        </div>
        @endif
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">{{ __('messages.login_logo_formats') }}</small>
      </div>
      <div class="form-group">
        <label for="stamp">{{ __('messages.stamp_label') }}</label>
        @if($business->stamp_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->stamp_url }}" alt="Stamp" style="max-height:100px; max-width:120px; object-fit:contain;" loading="lazy">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">{{ __('messages.stamp_upload_hint') }}</p>
        </div>
        @else
        <p class="text-muted" style="font-size:0.875rem; margin-bottom:0.5rem;">{{ __('messages.stamp_optional_hint') }}</p>
        @endif
        <input type="file" id="stamp" name="stamp" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">{{ __('messages.login_logo_formats') }}</small>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('messages.save_business') }}</button>
        <a href="{{ route('settings') }}" class="btn btn-outline">{{ __('messages.back_to_settings') }}</a>
      </div>
    </form>
  </div>
</div>
@endsection
