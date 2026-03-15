@extends('layout')
@section('title', 'Design & Branding')
@section('content')
@php $activePage = 'settings'; $pageTitle = 'Design & Branding'; @endphp
<div class="card">
  <div class="card-header">
    <h2>Design & Branding</h2>
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <p class="text-muted" style="margin-bottom:1.25rem;">Control the look of the app, login page, and printed invoices. Leave colors blank to keep defaults.</p>
    <form method="post" action="{{ route('settings.design.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <h3 style="margin-top:0;">App &amp; login</h3>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="primary_color">Primary colour</label>
          <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $business->primary_color) }}" style="height:38px; padding:2px; cursor:pointer;">
          <small class="text-muted">Buttons, links, sidebar accent</small>
        </div>
        <div class="form-group">
          <label for="accent_color">Accent colour</label>
          <input type="color" id="accent_color" name="accent_color" value="{{ old('accent_color', $business->accent_color) }}" style="height:38px; padding:2px; cursor:pointer;">
          <small class="text-muted">Secondary highlights</small>
        </div>
      </div>
      <div class="form-group">
        <label for="login_logo">Login page logo</label>
        @if($business->login_logo_url ?? null)
        <div class="business-logo-preview" style="margin-bottom:0.75rem;">
          <img src="{{ $business->login_logo_url }}" alt="Login logo" style="max-height:56px; max-width:180px; object-fit:contain;" loading="lazy">
          <p class="text-muted" style="font-size:0.875rem; margin-top:0.25rem;">Upload a new file to replace. If empty, company logo is used.</p>
        </div>
        @else
        <p class="text-muted" style="font-size:0.875rem; margin-bottom:0.5rem;">Optional. If not set, the company logo from Business settings is used on the login page.</p>
        @endif
        <input type="file" id="login_logo" name="login_logo" accept="image/jpeg,image/png,image/gif,image/webp">
        <small class="text-muted">JPEG, PNG, GIF or WebP. Max 2 MB.</small>
      </div>

      <h3 style="margin-top:1.5rem;">Printed invoice</h3>
      <div class="form-group">
        <label for="invoice_header_color">Invoice header / table header colour</label>
        <input type="color" id="invoice_header_color" name="invoice_header_color" value="{{ old('invoice_header_color', $business->invoice_header_color) }}" style="height:38px; padding:2px; cursor:pointer;">
        <small class="text-muted">Leave as is to use the primary colour above.</small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save design settings</button>
        <a href="{{ route('settings') }}" class="btn btn-outline">Back to Settings</a>
      </div>
    </form>
  </div>
</div>
@endsection
