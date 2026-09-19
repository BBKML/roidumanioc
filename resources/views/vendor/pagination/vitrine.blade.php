@if ($paginator->hasPages())
    <nav class="vt-pagination" role="navigation" aria-label="Pagination">
        <div class="vt-pagination-info">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </div>

        <div class="vt-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="vt-page is-disabled" aria-disabled="true">‹</span>
            @else
                <button type="button" class="vt-page" wire:click="previousPage" wire:loading.attr="disabled">‹</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="vt-page is-dots">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="vt-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <button type="button" class="vt-page" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" class="vt-page" wire:click="nextPage" wire:loading.attr="disabled">›</button>
            @else
                <span class="vt-page is-disabled" aria-disabled="true">›</span>
            @endif
        </div>
    </nav>
@endif
