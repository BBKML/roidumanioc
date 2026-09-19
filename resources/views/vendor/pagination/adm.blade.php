@if ($paginator->hasPages())
    <nav class="adm-pagination" role="navigation" aria-label="Pagination">
        <div class="adm-pagination-info">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </div>

        <div class="adm-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="adm-page is-disabled" aria-disabled="true">‹</span>
            @else
                <button type="button" class="adm-page" wire:click="previousPage" wire:loading.attr="disabled">‹</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="adm-page is-dots">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="adm-page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <button type="button" class="adm-page" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" class="adm-page" wire:click="nextPage" wire:loading.attr="disabled">›</button>
            @else
                <span class="adm-page is-disabled" aria-disabled="true">›</span>
            @endif
        </div>
    </nav>
@endif
