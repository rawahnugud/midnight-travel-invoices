@extends('layout')
@section('title', 'Users')
@section('content')
@php $activePage = 'users'; $pageTitle = 'Users'; @endphp
<div class="card">
  <div class="card-header">
    <h2>Users</h2>
    <button type="button" class="btn btn-primary btn-sm" id="open-add-user">Add User</button>
  </div>
  <div class="card-body">
    @if(session('error'))
    <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Created</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
        <tr>
          <td>{{ $u->username }}</td>
          <td>{{ $u->email ?? '—' }}</td>
          <td><span class="badge badge-{{ $u->role }}">{{ $u->role }}</span></td>
          <td>{{ $u->created_at ? \Carbon\Carbon::parse($u->created_at)->format('Y-m-d') : '—' }}</td>
          <td>
            @if($u->id !== $user?->id)
            <button type="button" class="btn btn-text btn-sm edit-user-btn" data-id="{{ $u->id }}" data-url="{{ route('users.update', $u) }}" data-username="{{ $u->username }}" data-email="{{ $u->email ?? '' }}" data-role="{{ $u->role }}">Edit</button>
            <form action="{{ route('users.destroy', $u) }}" method="post" class="inline-form" data-confirm="Delete this user?">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-text btn-sm btn-danger">Delete</button>
            </form>
            @else
            <span class="muted">(you)</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
  </div>
</div>

<div id="user-modal" class="modal" style="display:none;">
  <div class="modal-backdrop"></div>
  <div class="modal-content">
    <h3 id="user-modal-title">Add User</h3>
    <form method="post" action="{{ route('users.store') }}" id="user-form">
      @csrf
      <input type="hidden" name="_method" id="user-method" value="POST">
      <div class="form-group">
        <label for="modal-username">Username</label>
        <input type="text" id="modal-username" name="username" required>
      </div>
      <div class="form-group">
        <label for="modal-email">Email</label>
        <input type="email" id="modal-email" name="email">
      </div>
      <div class="form-group" id="modal-password-group">
        <label for="modal-password">Password</label>
        <input type="password" id="modal-password" name="password">
      </div>
      <div class="form-group" id="modal-new-password-group" style="display:none;">
        <label for="modal-new-password">New password (leave blank to keep)</label>
        <input type="password" id="modal-new-password" name="password">
      </div>
      <div class="form-group">
        <label for="modal-role">Role</label>
        <select id="modal-role" name="role">
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="viewer">Viewer</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-outline close-modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
