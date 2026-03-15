@extends('layout')
@section('title', __('messages.invoice') . ' ' . $invoice->invoice_number)
@section('content')
@php
  $activePage = 'invoices';
  $pageTitle = __('messages.invoice') . ' ' . $invoice->invoice_number;
  $companyName = optional($business)->company_name ?? __('messages.app_name');
  $currencySym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'][$invoice->currency ?? 'USD'] ?? ($invoice->currency ?? 'USD') . ' ';
@endphp
<div class="card">
  <div class="card-header">
    <h2>{{ $invoice->invoice_number }}</h2>
    <p class="text-muted" style="margin:0.25rem 0 0 0; font-size:0.9rem;">{{ $companyName }}</p>
    <div class="header-actions">
      <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-primary btn-sm">{{ __('messages.print_pdf') }}</a>
      @if($user && ($user->isAdmin() || ($user->isStaff() && $invoice->created_by === $user->id)))
      <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline btn-sm">{{ __('messages.edit') }}</a>
      @endif
    </div>
  </div>
  <div class="card-body invoice-view">
    @if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    <div class="invoice-from-row" style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border);">
      <strong style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">{{ __('messages.from') }}</strong>
      <p style="margin:0.25rem 0 0 0; font-weight:600;">{{ $companyName }}</p>
      @if(optional($business)->address)<p style="margin:0.15rem 0 0 0; font-size:0.9rem; color:var(--text-muted);">{{ $business->address }}</p>@endif
      @if(optional($business)->phone || optional($business)->email)
      <p style="margin:0.15rem 0 0 0; font-size:0.9rem; color:var(--text-muted);">@if($business->phone){{ $business->phone }}@endif @if($business->phone && $business->email) · @endif @if($business->email){{ $business->email }}@endif</p>
      @endif
    </div>
    <div class="invoice-meta-row">
      <div>
        <strong>{{ __('messages.customer') }}</strong>
        <p>{{ $invoice->customer_name }}</p>
        @if($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
        @if($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
        @if($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
      </div>
      <div>
        <p><strong>{{ __('messages.date_label') }}</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</p>
        <p><strong>{{ __('messages.created_by') }}</strong> {{ $invoice->creator?->username ?? '—' }}</p>
      </div>
    </div>
    <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('messages.item') }}</th>
          <th class="num">{{ __('messages.qty') }}</th>
          <th class="num">{{ __('messages.unit_price') }}</th>
          <th class="num">{{ __('messages.amount') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoice->lineItems as $item)
        <tr>
          <td>{{ $item->item_name }}@if($item->description) — {{ $item->description }}@endif</td>
          <td class="num">{{ $item->quantity }}</td>
          <td class="num">{{ $currencySym }}{{ number_format($item->unit_price, 2) }}</td>
          <td class="num">{{ $currencySym }}{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    <div class="invoice-totals">
      <p>{{ __('messages.subtotal') }}: {{ $currencySym }}{{ number_format($invoice->subtotal, 2) }}</p>
      @if($invoice->tax_rate > 0)<p>{{ __('messages.tax') }} ({{ $invoice->tax_rate }}%): {{ $currencySym }}{{ number_format($invoice->tax_amount, 2) }}</p>@endif
      @if($invoice->discount_amount > 0)<p>{{ __('messages.discount') }}: −{{ $currencySym }}{{ number_format($invoice->discount_amount, 2) }}</p>@endif
      <p class="grand-total">{{ __('messages.total') }}: {{ $currencySym }}{{ number_format($invoice->total, 2) }}</p>
    </div>
    @if($invoice->notes)<p><strong>{{ __('messages.notes') }}:</strong> {{ $invoice->notes }}</p>@endif
    @if($invoice->terms)<p><strong>{{ __('messages.terms') }}:</strong> {{ $invoice->terms }}</p>@endif
  </div>
</div>
@endsection
