@extends('layout')
@section('title', __('messages.users'))
@section('content')
@php $activePage = 'users'; $pageTitle = __('messages.users'); @endphp
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.users') }}</h2>
    <button type="button" class="btn btn-primary btn-sm" id="open-add-user">{{ __('messages.add_user') }}</button>
  </div>
  <div class="card-body">
    @if(session('error'))
    <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">
      <ul style="margin:0;padding-left:1.25rem;">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
    @endif
    <div class="table-responsive table-mobile-cards">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('messages.username') }}</th>
          <th>{{ __('messages.email') }}</th>
          <th>{{ __('messages.role') }}</th>
          <th>{{ __('messages.created') }}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
        <tr>
          <td data-label="{{ __('messages.username') }}">{{ $u->username }}</td>
          <td data-label="{{ __('messages.email') }}">{{ $u->email ?? '—' }}</td>
          <td data-label="{{ __('messages.role') }}"><span class="badge badge-{{ $u->role }}">{{ __("messages.{$u->role}") }}</span></td>
          <td data-label="{{ __('messages.created') }}">{{ $u->created_at ? \Carbon\Carbon::parse($u->created_at)->format('Y-m-d') : '—' }}</td>
          <td data-label="{{ __('messages.actions') }}">
            <div class="actions-wrap">
            <button type="button" class="btn btn-text btn-sm edit-user-btn" data-id="{{ $u->id }}" data-url="{{ route('users.update', $u) }}" data-username="{{ $u->username }}" data-email="{{ $u->email ?? '' }}" data-role="{{ $u->role }}">{{ __('messages.edit') }}</button>
            @if($u->id !== $user?->id)
            <form action="{{ route('users.destroy', $u) }}" method="post" class="inline-form" data-confirm="{{ __('messages.delete_user_confirm') }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-text btn-sm btn-danger">{{ __('messages.delete') }}</button>
            </form>
            @else
            <span class="muted">{{ __('messages.you') }}</span>
            @endif
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
  </div>
</div>

<div id="user-modal" class="modal" style="display:none;"
  data-title-add="{{ __('messages.add_user') }}"
  data-title-edit="{{ __('messages.edit_user') }}"
  data-label-password="{{ __('messages.password') }}"
  data-label-new-password="{{ __('messages.new_password') }}"
  data-section-password-add="{{ __('messages.password_section_add') }}"
  data-section-password-edit="{{ __('messages.password_section_edit') }}">
  <div class="modal-backdrop"></div>
  <div class="modal-content modal-content--user">
    <h3 id="user-modal-title">{{ __('messages.add_user') }}</h3>
    <form method="post" action="{{ route('users.store') }}" id="user-form" data-store-url="{{ route('users.store') }}">
      @csrf
      <input type="hidden" name="_method" id="user-method" value="POST">
      <div class="form-group">
        <label for="modal-username">{{ __('messages.username') }}</label>
        <input type="text" id="modal-username" name="username" required>
      </div>
      <div class="form-group">
        <label for="modal-email">{{ __('messages.email') }}</label>
        <input type="email" id="modal-email" name="email">
      </div>
      <div class="modal-password-wrap" id="modal-password-group" role="group" aria-labelledby="modal-password-section-title">
        <p class="modal-password-section-title" id="modal-password-section-title">{{ __('messages.password_section_add') }}</p>
        <div class="form-group">
          <label for="modal-password" id="modal-password-label">{{ __('messages.password') }}</label>
          <input type="password" id="modal-password" name="password" autocomplete="new-password">
        </div>
        <div class="form-group" id="modal-password-confirm-group">
          <label for="modal-password-confirmation" id="modal-password-confirm-label">{{ __('messages.confirm_password') }}</label>
          <input type="password" id="modal-password-confirmation" name="password_confirmation" autocomplete="new-password">
        </div>
        <p id="modal-password-hint" class="form-hint modal-password-hint">{{ __('messages.password_keep_hint') }}</p>
      </div>
      <div class="form-group">
        <label for="modal-role">{{ __('messages.role') }}</label>
        <select id="modal-role" name="role">
          <option value="admin">{{ __('messages.admin') }}</option>
          <option value="staff">{{ __('messages.staff') }}</option>
          <option value="viewer">{{ __('messages.viewer') }}</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
        <button type="button" class="btn btn-outline close-modal">{{ __('messages.cancel') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
