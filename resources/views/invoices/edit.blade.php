@extends('layout')
@section('title', __('messages.edit_invoice_title'))
@section('content')
@php
  $activePage = 'invoices';
  $pageTitle = __('messages.edit_invoice_title');
  $inv = $invoice;
  $items = $inv->items ?? $inv->lineItems;
  if ($items->isEmpty()) { $items = collect([(object)['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]]); }
@endphp
<div class="card">
  <div class="card-body">
    <form method="post" action="{{ route('invoices.update', $inv) }}" id="invoice-form"
  data-label-item-desc="{{ e(__('messages.item_description')) }}"
  data-placeholder-item="{{ e(__('messages.item_name_placeholder')) }}"
  data-placeholder-desc="{{ e(__('messages.description_placeholder')) }}"
  data-label-qty="{{ e(__('messages.qty')) }}"
  data-label-unit-price="{{ e(__('messages.unit_price')) }}"
  data-label-amount="{{ e(__('messages.amount')) }}"
  data-remove="{{ e(__('messages.remove')) }}">
      @csrf
      @method('PUT')
      <input type="hidden" name="invoice_number" value="{{ $inv->invoice_number }}">
      @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif
      <div class="form-row two-cols">
        <div class="form-group">
          <label>{{ __('messages.invoice_number') }}</label>
          <input type="text" value="{{ $inv->invoice_number }}" readonly class="readonly">
        </div>
        <div class="form-group">
          <label for="invoice_date">{{ __('messages.invoice_date') }}</label>
          <input type="date" id="invoice_date" name="invoice_date" value="{{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}" required>
        </div>
        <div class="form-group">
          <label for="status">{{ __('messages.status') }}</label>
          <select id="status" name="status">
            <option value="draft" {{ $inv->status === 'draft' ? 'selected' : '' }}>{{ __('messages.draft') }}</option>
            <option value="pending" {{ $inv->status === 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
            <option value="paid" {{ $inv->status === 'paid' ? 'selected' : '' }}>{{ __('messages.paid') }}</option>
            <option value="cancelled" {{ $inv->status === 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
          </select>
        </div>
        <div class="form-group">
          <label for="currency">{{ __('messages.currency') }}</label>
          <select id="currency" name="currency">
            <option value="USD" {{ $inv->currency === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ $inv->currency === 'EUR' ? 'selected' : '' }}>EUR</option>
            <option value="GBP" {{ $inv->currency === 'GBP' ? 'selected' : '' }}>GBP</option>
            <option value="SDG" {{ $inv->currency === 'SDG' ? 'selected' : '' }}>SDG</option>
          </select>
        </div>
      </div>
      <h3>{{ __('messages.customer') }}</h3>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="customer_name">{{ __('messages.customer_name') }}</label>
          <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $inv->customer_name) }}" required>
        </div>
        <div class="form-group">
          <label for="customer_email">{{ __('messages.customer_email') }}</label>
          <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', $inv->customer_email) }}">
        </div>
        <div class="form-group">
          <label for="customer_phone">{{ __('messages.customer_phone') }}</label>
          <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $inv->customer_phone) }}">
        </div>
      </div>
      <div class="form-group">
        <label for="customer_address">{{ __('messages.customer_address') }}</label>
        <textarea id="customer_address" name="customer_address" rows="2">{{ old('customer_address', $inv->customer_address) }}</textarea>
      </div>
      <h3>{{ __('messages.line_items') }}</h3>
      <div class="table-responsive table-mobile-cards">
      <table class="table line-items-table">
        <thead>
          <tr>
            <th>{{ __('messages.item_description') }}</th>
            <th class="col-qty">{{ __('messages.qty') }}</th>
            <th class="col-price">{{ __('messages.unit_price') }}</th>
            <th class="col-total">{{ __('messages.amount') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="line-items-tbody">
          @foreach($items as $idx => $item)
          <tr class="line-item-row">
            <td data-label="{{ __('messages.item_description') }}">
              <input type="text" name="items[{{ $idx }}][item_name]" placeholder="{{ __('messages.item_name_placeholder') }}" value="{{ $item->item_name ?? '' }}">
              <input type="text" name="items[{{ $idx }}][description]" placeholder="{{ __('messages.description_placeholder') }}" value="{{ $item->description ?? '' }}" class="input-desc">
            </td>
            <td class="col-qty" data-label="{{ __('messages.qty') }}"><input type="number" name="items[{{ $idx }}][quantity]" min="0" step="0.01" value="{{ $item->quantity ?? 1 }}" class="input-qty"></td>
            <td class="col-price" data-label="{{ __('messages.unit_price') }}"><input type="number" name="items[{{ $idx }}][unit_price]" min="0" step="0.01" value="{{ $item->unit_price ?? 0 }}" class="input-price"></td>
            <td class="col-total" data-label="{{ __('messages.amount') }}"><span class="line-total">0</span></td>
            <td data-label=""><button type="button" class="btn btn-text btn-sm remove-line">{{ __('messages.remove') }}</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
      </div>
      <button type="button" id="add-line" class="btn btn-outline btn-sm">{{ __('messages.add_line') }}</button>
      <div class="form-row two-cols totals-row">
        <div class="form-group">
          <label for="tax_rate">{{ __('messages.tax_percent') }}</label>
          <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="{{ $inv->tax_rate }}">
        </div>
        <div class="form-group">
          <label for="discount_amount">{{ __('messages.discount_amount') }}</label>
          <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01" value="{{ $inv->discount_amount }}">
        </div>
      </div>
      <div class="form-group">
        <label for="notes">{{ __('messages.notes') }}</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes', $inv->notes) }}</textarea>
      </div>
      <div class="form-group">
        <label for="terms">{{ __('messages.terms') }}</label>
        <textarea id="terms" name="terms" rows="2">{{ old('terms', $inv->terms) }}</textarea>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('messages.update_invoice') }}</button>
        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
      </div>
    </form>
  </div>
</div>
@endsection
