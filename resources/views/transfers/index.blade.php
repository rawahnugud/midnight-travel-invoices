@extends('layout')
@section('title', __('letters.menu'))
@section('content')
<div class="card">
  <div class="card-header"><h2>{{ __('letters.menu') }}</h2><a class="btn btn-primary" href="{{ route('letters.create') }}">{{ __('letters.new') }}</a></div>
  <div class="card-body">
    @forelse($letters as $letter)
      <div class="card-header">
        <div><strong>#{{ $letter->id }} · {{ $letter->details['bank'] ?? __('letters.new') }}</strong>
          <div>{{ $letter->details['date'] }} · {{ strtoupper($letter->details['language']) }}</div>
        </div>
        <div class="header-actions">
          <a class="btn btn-outline" href="{{ route('letters.edit', $letter) }}">{{ __('letters.edit') }}</a>
          <a class="btn btn-outline" href="{{ route('letters.copy', $letter) }}">{{ __('letters.copy') }}</a>
          <a class="btn btn-outline" href="{{ route('letters.print', $letter) }}">{{ __('messages.print') }}</a>
        </div>
      </div>
    @empty
      <p>{{ __('letters.no_drafts') }}</p>
    @endforelse
    {{ $letters->links('pagination.default') }}
  </div>
</div>
@endsection
