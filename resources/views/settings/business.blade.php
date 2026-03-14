@extends('layout')
@section('title', 'Business Settings')
@section('content')
@php $activePage = 'settings'; $pageTitle = 'Business Data & Logo'; @endphp
<div class="card">
  <div class="card-header">
    <h2>Business Data & Logo</h2>
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
          <label for="company_name">Company name *</label>
          <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $business->company_name ?? '') }}" required>
        </div>
        <div class="form-group">
          <label for="tagline">Tagline</label>
          <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $business->tagline ?? '') }}" placeholder="e.g. Where adventure meets luxury">
        </div>
      </div>
      <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2">{{ old('address', $business->address ?? '') }}</textarea>
      </div>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" value="{{ old('phone', $business->phone ?? '') }}">
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email', $business->email ?? '') }}">
        </div>
      </div>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="website">Website</label>
          <input type="url" id="website" name="website" value="{{ old('website', $business->website ?? '') }}" placeholder="https://">
        </div>
        <div class="form-group">
          <label for="default_currency">Default currency</label>
          <select id="default_currency" name="default_currency">
            <option value="USD" {{ ($business->default_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ ($business->default_currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR</option>
            <option value="GBP" {{ ($business->default_currency ?? '') === 'GBP' ? 'selected' : '' }}>GBP</option>
            <option value="SDG" {{ ($business->default_currency ?? '') === 'SDG' ? 'selected' : '' }}>SDG</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="tax_id">Tax ID / VAT (optional)</label>
        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $business->tax_id ?? '') }}">
      </div>
      <div class="form-group">
        <label for="logo">Logo</label>
        @if($business->logo_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->logo_url }}" alt="Current logo" style="max-height:80px; max-width:200px; object-fit:contain;">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">Current logo. Upload a new file to replace.</p>
        </div>
        @endif
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">JPEG, PNG, GIF or WebP. Max 2 MB.</small>
      </div>
      <div class="form-group">
        <label for="stamp">Invoice stamp</label>
        @if($business->stamp_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->stamp_url }}" alt="Current stamp" style="max-height:100px; max-width:120px; object-fit:contain;">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">Shown on printed/PDF invoices. Upload a new file to replace.</p>
        </div>
        @else
        <p class="text-muted" style="font-size:0.875rem; margin-bottom:0.5rem;">Optional. Shown on the printed invoice (e.g. bottom right).</p>
        @endif
        <input type="file" id="stamp" name="stamp" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">JPEG, PNG, GIF or WebP. Max 2 MB.</small>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save business settings</button>
        <a href="{{ route('settings') }}" class="btn btn-outline">Back to Settings</a>
      </div>
    </form>
  </div>
</div>
@endsection
