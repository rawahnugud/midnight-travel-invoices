@php
  $details = $business->invoiceDetails($invoice->branch);
  $company = $invoice->issuerName($business);
  $currencySym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'][$invoice->currency ?? 'USD'] ?? ($invoice->currency ?? 'USD') . ' ';
  $invoiceHeaderColor = optional($business)->invoice_header_color ?? '#0f172a';
  $logoPath = $business?->invoiceLogoPath($invoice->branch);
  $stampPath = $business?->invoiceStampPath($invoice->branch);
  $logoUrl = $logoPath ? url($logoPath) : null;
  $stampUrl = $stampPath ? url($stampPath) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  @php
    $printUrl = url(route('invoices.print', $invoice));
    $ogImageUrl = $logoUrl ?: $stampUrl;
  @endphp
  <title>{{ __('messages.invoice') }} {{ $invoice->invoice_number }} — {{ $company }}</title>
  @if($ogImageUrl)
    <link rel="icon" href="{{ $ogImageUrl }}">
  @endif
  <link rel="canonical" href="{{ $printUrl }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="{{ __('messages.invoice') }} {{ $invoice->invoice_number }} — {{ $company }}">
  <meta property="og:description" content="Invoice from {{ $company }} to {{ $invoice->customer_name }}.">
  <meta property="og:url" content="{{ $printUrl }}">
  @if($ogImageUrl)
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">
  @endif
  <meta name="twitter:title" content="{{ __('messages.invoice') }} {{ $invoice->invoice_number }} — {{ $company }}">
  <meta name="twitter:description" content="Invoice from {{ $company }} to {{ $invoice->customer_name }}.">
  <meta name="twitter:card" content="summary_large_image">
  <style>:root { --invoice-header: {{ $invoiceHeaderColor }}; }</style>
  <link rel="stylesheet" href="{{ asset('css/print.css') }}?v={{ filemtime(public_path('css/print.css')) }}">
  @if($logoUrl)<link rel="preload" as="image" href="{{ $logoUrl }}">@endif
  @if($stampUrl)<link rel="preload" as="image" href="{{ $stampUrl }}">@endif
</head>
<body class="print-body">
  <div class="print-toolbar no-print">
    <a href="{{ route('invoices.show', $invoice) }}" class="back-link">{{ __('messages.back_to_invoice') }}</a>
    <button type="button" class="btn-print" id="btn-print">{{ __('messages.print_save_pdf') }}</button>
  </div>
  <div class="invoice-print">
    <header class="invoice-print-header">
      <div class="invoice-print-brand">
        @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $company }}" class="invoice-print-logo-img" width="160" height="44" fetchpriority="high">
        <p class="invoice-print-company-name">{{ $company }}</p>
        @else
        <span class="invoice-print-logo">{{ $company }}</span>
        @endif
        @if($details->tagline)
        <p class="invoice-print-tagline">{{ $details->tagline }}</p>
        @endif
      </div>
      <div class="invoice-print-doc">
        <h1 class="invoice-print-title">{{ strtoupper(__('messages.invoice')) }}</h1>
        <dl class="invoice-print-meta">
          <dt>{{ __('messages.invoice_number') }}</dt>
          <dd>{{ $invoice->invoice_number }}</dd>
          <dt>{{ __('messages.date') }}</dt>
          <dd>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M j, Y') }}</dd>
          @if($invoice->status === 'cancelled')
          <dt>{{ __('messages.status') }}</dt>
          <dd><strong>{{ __('messages.cancelled') }}</strong></dd>
          @endif
        </dl>
      </div>
    </header>

    <div class="invoice-print-parties">
      <div class="invoice-print-from">
        <strong class="invoice-print-label">{{ __('messages.from') }}</strong>
        <p class="invoice-print-company">{{ $company }}</p>
        @if($details->address)<p>{{ $details->address }}</p>@endif
        @if($details->phone)<p>{{ $details->phone }}</p>@endif
        @if($details->email)<p>{{ $details->email }}</p>@endif
        @if($details->website)<p>{{ $details->website }}</p>@endif
        @if($details->tax_id)<p>{{ __('messages.tax_id') }}: {{ $details->tax_id }}</p>@endif
      </div>
      <div class="invoice-print-to">
        <strong class="invoice-print-label">{{ __('messages.bill_to') }}</strong>
        <p class="invoice-print-customer">{{ $invoice->customer_name }}</p>
        @if($invoice->customer_email)<p>{{ $invoice->customer_email }}</p>@endif
        @if($invoice->customer_phone)<p>{{ $invoice->customer_phone }}</p>@endif
        @if($invoice->customer_address)<p>{{ $invoice->customer_address }}</p>@endif
      </div>
    </div>

    <table class="invoice-print-table">
      <thead>
        <tr>
          <th class="col-item">{{ __('messages.item_description') }}</th>
          <th class="col-qty">{{ __('messages.qty') }}</th>
          <th class="col-price">{{ __('messages.unit_price_print') }}</th>
          <th class="col-amount">{{ __('messages.amount') }}</th>
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
      <div class="invoice-print-totals-block">
        <table class="invoice-print-totals">
          <tr>
            <td class="label">{{ __('messages.subtotal') }}</td>
            <td class="num">{{ $currencySym }}{{ number_format($invoice->subtotal, 2) }}</td>
          </tr>
          @if($invoice->tax_rate > 0)
          <tr>
            <td class="label">{{ __('messages.tax') }} ({{ number_format($invoice->tax_rate, 1) }}%)</td>
            <td class="num">{{ $currencySym }}{{ number_format($invoice->tax_amount, 2) }}</td>
          </tr>
          @endif
          @if($invoice->discount_amount > 0)
          <tr>
            <td class="label">{{ __('messages.discount') }}</td>
            <td class="num">−{{ $currencySym }}{{ number_format($invoice->discount_amount, 2) }}</td>
          </tr>
          @endif
          <tr class="grand-total-row">
            <td class="label">{{ __('messages.total') }}</td>
            <td class="num">{{ $currencySym }}{{ number_format($invoice->total, 2) }}</td>
          </tr>
        </table>
      </div>
      @if($stampUrl)
      <div class="invoice-print-stamp">
        <img src="{{ $stampUrl }}" alt="Stamp" class="invoice-print-stamp-img" width="100" height="80">
      </div>
      @endif
    </div>

    @if($invoice->notes || $invoice->terms)
    <div class="invoice-print-extra">
      @if($invoice->notes)
      <div class="invoice-print-notes">
        <strong>{{ __('messages.notes') }}</strong><br>{{ $invoice->notes }}
      </div>
      @endif
      @if($invoice->terms)
      <div class="invoice-print-terms">
        <strong>{{ __('messages.terms') }}</strong><br>{{ $invoice->terms }}
      </div>
      @endif
    </div>
    @endif

    <footer class="invoice-print-footer with-verification">
      <div>
        <p class="thanks">{{ __('messages.thank_you') }}</p>
        <p class="company">{{ $company }}</p>
        @if($details->address || $details->phone || $details->email)
        <p class="contact">
          @if($details->address){{ $details->address }}@endif
          @if($details->phone) · {{ $details->phone }}@endif
          @if($details->email) · {{ $details->email }}@endif
        </p>
        @endif
      </div>
      <a id="verify-invoice" href="{{ url(\Illuminate\Support\Facades\URL::signedRoute('invoice.verify', ['id' => $invoice->id], null, false)) }}">
        <span id="verification-qr" aria-hidden="true"></span>{{ __('messages.verify_invoice') }}
      </a>
      <script src="{{ asset('js/vendor/qrcode-2.0.4.js') }}"></script>
      <script>
        const code = qrcode(0, 'M');
        code.addData(document.getElementById('verify-invoice').href);
        code.make();
        document.getElementById('verification-qr').innerHTML = code.createSvgTag(2, 8);
      </script>
    </footer>
  </div>
  @if($stampUrl)
  <script src="{{ asset('js/invoice-stamp.js') }}"></script>
  @endif
  <script>
    (function() {
      var btn = document.getElementById('btn-print');
      if (btn) btn.addEventListener('click', function() { window.print(); });
      if (window.location.search.indexOf('auto=1') !== -1) {
        window.addEventListener('load', function() {
          window.requestAnimationFrame(function() { window.print(); });
        });
      }
    })();
  </script>
</body>
</html>
