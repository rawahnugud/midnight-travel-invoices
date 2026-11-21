@extends('layout')
@section('title', __('messages.invoice') . ' ' . $invoice->invoice_number)
@section('content')
@php
  $activePage = 'invoices';
  $pageTitle = __('messages.invoice') . ' ' . $invoice->invoice_number;
  $canEdit = $user && ($user->isAdmin() || ($user->isStaff() && $invoice->created_by === $user->id));
  $payments = $invoice->payments;
  $outstanding = $invoice->outstandingAmount();
  $historicalPayment = $invoice->status === 'paid' && $payments->isEmpty();
  $recordable = $historicalPayment ? $invoice->total : $outstanding;
  $companyName = $invoice->issuerName($business);
  $currencySym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'][$invoice->currency ?? 'USD'] ?? ($invoice->currency ?? 'USD') . ' ';
@endphp
<div class="card">
    <div class="card-header">
    <h2>{{ $invoice->invoice_number }}</h2>
    <p class="text-muted" style="margin:0.25rem 0 0 0; font-size:0.9rem;">{{ $companyName }}</p>
    <div class="header-actions">
      <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-primary btn-sm">{{ __('messages.print_pdf') }}</a>
      @if($canEdit && $payments->isEmpty())
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
      @unless($invoice->branch)
      @if(optional($business)->address)<p style="margin:0.15rem 0 0 0; font-size:0.9rem; color:var(--text-muted);">{{ $business->address }}</p>@endif
      @if(optional($business)->phone || optional($business)->email)
      <p style="margin:0.15rem 0 0 0; font-size:0.9rem; color:var(--text-muted);">@if($business->phone){{ $business->phone }}@endif @if($business->phone && $business->email) · @endif @if($business->email){{ $business->email }}@endif</p>
      @endif
      @endunless
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
        <p><strong>{{ __('messages.payment_status') }}</strong> <span class="badge badge-{{ $invoice->status }}">{{ __('messages.'.$invoice->status) }}</span></p>
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
<section class="card" aria-labelledby="payment-heading">
  <div class="card-header">
    <h2 id="payment-heading">{{ __('messages.payments') }}</h2>
    @unless($historicalPayment || $invoice->status === 'cancelled')
    <p>{{ __('messages.outstanding_balance') }} <strong><bdi>{{ $invoice->currency }} {{ number_format($outstanding, 2) }}</bdi></strong></p>
    @endunless
  </div>
  <div class="card-body">
    @if($payments->isNotEmpty())
    <div class="table-responsive">
      <table class="table">
        <thead><tr>
          <th>{{ __('messages.receipt_number') }}</th>
          <th>{{ __('messages.paid_on') }}</th>
          <th>{{ __('messages.payment_method') }}</th>
          <th>{{ __('messages.payment_reference') }}</th>
          <th class="num">{{ __('messages.amount_received') }}</th>
        </tr></thead>
        <tbody>
          @foreach($payments as $payment)
          <tr>
            <td data-label="{{ __('messages.receipt_number') }}"><a href="{{ route('invoices.payments.show', [$invoice, $payment]) }}" aria-label="{{ __('messages.print_receipt') }} {{ $payment->receiptNumber() }}"><bdi>{{ $payment->receiptNumber() }}</bdi></a></td>
            <td data-label="{{ __('messages.paid_on') }}"><bdi>{{ $payment->paid_on->format('Y-m-d') }}</bdi></td>
            <td data-label="{{ __('messages.payment_method') }}">{{ __('messages.payment_method_'.$payment->method) }}</td>
            <td data-label="{{ __('messages.payment_reference') }}"><bdi>{{ $payment->reference ?? '—' }}</bdi></td>
            <td class="num" data-label="{{ __('messages.amount_received') }}"><bdi>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</bdi></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <p class="text-muted">{{ __('messages.no_payments') }}</p>
    @endif
    @if($errors->any())
    <ul class="alert alert-error" role="alert">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    @endif
    @if($canEdit && $invoice->status === 'draft')
    <p>{{ __('messages.payment_draft_hint') }}</p>
    @endif
    @if($canEdit && ($invoice->status === 'pending' || $historicalPayment) && $recordable > 0)
    <form method="post" action="{{ route('invoices.payments.store', $invoice) }}">
      @csrf
      <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
      @if($historicalPayment)<p>{{ __('messages.legacy_payment_notice') }}</p>@endif
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="payment-amount">{{ __('messages.amount_received') }} ({{ $invoice->currency }})</label>
          <input id="payment-amount" name="amount" type="number" min="0.01" max="{{ $recordable }}" step="0.01" value="{{ old('amount', number_format($recordable, 2, '.', '')) }}" required @readonly($historicalPayment)>
        </div>
        <div class="form-group">
          <label for="paid-on">{{ __('messages.paid_on') }}</label>
          <input id="paid-on" name="paid_on" type="date" min="{{ $invoice->invoice_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" value="{{ old('paid_on', $historicalPayment ? '' : now()->toDateString()) }}" required>
        </div>
      </div>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="payment-method">{{ __('messages.payment_method') }}</label>
          <select id="payment-method" name="method" required>
            <option value=""></option>
            @foreach(\App\Models\Payment::METHODS as $method)
            <option value="{{ $method }}" @selected(old('method') === $method)>{{ __('messages.payment_method_'.$method) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="payment-reference">{{ __('messages.optional_reference') }}</label>
          <input id="payment-reference" name="reference" value="{{ old('reference') }}">
        </div>
      </div>
      <button class="btn btn-primary" type="submit">{{ __($historicalPayment ? 'messages.confirm_existing_payment' : 'messages.record_payment') }}</button>
    </form>
    @endif
  </div>
</section>
@endsection
