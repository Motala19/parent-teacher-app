@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-bold text-ink">{{ $paginator->firstItem() }}</span>
            to <span class="font-bold text-ink">{{ $paginator->lastItem() }}</span>
            of <span class="font-bold text-ink">{{ $paginator->total() }}</span>
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="pg pg-off" aria-disabled="true">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pg" aria-label="Previous">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pg pg-off">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pg pg-on" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pg">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pg" aria-label="Next">&rsaquo;</a>
            @else
                <span class="pg pg-off" aria-disabled="true">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif