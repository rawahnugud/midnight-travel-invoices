@extends('layout')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('content')
@php $activePage = 'invoices'; $pageTitle = 'Invoice ' . $invoice->invoice_number; @endphp
<div class="card">
  <div class="card-header">
    <h2>{{ $invoice->invoice_number }}</h2>
    <div class="header-actions">
      <span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span>
      <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-primary btn-sm">Print / PDF</a>
      @if($user && ($user->isAdmin() || ($user->isStaff() && $invoice->created_by === $user->id)))
      <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline btn-sm">Edit</a>
      @endif
    </div>
  </div>
  <div class="card-body invoice-view">
    <div class="invoice-meta-row">
      <div>
        <strong>Customer</strong>
        <p>{{ $invoice->customer_name }}</p>
        @if($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
        @if($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
        @if($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
      </div>
      <div>
        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</p>
        @if($invoice->due_date)<p><strong>Due:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') }}</p>@endif
        <p><strong>Created by:</strong> {{ $invoice->creator?->username ?? '—' }}</p>
      </div>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Item</th>
          <th class="num">Qty</th>
          <th class="num">Unit Price</th>
          <th class="num">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoice->lineItems as $item)
        <tr>
          <td>{{ $item->item_name }}@if($item->description) — {{ $item->description }}@endif</td>
          <td class="num">{{ $item->quantity }}</td>
          <td class="num">${{ number_format($item->unit_price, 2) }}</td>
          <td class="num">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="invoice-totals">
      <p>Subtotal: ${{ number_format($invoice->subtotal, 2) }}</p>
      @if($invoice->tax_rate > 0)<p>Tax ({{ $invoice->tax_rate }}%): ${{ number_format($invoice->tax_amount, 2) }}</p>@endif
      @if($invoice->discount_amount > 0)<p>Discount: -${{ number_format($invoice->discount_amount, 2) }}</p>@endif
      <p class="grand-total">Total: ${{ number_format($invoice->total, 2) }}</p>
    </div>
    @if($invoice->notes)<p><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif
    @if($invoice->terms)<p><strong>Terms:</strong> {{ $invoice->terms }}</p>@endif
  </div>
</div>
@endsection
