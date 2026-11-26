@extends('layout')
@section('title', __('messages.customer_balance'))
@section('content')
@php $activePage = 'invoices'; $pageTitle = __('messages.customer_balance'); @endphp
<section class="card">
  <div class="card-header">
    <h2>{{ __('messages.customer_balance') }}</h2>
    <a href="{{ route('invoices.index') }}" class="btn btn-outline btn-sm">{{ __('messages.invoices') }}</a>
  </div>
  <div class="card-body">
    <form method="get" action="{{ route('invoices.customer-balance') }}" class="invoice-search-row">
      <div class="form-group">
        <label for="customer-search">{{ __('messages.customer') }}</label>
        <input id="customer-search" type="search" name="q" maxlength="255" required value="{{ $search ?: $customer }}">
      </div>
      <button class="btn btn-primary" type="submit">{{ __('messages.search') }}</button>
    </form>
    @if($invoices->isNotEmpty())
      <h3>{{ $customer }}</h3>
      <div class="stats-grid">
        @foreach($totals as $currency => $amount)
        <div class="stat-card"><span class="stat-label">{{ __('messages.total_due') }}</span><strong class="stat-value"><bdi>{{ $currency }} {{ number_format($amount, 2) }}</bdi></strong></div>
        @endforeach
      </div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>{{ __('messages.number') }}</th><th>{{ __('messages.date') }}</th><th>{{ __('messages.total') }}</th><th>{{ __('messages.outstanding_balance') }}</th></tr></thead>
        <tbody>
          @foreach($invoices as $invoice)
          <tr>
            <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
            <td><bdi>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</bdi></td>
            <td><strong><bdi>{{ $invoice->currency }} {{ number_format($invoice->outstandingAmount(), 2) }}</bdi></strong></td>
          </tr>
          @endforeach
        </tbody>
      </table></div>
    @elseif($customer === '' && $matches && $matches->count())
      <p>{{ __('messages.select_balance_customer') }}</p>
      <ul>
        @foreach($matches as $match)
        <li><a href="{{ route('invoices.customer-balance', ['q' => $search, 'customer' => $match->name]) }}">{{ $match->name }}</a></li>
        @endforeach
      </ul>
      {{ $matches->links('pagination.default') }}
    @elseif($search !== '' || $customer !== '')
      <p class="empty-state">{{ __('messages.no_pending_customer') }}</p>
    @endif
  </div>
</section>
@endsection
