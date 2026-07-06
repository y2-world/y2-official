@if (!empty($breadcrumbs))
    <nav style="text-align: left; margin-bottom: 12px;">
        @foreach ($breadcrumbs as $i => $crumb)
            @if (!$loop->last)
                <a href="{{ $crumb['url'] }}" style="color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.8rem;" class="breadcrumb-item-link">{{ $crumb['label'] }}</a>
                <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin: 0 4px;">›</span>
            @else
                <span style="color: rgba(255,255,255,0.95); font-size: 0.8rem;" class="breadcrumb-item-current">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
    <style>
        @media (max-width: 767px) {
            .breadcrumb-item-link, .breadcrumb-item-current {
                font-size: 0.7rem !important;
            }
        }
    </style>
@endif
