@extends('layout')
@section('title', __('reports.title'))
@section('content')
@php
  $pageTitle = __('reports.title');
  $filterValue = fn ($key) => is_scalar(request()->query($key)) ? (string) request()->query($key) : '';
@endphp

<div class="card">
  <div class="card-body">
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('reports.team') }}" method="get">
      <div class="form-row two-cols">
        <div class="form-group">
          <label for="report-user">{{ __('reports.creator') }}</label>
          <select name="user_id" id="report-user">
            <option value="">{{ __('reports.all_users') }}</option>
            @foreach($users as $member)
            <option value="{{ $member->id }}" @selected($filterValue('user_id') === (string) $member->id)>{{ $member->username }}{{ $member->trashed() ? ' (' . __('reports.inactive') . ')' : '' }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="report-from">{{ __('reports.from') }}</label>
          <input type="date" name="from" id="report-from" value="{{ $filterValue('from') }}">
        </div>
        <div class="form-group">
          <label for="report-to">{{ __('reports.to') }}</label>
          <input type="date" name="to" id="report-to" value="{{ $filterValue('to') }}">
        </div>
        <div class="form-group">
          <label for="report-currency">{{ __('messages.currency') }}</label>
          <select name="currency" id="report-currency">
            <option value="">{{ __('reports.all_currencies') }}</option>
            @foreach($currencies as $code)
            <option value="{{ $code }}" @selected($filterValue('currency') === $code)>{{ $code }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="report-status">{{ __('messages.status') }}</label>
          <select name="status" id="report-status">
            <option value="">{{ __('reports.all_statuses') }}</option>
            @foreach(['pending', 'paid', 'draft', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected($filterValue('status') === $status)>{{ __('messages.' . $status) }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">{{ __('reports.filter') }}</button>
      <a href="{{ route('reports.team') }}" class="btn btn-text">{{ __('reports.reset') }}</a>
    </form>
  </div>
</div>

@foreach(['totals' => $totals, 'breakdown' => $rows] as $section => $entries)
<div class="card">
  <div class="card-header"><h2>{{ __('reports.' . $section) }}</h2></div>
  <div class="card-body">
    @if($section === 'totals')<p>{{ __('reports.scope') }}</p>@endif
    @if($entries->isEmpty())
    <p class="empty-state">{{ __('reports.no_matches') }}</p>
    @else
    <div class="table-responsive table-mobile-cards">
      <table class="table">
        <thead><tr>
          @if($section === 'breakdown')<th scope="col">{{ __('reports.creator') }}</th>@endif
          <th scope="col">{{ __('messages.currency') }}</th>
          <th scope="col">{{ __('messages.invoices') }}</th>
          <th scope="col">{{ __('messages.paid') }}</th>
          <th scope="col">{{ __('messages.pending') }}</th>
          <th scope="col" class="num">{{ __('reports.invoiced') }}</th>
          <th scope="col" class="num">{{ __('messages.outstanding_balance') }}</th>
        </tr></thead>
        <tbody>
          @foreach($entries as $row)
          <tr>
            @if($section === 'breakdown')
            <td data-label="{{ __('reports.creator') }}">
              @if($row->user_id)
              <a href="{{ route('reports.team', array_merge(request()->only(['from', 'to', 'status']), ['user_id' => $row->user_id, 'currency' => $row->currency])) }}#invoice-details">{{ $row->username ?? __('reports.unknown') }}</a>
              @else
              {{ __('reports.unknown') }}
              @endif
            </td>
            @endif
            <td data-label="{{ __('messages.currency') }}"><bdi>{{ $row->currency }}</bdi></td>
            <td data-label="{{ __('messages.invoices') }}">{{ $row->invoice_count }}</td>
            <td data-label="{{ __('messages.paid') }}">{{ $row->paid_count }}</td>
            <td data-label="{{ __('messages.pending') }}">{{ $row->pending_count }}</td>
            <td data-label="{{ __('reports.invoiced') }}" class="num"><bdi>{{ number_format($row->invoiced_amount, 2) }}</bdi></td>
            <td data-label="{{ __('messages.outstanding_balance') }}" class="num"><bdi>{{ number_format($row->outstanding_amount, 2) }}</bdi></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>
@endforeach

<div class="card" id="invoice-details">
  <div class="card-header"><h2>{{ __('reports.details') }}</h2></div>
  <div class="card-body">
    @if($invoices->count())
    <div class="table-responsive table-mobile-cards">
      <table class="table">
        <thead><tr>
          <th scope="col">{{ __('messages.number') }}</th>
          <th scope="col">{{ __('messages.invoice_date') }}</th>
          <th scope="col">{{ __('messages.customer') }}</th>
          <th scope="col">{{ __('reports.creator') }}</th>
          <th scope="col">{{ __('messages.status') }}</th>
          <th scope="col" class="num">{{ __('messages.total') }}</th>
          <th scope="col" class="num">{{ __('messages.outstanding_balance') }}</th>
        </tr></thead>
        <tbody>
          @foreach($invoices as $invoice)
          <tr>
            <td data-label="{{ __('messages.number') }}"><a href="{{ route('invoices.show', $invoice) }}"><bdi>{{ $invoice->invoice_number }}</bdi></a></td>
            <td data-label="{{ __('messages.invoice_date') }}"><bdi>{{ $invoice->invoice_date->format('Y-m-d') }}</bdi></td>
            <td data-label="{{ __('messages.customer') }}">{{ $invoice->customer_name }}</td>
            <td data-label="{{ __('reports.creator') }}">{{ $invoice->creator?->username ?? __('reports.unknown') }}</td>
            <td data-label="{{ __('messages.status') }}"><span class="badge badge-{{ $invoice->status }}">{{ __('messages.' . $invoice->status) }}</span></td>
            <td data-label="{{ __('messages.total') }}" class="num"><bdi>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</bdi></td>
            <td data-label="{{ __('messages.outstanding_balance') }}" class="num"><bdi>{{ $invoice->currency }} {{ number_format($invoice->status === 'pending' ? $invoice->outstandingAmount() : 0, 2) }}</bdi></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $invoices->fragment('invoice-details')->links('pagination.default') }}
    @else
    <p class="empty-state">{{ __('reports.no_matches') }}</p>
    @endif
  </div>
</div>
@endsection
