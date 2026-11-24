@if ($paginator->hasPages())
  <div class="admin-pagination">
    <div class="admin-pagination-meta">
      Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </div>
    <div class="admin-pagination-links">
      @if ($paginator->onFirstPage())
        <span class="page-btn disabled">Prev</span>
      @else
        <a class="page-btn" href="{{ $paginator->previousPageUrl() }}">Prev</a>
      @endif

      @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
        @if ($page == $paginator->currentPage())
          <span class="page-btn active">{{ $page }}</span>
        @else
          <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a class="page-btn" href="{{ $paginator->nextPageUrl() }}">Next</a>
      @else
        <span class="page-btn disabled">Next</span>
      @endif
    </div>
  </div>
@endif
