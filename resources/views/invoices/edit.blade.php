@extends('layout')
@section('title', 'Edit Invoice')
@section('content')
@php
  $activePage = 'invoices';
  $pageTitle = 'Edit Invoice';
  $inv = $invoice;
  $items = $inv->items ?? $inv->lineItems;
  if ($items->isEmpty()) { $items = collect([(object)['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]]); }
@endphp
<div class="card">
  <div class="card-body">
    <form method="post" action="{{ route('invoices.update', $inv) }}" id="invoice-form">
      @csrf
      @method('PUT')
      <input type="hidden" name="invoice_number" value="{{ $inv->invoice_number }}">
      @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif
      <div class="form-row two-cols">
        <div class="form-group">
          <label>Invoice #</label>
          <input type="text" value="{{ $inv->invoice_number }}" readonly class="readonly">
        </div>
        <div class="form-group">
          <label for="invoice_date">Invoice Date</label>
          <input type="date" id="invoice_date" name="invoice_date" value="{{ $inv->invoice_date->format('Y-m-d') }}" required>
        </div>
        <div class="form-group">
          <label for="due_date">Due Date</label>
          <input type="date" id="due_date" name="due_date" value="{{ $inv->due_date?->format('Y-m-d') }}">
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="draft" {{ $inv->status === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="pending" {{ $inv->status === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ $inv->status === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="cancelled" {{ $inv->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label for="currency">Currency</label>
          <select id="currency" name="currency">
            <option value="USD" {{ $inv->currency === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ $inv->currency === 'EUR' ? 'selected' : '' }}>EUR</option>
            <option value="GBP" {{ $inv->currency === 'GBP' ? 'selected' : '' }}>GBP</option>
            <option value="SDG" {{ $inv->currency === 'SDG' ? 'selected' : '' }}>SDG</option>
          </select>
        </div>
      </div>
      <h3>Customer</h3>
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="customer_name">Name *</label>
          <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $inv->customer_name) }}" required>
        </div>
        <div class="form-group">
          <label for="customer_email">Email</label>
          <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email', $inv->customer_email) }}">
        </div>
        <div class="form-group">
          <label for="customer_phone">Phone</label>
          <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $inv->customer_phone) }}">
        </div>
      </div>
      <div class="form-group">
        <label for="customer_address">Address</label>
        <textarea id="customer_address" name="customer_address" rows="2">{{ old('customer_address', $inv->customer_address) }}</textarea>
      </div>
      <h3>Line Items</h3>
      <table class="table line-items-table">
        <thead>
          <tr>
            <th>Item / Description</th>
            <th class="col-qty">Qty</th>
            <th class="col-price">Unit Price</th>
            <th class="col-total">Amount</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="line-items-tbody">
          @foreach($items as $idx => $item)
          <tr class="line-item-row">
            <td>
              <input type="text" name="items[{{ $idx }}][item_name]" placeholder="Item name" value="{{ $item->item_name ?? '' }}">
              <input type="text" name="items[{{ $idx }}][description]" placeholder="Description" value="{{ $item->description ?? '' }}" class="input-desc">
            </td>
            <td class="col-qty"><input type="number" name="items[{{ $idx }}][quantity]" min="0" step="0.01" value="{{ $item->quantity ?? 1 }}" class="input-qty"></td>
            <td class="col-price"><input type="number" name="items[{{ $idx }}][unit_price]" min="0" step="0.01" value="{{ $item->unit_price ?? 0 }}" class="input-price"></td>
            <td class="col-total"><span class="line-total">0</span></td>
            <td><button type="button" class="btn btn-text btn-sm remove-line">Remove</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <button type="button" id="add-line" class="btn btn-outline btn-sm">+ Add line</button>
      <div class="form-row two-cols totals-row">
        <div class="form-group">
          <label for="tax_rate">Tax %</label>
          <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="{{ $inv->tax_rate }}">
        </div>
        <div class="form-group">
          <label for="discount_amount">Discount (amount)</label>
          <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01" value="{{ $inv->discount_amount }}">
        </div>
      </div>
      <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes', $inv->notes) }}</textarea>
      </div>
      <div class="form-group">
        <label for="terms">Terms</label>
        <textarea id="terms" name="terms" rows="2">{{ old('terms', $inv->terms) }}</textarea>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Invoice</button>
        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
