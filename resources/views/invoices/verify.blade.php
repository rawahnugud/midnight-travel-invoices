@php($issuer = $invoice->issuerName($business))
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('messages.verify_invoice') }} · {{ $issuer }}</title>
  @include('partials.favicon')
  <style>
    body { margin: 0; background: #faf7f8; color: #292228; font: 1rem/1.5 system-ui, sans-serif; }
    main { max-width: 28rem; margin: 8vh auto; padding: 2rem; background: white; border-top: 4px solid #85144b; }
    h1 { font-size: 1.5rem; margin: .5rem 0; }
    dl { display: grid; grid-template-columns: 1fr auto; gap: .8rem; }
    dd { font-weight: 600; margin: 0; }
  </style>
</head>
<body>
  <main>
    <strong>{{ $issuer }}</strong>
    <h1>{{ __('messages.verify_invoice') }}</h1>
    <p>{{ __('messages.verify_compare') }}</p>
    <dl>
      <dt>{{ __('messages.invoice_number') }}</dt><dd><bdi>{{ $invoice->invoice_number }}</bdi></dd>
      <dt>{{ __('messages.invoice_date') }}</dt><dd>{{ $invoice->invoice_date->format('Y-m-d') }}</dd>
      <dt>{{ __('messages.total') }}</dt><dd><bdi>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</bdi></dd>
      <dt>{{ __('messages.status') }}</dt><dd>{{ __('messages.'.$invoice->status) }}</dd>
    </dl>
  </main>
</body>
</html>
