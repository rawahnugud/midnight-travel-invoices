@php
  $company = $company ?? optional($business)->company_name ?? 'Midnight Travel';
  $bottomText = $bottomText ?? 'OFFICIAL STAMP';
  $companyEsc = e(strtoupper($company));
  $bottomEsc = e(strtoupper($bottomText));
@endphp
<svg class="invoice-stamp-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" role="img" aria-label="Official stamp">
  <defs>
    <path id="invoice-stamp-top-arc" d="M 8,50 A 42,42 0 0 1 92,50" />
    <path id="invoice-stamp-bottom-arc" d="M 92,50 A 42,42 0 0 1 8,50" />
  </defs>
  <!-- Double ring border -->
  <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="1.2" />
  <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="0.6" />
  <!-- Top curved text: company name -->
  <text fill="currentColor" font-size="5.8" font-weight="700" letter-spacing="0.08em" text-anchor="middle">
    <textPath href="#invoice-stamp-top-arc" xlink:href="#invoice-stamp-top-arc" startOffset="50%">{{ $companyEsc }}</textPath>
  </text>
  <!-- Bottom curved text -->
  <text fill="currentColor" font-size="3.8" font-weight="600" letter-spacing="0.15em" text-anchor="middle">
    <textPath href="#invoice-stamp-bottom-arc" xlink:href="#invoice-stamp-bottom-arc" startOffset="50%">{{ $bottomEsc }}</textPath>
  </text>
  <!-- Center: globe icon -->
  <circle cx="50" cy="50" r="14" fill="none" stroke="currentColor" stroke-width="0.7" />
  <ellipse cx="50" cy="50" rx="14" ry="5" fill="none" stroke="currentColor" stroke-width="0.55" />
  <ellipse cx="50" cy="50" rx="5" ry="14" fill="none" stroke="currentColor" stroke-width="0.55" />
  <path d="M 50 36 L 50 64 M 35 50 L 65 50" stroke="currentColor" stroke-width="0.4" fill="none" />
</svg>
