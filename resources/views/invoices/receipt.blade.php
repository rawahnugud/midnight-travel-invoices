@php
  $logo = $business?->invoiceLogoPath($payment->branch);
  $stamp = $business?->invoiceStampPath($payment->branch);
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('messages.payment_receipt') }} {{ $payment->receiptNumber() }} — {{ $payment->issuer_name }}</title>
  @include('partials.favicon')
  <link rel="stylesheet" href="{{ asset('css/print.css') }}?v={{ filemtime(public_path('css/print.css')) }}">
  <style>
    .receipt { --invoice-header: {{ $business?->invoice_header_color ?? '#631c36' }}; }
    .receipt .invoice-print-header { flex-wrap: wrap; }
    .receipt .invoice-print-brand { flex-shrink: 1; min-width: 0; }
    .receipt .invoice-print-title { font-size: 1.4rem; }
    .receipt .invoice-print-doc { max-width: 100%; }
    .receipt .reversal-notice { border: 2px solid currentColor; color: #70152b; padding: .75rem 1rem; }
    .receipt dt, .receipt dd, .receipt p { overflow-wrap: anywhere; }
    .receipt .amounts { display: flex; flex-wrap: wrap; gap: 1.25rem; margin: 1.5rem 0; border-block-start: 2px solid var(--invoice-header); padding-block-start: 1rem; }
    .receipt .amounts > div { flex: 1 1 10rem; }
    .receipt .amounts dt { color: #475569; }
    .receipt .amounts dd { margin: 0.25rem 0; font-variant-numeric: tabular-nums; font-weight: 600; }
    .receipt .amounts .received { font-size: 1.4rem; color: var(--invoice-header); }
  </style>
</head>
<body class="print-body">
  <nav class="print-toolbar no-print">
    <a class="back-link" href="{{ route('invoices.show', $invoice) }}">{{ __('messages.back_to_invoice') }}</a>
    @if(auth()->user()?->isAdmin() && ! $payment->reversed_at)
    <a class="back-link" href="{{ route('invoices.show', $invoice) }}#receipt-correction">{{ __('messages.correct_receipt') }}</a>
    @endif
    <button class="btn-print" type="button" onclick="window.print()">{{ __('messages.print_save_pdf') }}</button>
  </nav>
  <main class="invoice-print receipt">
    <header class="invoice-print-header">
      <div class="invoice-print-brand">
        @if($logo)<img src="{{ url($logo) }}" class="invoice-print-logo-img" alt="">@endif
        <p class="invoice-print-company-name">{{ $payment->issuer_name }}</p>
      </div>
      <div class="invoice-print-doc">
        <h1 class="invoice-print-title">{{ __('messages.payment_receipt') }}</h1>
        <dl class="invoice-print-meta">
          <dt>{{ __('messages.receipt_number') }}</dt><dd><bdi>{{ $payment->receiptNumber() }}</bdi></dd>
          <dt>{{ __('messages.invoice_number') }}</dt><dd><bdi>{{ $payment->invoice_number }}</bdi></dd>
          <dt>{{ __('messages.paid_on') }}</dt><dd><bdi>{{ $payment->paid_on->format('Y-m-d') }}</bdi></dd>
        </dl>
      </div>
    </header>
    @if($payment->reversed_at)
    <p class="reversal-notice"><strong>{{ __('messages.receipt_reversed_on', ['date' => $payment->reversed_at->format('Y-m-d')]) }}</strong><br>{{ __('messages.reversed_receipt_notice') }}</p>
    @endif
    <div class="invoice-print-parties">
      <div class="invoice-print-from">
        <strong class="invoice-print-label">{{ __('messages.received_from') }}</strong>
        <p><bdi>{{ $payment->customer_name }}</bdi></p>
      </div>
      <div class="invoice-print-to">
        <strong class="invoice-print-label">{{ __('messages.payment_method') }}</strong>
        <p>{{ __('messages.payment_method_'.$payment->method) }}</p>
        @if($payment->reference !== null && $payment->reference !== '')
        <p>{{ __('messages.payment_reference') }} <bdi>{{ $payment->reference }}</bdi></p>
        @endif
      </div>
    </div>
    <dl class="amounts">
      <div>
        <dt>{{ __('messages.amount_received') }}</dt>
        <dd class="received"><bdi>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</bdi></dd>
      </div>
      <div>
        <dt>{{ __('messages.balance_after_payment') }}</dt>
        <dd><bdi>{{ $payment->currency }} {{ number_format($payment->balance_after, 2) }}</bdi></dd>
      </div>
    </dl>
    @if($stamp)
    <div class="invoice-print-stamp">
      <img src="{{ url($stamp) }}" alt="{{ __('messages.stamp_label') }}" class="invoice-print-stamp-img" width="100" height="80">
    </div>
    @endif
  </main>
  @if($stamp)
  <script src="{{ asset('js/invoice-stamp.js') }}"></script>
  @endif
</body>
</html>
