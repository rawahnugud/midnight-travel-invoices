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
    <form method="get" action="{{ route('invoices.index') }}" role="search" class="invoice-filters">
      <div class="invoice-search-row">
        <div class="form-group">
          <label for="invoice-search">{{ __('messages.invoice_search') }}</label>
          <input type="search" id="invoice-search" name="q" maxlength="255" value="{{ $search }}">
        </div>
        <button type="submit" name="filter" value="{{ $filter }}" class="btn btn-outline">{{ __('messages.search') }}</button>
      </div>
      <div class="invoice-filter-row" role="group" aria-label="{{ __('messages.status') }}">
        @foreach(['all' => 'all_invoices', 'pending' => 'pending', 'draft' => 'draft', 'paid' => 'paid', 'cancelled' => 'cancelled'] as $value => $label)
        <button type="submit" name="filter" value="{{ $value }}" class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline' }}" aria-pressed="{{ $status === $value ? 'true' : 'false' }}">{{ __('messages.'.$label) }}</button>
        @endforeach
      </div>
      @if($status === 'pending')
      <div class="invoice-filter-row invoice-payment-filters" role="group" aria-label="{{ __('messages.payment_status') }}">
        @foreach(['pending' => 'all_pending', 'unpaid' => 'unpaid', 'partially_paid' => 'partially_paid'] as $value => $label)
        <button type="submit" name="filter" value="{{ $value }}" class="btn btn-sm {{ $filter === $value ? 'btn-primary' : 'btn-outline' }}" aria-pressed="{{ $filter === $value ? 'true' : 'false' }}">{{ __('messages.'.$label) }}</button>
        @endforeach
      </div>
      @endif
      @if($search !== '' || $filter !== 'all')
      <a href="{{ route('invoices.index') }}">{{ __('messages.clear_filters') }}</a>
      @endif
    </form>
    @if($invoices->count())
    <div class="table-responsive table-mobile-cards">
      <table class="table table-invoices">
        <thead>
          <tr>
            <th class="col-number">{{ __('messages.number') }}</th>
            <th class="col-customer">{{ __('messages.customer') }}</th>
            <th class="col-date">{{ __('messages.date') }}</th>
            <th class="col-status">{{ __('messages.payment_status') }}</th>
            <th class="col-total num">{{ __('messages.total') }}</th>
            @if($user && $user->role === 'admin')
            <th class="col-author">{{ __('messages.author') }}</th>
            @endif
            <th class="col-actions">{{ __('messages.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
          @php
            $editable = !$inv->payments_exists && $user && ($user->isAdmin() || ($user->isStaff() && $inv->created_by === $user->id));
            $removable = $editable || ($user?->isAdmin() && $inv->status === 'cancelled' && !$inv->active_payments_exist);
          @endphp
          <tr>
            <td class="col-number" data-label="{{ __('messages.number') }}">
              <a href="{{ route('invoices.show', $inv) }}" class="invoice-number-link">{{ $inv->invoice_number }}</a>
            </td>
            <td class="col-customer" data-label="{{ __('messages.customer') }}">{{ $inv->customer_name }}</td>
            <td class="col-date" data-label="{{ __('messages.date') }}">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('M j, Y') }}</td>
            <td class="col-status" data-label="{{ __('messages.payment_status') }}">
              <span class="badge badge-{{ $inv->status }}">{{ __('messages.'.$inv->status) }}</span>
            </td>
            <td class="col-total num" data-label="{{ __('messages.total') }}">{{ $inv->currency_symbol }}{{ number_format($inv->total ?? 0, 2) }}
              @if($inv->status === 'pending')
              <div><small>{{ __('messages.outstanding_balance') }} <bdi>{{ $inv->currency }} {{ number_format(max(0, $inv->receipt_balance ?? $inv->total), 2) }}</bdi></small></div>
              @endif
            </td>
            @if($user && $user->role === 'admin')
            <td class="col-author" data-label="{{ __('messages.author') }}">{{ $inv->creator?->username ?? '—' }}</td>
            @endif
            <td class="col-actions" data-label="{{ __('messages.actions') }}">
              <div class="invoice-row-actions">
                <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">{{ __('messages.print') }}</a>
                @if($editable || $removable)
                <details>
                  <summary>{{ __('messages.more_actions') }}</summary>
                  @if($editable)
                  <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-text btn-sm">{{ __('messages.edit') }}</a>
                  @endif
                  @if($removable)
                  <form action="{{ route('invoices.destroy', $inv) }}" method="post" class="inline-form" data-confirm="{{ __('messages.delete_invoice_confirm') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-text btn-sm btn-danger">{{ __('messages.delete') }}</button>
                  </form>
                  @endif
                </details>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($invoices->hasPages())
    <div class="pagination-wrap" style="margin-top:1rem; display:flex; justify-content:center;">
      {{ $invoices->links('pagination.default') }}
    </div>
    @endif
    @else
    <p class="empty-state">
      @if($search !== '' || $filter !== 'all')
      {{ __('reports.no_matches') }}
      @elseif($user && in_array($user->role, ['admin', 'staff']))
      {{ __('messages.no_invoices_yet') }} <a href="{{ route('invoices.create') }}">{{ __('messages.create_first_invoice') }}</a>.
      @else
      {{ __('messages.no_invoices_yet') }}
      @endif
    </p>
    @endif
  </div>
</div>
@endsection
