@extends('layout')
@section('title', 'Invoices')
@section('content')
@php $activePage = 'invoices'; $pageTitle = 'Invoices'; @endphp
<div class="card">
  <div class="card-header">
    <h2>All Invoices</h2>
    @if($user && in_array($user->role, ['admin', 'staff']))
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">New Invoice</a>
    @endif
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($invoices->count())
    <table class="table">
      <thead>
        <tr>
          <th>Number</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Status</th>
          <th>Total</th>
          <th>Created by</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoices as $inv)
        <tr>
          <td><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
          <td>{{ $inv->customer_name }}</td>
          <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}</td>
          <td><span class="badge badge-{{ $inv->status }}">{{ $inv->status }}</span></td>
          <td>${{ number_format($inv->total ?? 0, 2) }}</td>
          <td>{{ $inv->creator?->username ?? '—' }}</td>
          <td class="actions">
            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-text btn-sm">View</a>
            <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">Print</a>
            @if($user && ($user->isAdmin() || ($user->isStaff() && $inv->created_by === $user->id)))
            <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-text btn-sm">Edit</a>
            <form action="{{ route('invoices.destroy', $inv) }}" method="post" class="inline-form" data-confirm="Delete this invoice?">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-text btn-sm btn-danger">Delete</button>
            </form>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <p class="empty-state">No invoices yet. <a href="{{ route('invoices.create') }}">Create your first invoice</a>.</p>
    @endif
  </div>
</div>
@endsection
