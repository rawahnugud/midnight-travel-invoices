@extends('layout')
@section('title', __('messages.invoices'))
@section('content')
@php $activePage = 'invoices'; $pageTitle = __('messages.invoices'); @endphp
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.invoices') }}</h2>
    @if($user && in_array($user->role, ['admin', 'staff']))
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">{{ __('messages.new_invoice') }}</a>
    @endif
  </div>
  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if($invoices->count())
    <div class="table-responsive table-mobile-cards">
      <table class="table table-invoices">
        <thead>
          <tr>
            <th class="col-number">{{ __('messages.number') }}</th>
            <th class="col-customer">{{ __('messages.customer') }}</th>
            <th class="col-date">{{ __('messages.date') }}</th>
            <th class="col-total num">{{ __('messages.total') }}</th>
            @if($user && $user->role === 'admin')
            <th class="col-author">{{ __('messages.author') }}</th>
            @endif
            <th class="col-actions">{{ __('messages.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
          <tr>
            <td class="col-number" data-label="{{ __('messages.number') }}">
              <a href="{{ route('invoices.show', $inv) }}" class="invoice-number-link">{{ $inv->invoice_number }}</a>
            </td>
            <td class="col-customer" data-label="{{ __('messages.customer') }}">{{ $inv->customer_name }}</td>
            <td class="col-date" data-label="{{ __('messages.date') }}">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('M j, Y') }}</td>
            <td class="col-total num" data-label="{{ __('messages.total') }}">{{ $inv->currency_symbol }}{{ number_format($inv->total ?? 0, 2) }}</td>
            @if($user && $user->role === 'admin')
            <td class="col-author" data-label="{{ __('messages.author') }}">{{ $inv->creator?->username ?? '—' }}</td>
            @endif
            <td class="col-actions" data-label="{{ __('messages.actions') }}">
              <div class="actions-wrap">
                <a href="{{ route('invoices.show', $inv) }}" class="btn btn-text btn-sm">{{ __('messages.view') }}</a>
                <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">{{ __('messages.print') }}</a>
                @if($user && ($user->isAdmin() || ($user->isStaff() && $inv->created_by === $user->id)))
                <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-text btn-sm">{{ __('messages.edit') }}</a>
                <form action="{{ route('invoices.destroy', $inv) }}" method="post" class="inline-form" data-confirm="{{ __('messages.delete_invoice_confirm') }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-text btn-sm btn-danger">{{ __('messages.delete') }}</button>
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
      {{ __('messages.no_invoices_yet') }} <a href="{{ route('invoices.create') }}">{{ __('messages.create_first_invoice') }}</a>.
      @else
      {{ __('messages.no_invoices_yet') }}
      @endif
    </p>
    @endif
  </div>
</div>
@endsection
