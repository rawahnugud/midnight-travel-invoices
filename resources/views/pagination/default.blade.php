@if ($paginator->hasPages())
<nav class="pagination-nav" role="navigation" aria-label="{{ __('pagination.label') }}">
  <ul class="pagination">
    @if ($paginator->onFirstPage())
    <li class="pagination-item disabled" aria-disabled="true"><span class="pagination-link pagination-previous">{{ __('pagination.previous') }}</span></li>
    @else
    <li class="pagination-item"><a class="pagination-link pagination-previous" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a></li>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
      <li class="pagination-gap" aria-hidden="true">…</li>
      @else
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
          <li class="pagination-item active" aria-current="page"><span class="pagination-link">{{ $page }}</span></li>
          @else
          <li class="pagination-item"><a class="pagination-link" href="{{ $url }}">{{ $page }}</a></li>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
    <li class="pagination-item"><a class="pagination-link pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a></li>
    @else
    <li class="pagination-item disabled" aria-disabled="true"><span class="pagination-link pagination-next">{{ __('pagination.next') }}</span></li>
    @endif
  </ul>
</nav>
@endif
