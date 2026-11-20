@extends('layout')
@section('title', __('messages.dashboard'))
@section('content')
@php
  $activePage = 'dashboard';
  $pageTitle = __('messages.dashboard');
@endphp
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-value">{{ $stats['total_invoices'] ?? 0 }}</span>
    <span class="stat-label">{{ __('messages.total_invoices') }}</span>
  </div>
  @foreach($stats['revenue'] as $currency => $amount)
  <div class="stat-card">
    <span class="stat-value"><bdi>{{ $currency }} {{ number_format($amount, 2) }}</bdi></span>
    <span class="stat-label">{{ __('messages.paid_revenue') }}</span>
  </div>
  @endforeach
</div>
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.recent_invoices') }}</h2>
    @unless($user->isViewer())
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">{{ __('messages.new_invoice') }}</a>
    @endunless
  </div>
  <div class="card-body">
    @if($recentInvoices->count())
    <div class="table-responsive table-mobile-cards">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('messages.number') }}</th>
          <th>{{ __('messages.customer') }}</th>
          <th>{{ __('messages.date') }}</th>
          <th>{{ __('messages.total') }}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentInvoices as $inv)
        <tr>
          <td data-label="{{ __('messages.number') }}"><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
          <td data-label="{{ __('messages.customer') }}">{{ $inv->customer_name }}</td>
          <td data-label="{{ __('messages.date') }}">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}</td>
          <td data-label="{{ __('messages.total') }}"><bdi>{{ $inv->currency }} {{ number_format($inv->total, 2) }}</bdi></td>
          <td data-label=""><a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">{{ __('messages.print') }}</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    @else
    <p class="empty-state">{{ __('messages.no_invoices_yet') }} @unless($user->isViewer()) <a href="{{ route('invoices.create') }}">{{ __('messages.create_first_invoice') }}</a>. @endunless</p>
    @endif
  </div>
</div>
@endsection
