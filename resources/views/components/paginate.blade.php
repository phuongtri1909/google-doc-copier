@if ($paginator->hasPages())
    <div class="custom-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-item pagination-disabled">
                <i class="fas fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-item pagination-arrow">
                <i class="fas fa-chevron-left"></i>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="pagination-item pagination-dots">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @php
                    $totalPages = count($element);
                    $current = $paginator->currentPage();
                    $showDots = true;
                @endphp

                @foreach ($element as $page => $url)
                    @if ($page == $current || $page <= 2 || $page > $totalPages - 2 || abs($current - $page) <= 1)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-item pagination-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-item">{{ $page }}</a>
                        @endif
                        @php $showDots = true; @endphp
                    @elseif($showDots)
                        <span class="pagination-item pagination-dots">...</span>
                        @php $showDots = false; @endphp
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item pagination-arrow">
                <i class="fas fa-chevron-right"></i>
            </a>
        @else
            <span class="pagination-item pagination-disabled">
                <i class="fas fa-chevron-right"></i>
            </span>
        @endif
    </div>
@endif

@once
<style>
    .custom-pagination {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
        margin-top: 10px;
    }
    
    .pagination-item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        border-radius: 6px;
        background-color: white;
        border: 1px solid #e4e7ea;
        color: #555;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .pagination-item:hover:not(.pagination-active):not(.pagination-disabled):not(.pagination-dots) {
        background-color: #f8f9fa;
        border-color: #ddd;
        color: var(--primary-color);
    }
    
    .pagination-active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        box-shadow: 0 2px 5px rgba(91, 60, 37, 0.2);
    }
    
    .pagination-disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .pagination-dots {
        border: none;
        background: transparent;
    }
    
    .pagination-arrow {
        background-color: #f8f9fa;
    }
    
    .pagination-arrow:hover {
        background-color: #e9ecef;
    }
    
    @media (max-width: 576px) {
        .pagination-item {
            min-width: 32px;
            height: 32px;
            font-size: 13px;
        }
    }
</style>
@endonce