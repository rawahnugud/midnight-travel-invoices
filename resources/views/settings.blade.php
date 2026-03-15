@extends('layout')
@section('title', __('messages.settings'))
@section('content')
@php $activePage = 'settings'; $pageTitle = __('messages.settings'); @endphp
<div class="card">
  <div class="card-body">
    <h2>{{ __('messages.settings') }}</h2>
    <p>{{ __('messages.settings_intro') }}</p>
    @if($user && $user->role === 'admin')
    <ul class="settings-links">
      <li>
        <a href="{{ route('settings.business.edit') }}" class="btn btn-outline btn-sm">{{ __('messages.business_data_logo') }}</a>
        <span class="settings-link-desc">{{ __('messages.business_data_desc') }}</span>
      </li>
      <li>
        <a href="{{ route('settings.design.edit') }}" class="btn btn-outline btn-sm">{{ __('messages.design_branding') }}</a>
        <span class="settings-link-desc">{{ __('messages.design_branding_desc') }}</span>
      </li>
    </ul>
    @endif
  </div>
</div>
@endsection
