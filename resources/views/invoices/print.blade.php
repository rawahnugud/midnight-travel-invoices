<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice {{ $invoice->invoice_number }} — {{ optional($business)->company_name ?? 'Midnight Travel' }}</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>
<body class="print-body">
  <div class="invoice-print">
    <header class="invoice-print-header">
      <div class="invoice-print-brand">
        @if(optional($business)->logo_url)
        <img src="{{ $business->logo_url }}" alt="{{ optional($business)->company_name }}" class="invoice-print-logo-img" style="max-height:56px; max-width:200px; object-fit:contain;">
        @else
        <span class="invoice-print-logo">{{ optional($business)->company_name ?? 'Midnight Travel' }}</span>
        @endif
        @if(optional($business)->tagline)
        <p class="invoice-print-tagline">{{ $business->tagline }}</p>
        @else
        <p class="invoice-print-tagline">Where adventure meets luxury</p>
        @endif
      </div>
      <div class="invoice-print-meta">
        <h1>INVOICE</h1>
        <p class="invoice-number">{{ $invoice->invoice_number }}</p>
        <p>Date: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</p>
        @if($invoice->due_date)<p>Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') }}</p>@endif
        <p><span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span></p>
      </div>
    </header>
    <div class="invoice-print-parties">
      <div>
        <strong>Bill To</strong>
        <p>{{ $invoice->customer_name }}</p>
        @if($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
        @if($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
        @if($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
      </div>
    </div>
    <table class="invoice-print-table">
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
    <div class="invoice-print-totals">
      <p>Subtotal: ${{ number_format($invoice->subtotal, 2) }}</p>
      @if($invoice->tax_rate > 0)<p>Tax ({{ $invoice->tax_rate }}%): ${{ number_format($invoice->tax_amount, 2) }}</p>@endif
      @if($invoice->discount_amount > 0)<p>Discount: -${{ number_format($invoice->discount_amount, 2) }}</p>@endif
      <p class="grand-total">Total: ${{ number_format($invoice->total, 2) }}</p>
    </div>
    @if($invoice->notes)<div class="invoice-print-notes"><strong>Notes:</strong> {{ $invoice->notes }}</div>@endif
    @if($invoice->terms)<div class="invoice-print-terms"><strong>Terms:</strong> {{ $invoice->terms }}</div>@endif
    <footer class="invoice-print-footer">
      <p>Thank you for your business.</p>
      <p><strong>{{ optional($business)->company_name ?? 'Midnight Travel' }}</strong></p>
      @if(optional($business) && (optional($business)->address || optional($business)->phone || optional($business)->email))
      @if(optional($business)->address)<p>{{ $business->address }}</p>@endif
      @if(optional($business)->phone)<span>{{ $business->phone }}</span>@endif
      @if(optional($business)->email)<span>{{ $business->email }}</span>@endif
      @endif
    </footer>
  </div>
  <script>window.onload = function() { window.print(); };</script>
</body>
</html>
