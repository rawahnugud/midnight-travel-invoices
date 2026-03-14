@extends('layout')
@section('title', 'Invoices')
@section('content')
@php $activePage = 'invoices'; $pageTitle = 'Invoices'; @endphp
<div class="card">
  <div class="card-header">
    <h2>Invoices</h2>
    @if($user && in_array($user->role, ['admin', 'staff']))
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">New Invoice</a>
    @endif
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($invoices->count())
    <div class="table-responsive">
      <table class="table table-invoices">
        <thead>
          <tr>
            <th class="col-number">Number</th>
            <th class="col-customer">Customer</th>
            <th class="col-date">Date</th>
            <th class="col-status">Status</th>
            <th class="col-total num">Total</th>
            @if($user && $user->role === 'admin')
            <th class="col-author">Author</th>
            @endif
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
          <tr>
            <td class="col-number">
              <a href="{{ route('invoices.show', $inv) }}" class="invoice-number-link">{{ $inv->invoice_number }}</a>
            </td>
            <td class="col-customer">{{ $inv->customer_name }}</td>
            <td class="col-date">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('M j, Y') }}</td>
            <td class="col-status"><span class="badge badge-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
            <td class="col-total num">{{ $inv->currency_symbol }}{{ number_format($inv->total ?? 0, 2) }}</td>
            @if($user && $user->role === 'admin')
            <td class="col-author">{{ $inv->creator?->username ?? '—' }}</td>
            @endif
            <td class="col-actions">
              <div class="actions-wrap">
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
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <p class="empty-state">
      @if($user && in_array($user->role, ['admin', 'staff']))
      No invoices yet. <a href="{{ route('invoices.create') }}">Create your first invoice</a>.
      @else
      No invoices yet.
      @endif
    </p>
    @endif
  </div>
</div>
@endsection
