<!doctype html>
<html lang="{{ $data['language'] }}" dir="{{ $data['language']==='ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('letters.subject') }} · {{ $data['date'] }}</title>
  @include('partials.favicon')
  <style>
    * { box-sizing:border-box }
    body { background:#f4f5f7; color:#111; margin:0; font:12pt/1.35 "Times New Roman",serif }
    nav, article { max-width:210mm; margin:auto }
    nav { display:flex; gap:1rem; align-items:center; padding:1rem; font:1rem sans-serif }
    article { background:white; min-height:297mm; padding:35mm 17mm 25mm }
    .opening, h1 { text-align:center }
    .opening { margin:0 0 10mm }
    h1 { font-size:1em; margin:5mm 0 }
    p { margin:4mm 0 }
    table { border-collapse:collapse; width:100%; margin:10mm 0; table-layout:fixed }
    th, td { border:1px solid black; padding:2mm; text-align:start; overflow-wrap:anywhere }
    th:last-child { width:42% }
    tr, footer { break-inside:avoid }
    footer { margin-top:20mm }
    @page { size:A4; margin:35mm 17mm 25mm }
    @media print { body { background:white } nav { display:none } article { padding:0; min-height:0; max-width:none } thead { display:table-header-group } }
    @media screen and (max-width:650px) { article { padding:16px } }
  </style>
</head>
<body>
<nav><a href="{{ route('letters.edit',$letter) }}">{{ __('letters.edit') }}</a><button type="button" onclick="window.print()">{{ __('messages.print') }}</button></nav>
<article>
  <p class="opening" lang="ar">بسم الله الرحمن الرحيم</p>
  <p><strong>{{ __('letters.date') }}:</strong> <bdi>{{ \Carbon\Carbon::parse($data['date'])->format('d/m/Y') }}</bdi></p>
  <p>{{ __('letters.to') }} {{ $data['bank'] }}</p>
  <h1>{{ $data['language']==='ar' ? 'الموضوع' : 'Subject' }}: {{ __('letters.subject') }}</h1>
  <p>{{ __('letters.greeting') }}</p>
  <p>{{ __('letters.debit') }} <strong><bdi>SDG {{ \App\Support\TransferAmount::display($total) }}</bdi></strong>
    ({{ \App\Support\TransferAmount::words($total, $data['language']) }})
    {{ __('letters.from') }} <strong>{{ $data['holder'] }}</strong>{{ $data['language'] === 'ar' ? '،' : ',' }} {{ __('letters.account_no') }}
    <strong><bdi>{{ $data['account'] }}</bdi></strong> {{ __('letters.credit') }}</p>
  <table>
    <thead><tr>@foreach(['name','account','amount','words'] as $field)<th>{{ __('letters.'.$field) }}</th>@endforeach</tr></thead>
    <tbody>@foreach($data['rows'] as $row)
    <tr><td>{{ $row['name'] }}</td><td><bdi>{{ $row['account'] }}</bdi></td>
      <td><bdi>{{ \App\Support\TransferAmount::display($row['minor']) }}</bdi></td><td>{{ \App\Support\TransferAmount::words($row['minor'], $data['language']) }}</td></tr>
    @endforeach</tbody>
  </table>
  <footer><p>{{ __('letters.closing') }}</p><strong>{{ $data['signer'] }}</strong><br>{{ $data['role'] }}</footer>
</article>
</body></html>
