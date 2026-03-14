@extends('layout')
@section('title', 'Settings')
@section('content')
@php $activePage = 'settings'; $pageTitle = 'Settings'; @endphp
<div class="card">
  <div class="card-body">
    <h2>Settings</h2>
    <p>Application settings can be configured here.</p>
    @if($user && $user->role === 'admin')
    <ul class="settings-links">
      <li>
        <a href="{{ route('settings.business.edit') }}" class="btn btn-outline btn-sm">Business data & logo</a>
        <span class="settings-link-desc">— Company name, address, contact, logo for invoices</span>
      </li>
      <li>
        <a href="{{ route('settings.design.edit') }}" class="btn btn-outline btn-sm">Design & branding</a>
        <span class="settings-link-desc">— Colours, login logo, printed invoice look</span>
      </li>
    </ul>
    @endif
  </div>
</div>
@endsection
