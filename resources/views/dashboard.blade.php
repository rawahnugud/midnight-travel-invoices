@extends('layout')
@section('title', 'Dashboard')
@section('content')
@php
  $activePage = 'dashboard';
  $pageTitle = 'Dashboard';
  $formatMoney = fn($n) => number_format((float) $n, 2) . ' ' . ($stats['currency'] ?? 'USD');
@endphp
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-value">{{ $stats['total_invoices'] ?? 0 }}</span>
    <span class="stat-label">Total Invoices</span>
  </div>
  <div class="stat-card stat-success">
    <span class="stat-value">{{ $stats['paid'] ?? 0 }}</span>
    <span class="stat-label">Paid</span>
  </div>
  <div class="stat-card stat-warning">
    <span class="stat-value">{{ $stats['pending'] ?? 0 }}</span>
    <span class="stat-label">Pending</span>
  </div>
  <div class="stat-card stat-revenue">
    <span class="stat-value">{{ $formatMoney($stats['revenue'] ?? 0) }}</span>
    <span class="stat-label">Revenue</span>
  </div>
</div>
<div class="card">
  <div class="card-header">
    <h2>Recent Invoices</h2>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">New Invoice</a>
  </div>
  <div class="card-body">
    @if($recentInvoices->count())
    <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Number</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Status</th>
          <th>Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentInvoices as $inv)
        <tr>
          <td><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
          <td>{{ $inv->customer_name }}</td>
          <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}</td>
          <td><span class="badge badge-{{ $inv->status }}">{{ $inv->status }}</span></td>
          <td>${{ number_format($inv->total, 2) }}</td>
          <td><a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-text btn-sm">Print</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    @else
    <p class="empty-state">No invoices yet. <a href="{{ route('invoices.create') }}">Create your first invoice</a>.</p>
    @endif
  </div>
</div>
@endsection
