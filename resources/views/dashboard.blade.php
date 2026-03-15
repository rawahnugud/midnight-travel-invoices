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
</div>
<div class="card">
  <div class="card-header">
    <h2>{{ __('messages.recent_invoices') }}</h2>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">{{ __('messages.new_invoice') }}</a>
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
          <td data-label="{{ __('messages.total') }}">${{ number_format($inv->total, 2) }}</td>
          <td data-label=""><a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">{{ __('messages.print') }}</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    @else
    <p class="empty-state">{{ __('messages.no_invoices_yet') }} <a href="{{ route('invoices.create') }}">{{ __('messages.create_first_invoice') }}</a>.</p>
    @endif
  </div>
</div>
@endsection
