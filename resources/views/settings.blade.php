@extends('layout')
@section('title', 'Settings')
@section('content')
@php $activePage = 'settings'; $pageTitle = 'Settings'; @endphp
<div class="card">
  <div class="card-body">
    <h2>Settings</h2>
    <p>Application settings can be configured here.</p>
    @if($user && $user->role === 'admin')
    <ul class="settings-links" style="list-style:none; padding:0; margin-top:1rem;">
      <li style="margin-bottom:0.5rem;">
        <a href="{{ route('settings.business.edit') }}" class="btn btn-outline btn-sm">Business data & logo</a>
        <span class="text-muted" style="margin-left:0.5rem;">— Company name, address, contact, logo for invoices</span>
      </li>
    </ul>
    @endif
  </div>
</div>
@endsection
