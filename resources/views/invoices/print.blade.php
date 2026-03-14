@php
  $company = optional($business)->company_name ?? 'Midnight Travel';
  $currencySym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'][$invoice->currency ?? 'USD'] ?? ($invoice->currency ?? 'USD') . ' ';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice {{ $invoice->invoice_number }} — {{ $company }}</title>
  @php $invoiceHeaderColor = optional($business)->invoice_header_color ?? '#0f172a'; @endphp
  <style>:root { --invoice-header: {{ $invoiceHeaderColor }}; }</style>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>
<body class="print-body">
  <div class="invoice-print">
    <header class="invoice-print-header">
      <div class="invoice-print-brand">
        @if(optional($business)->logo_url)
        <img src="{{ $business->logo_url }}" alt="{{ $company }}" class="invoice-print-logo-img">
        @else
        <span class="invoice-print-logo">{{ $company }}</span>
        @endif
        @if(optional($business)->tagline)
        <p class="invoice-print-tagline">{{ $business->tagline }}</p>
        @endif
      </div>
      <div class="invoice-print-doc">
        <h1 class="invoice-print-title">INVOICE</h1>
        <dl class="invoice-print-meta">
          <dt>Invoice #</dt>
          <dd>{{ $invoice->invoice_number }}</dd>
          <dt>Date</dt>
          <dd>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M j, Y') }}</dd>
          @if($invoice->due_date)
          <dt>Due date</dt>
          <dd>{{ \Carbon\Carbon::parse($invoice->due_date)->format('M j, Y') }}</dd>
          @endif
        </dl>
      </div>
    </header>

    <div class="invoice-print-parties">
      <div class="invoice-print-from">
        <strong class="invoice-print-label">From</strong>
        <p class="invoice-print-company">{{ $company }}</p>
        @if(optional($business)->address)<p>{{ $business->address }}</p>@endif
        @if(optional($business)->phone)<p>{{ $business->phone }}</p>@endif
        @if(optional($business)->email)<p>{{ $business->email }}</p>@endif
        @if(optional($business)->website)<p>{{ $business->website }}</p>@endif
        @if(optional($business)->tax_id)<p>Tax ID: {{ $business->tax_id }}</p>@endif
      </div>
      <div class="invoice-print-to">
        <strong class="invoice-print-label">Bill to</strong>
        <p class="invoice-print-customer">{{ $invoice->customer_name }}</p>
        @if($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
        @if($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
        @if($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
      </div>
    </div>

    <table class="invoice-print-table">
      <thead>
        <tr>
          <th class="col-item">Item / Description</th>
          <th class="col-qty">Qty</th>
          <th class="col-price">Unit price</th>
          <th class="col-amount">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoice->lineItems as $item)
        <tr>
          <td class="col-item">
            <span class="item-name">{{ $item->item_name }}</span>
            @if($item->description)<span class="item-desc">— {{ $item->description }}</span>@endif
          </td>
          <td class="col-qty num">{{ number_format($item->quantity, 2) }}</td>
          <td class="col-price num">{{ $currencySym }}{{ number_format($item->unit_price, 2) }}</td>
          <td class="col-amount num">{{ $currencySym }}{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="invoice-print-totals-stamp-wrap">
      <table class="invoice-print-totals">
        <tr>
          <td>Subtotal</td>
          <td class="num">{{ $currencySym }}{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_rate > 0)
        <tr>
          <td>Tax ({{ number_format($invoice->tax_rate, 1) }}%)</td>
          <td class="num">{{ $currencySym }}{{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->discount_amount > 0)
        <tr>
          <td>Discount</td>
          <td class="num">−{{ $currencySym }}{{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total-row">
          <td><strong>Total</strong></td>
          <td class="num"><strong>{{ $currencySym }}{{ number_format($invoice->total, 2) }}</strong></td>
        </tr>
      </table>
      @if(optional($business)->stamp_url)
      <div class="invoice-print-stamp">
        <img src="{{ $business->stamp_url }}" alt="Stamp" class="invoice-print-stamp-img">
      </div>
      @endif
    </div>

    @if($invoice->notes || $invoice->terms)
    <div class="invoice-print-extra">
      @if($invoice->notes)
      <div class="invoice-print-notes">
        <strong>Notes</strong><br>{{ $invoice->notes }}
      </div>
      @endif
      @if($invoice->terms)
      <div class="invoice-print-terms">
        <strong>Terms</strong><br>{{ $invoice->terms }}
      </div>
      @endif
    </div>
    @endif

    <footer class="invoice-print-footer">
      <p class="thanks">Thank you for your business.</p>
      <p class="company">{{ $company }}</p>
      @if(optional($business)->address || optional($business)->phone || optional($business)->email)
      <p class="contact">
        @if(optional($business)->address){{ $business->address }}@endif
        @if(optional($business)->phone) · {{ $business->phone }}@endif
        @if(optional($business)->email) · {{ $business->email }}@endif
      </p>
      @endif
    </footer>
  </div>
  <script>window.onload = function() { window.print(); };</script>
</body>
</html>
