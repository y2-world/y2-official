@if (!empty($breadcrumbs))
    <nav style="text-align: left; margin-bottom: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        @foreach ($breadcrumbs as $i => $crumb)
            @if (!$loop->last)
                @if (!$loop->first)
                    <span style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin: 0 4px;">›</span>
                @endif
                <a href="{{ $crumb['url'] }}" style="color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.7rem;" class="breadcrumb-item-link">{{ $crumb['label'] }}</a>
            @else
                <span style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin: 0 4px;">›</span>
                <span style="color: rgba(255,255,255,0.95); font-size: 0.7rem;" class="breadcrumb-item-current">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
    <style>
        @media (max-width: 767px) {
            .breadcrumb-item-link, .breadcrumb-item-current {
                font-size: 0.5rem !important;
            }
        }
    </style>
@endif
