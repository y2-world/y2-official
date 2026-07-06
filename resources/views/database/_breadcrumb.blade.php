@if (!empty($breadcrumbs))
    <nav class="breadcrumb-nav">
        @foreach ($breadcrumbs as $i => $crumb)
            @if (!$loop->last)
                <a href="{{ $crumb['url'] }}" class="breadcrumb-item-link">{{ $crumb['label'] }}</a>
                <span class="breadcrumb-sep">›</span>
            @else
                <span class="breadcrumb-item-current">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
    <style>
        .breadcrumb-nav {
            position: absolute;
            top: 62px;
            left: 15px;
            right: 15px;
        }
        .breadcrumb-item-link {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.8rem;
        }
        .breadcrumb-sep {
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            margin: 0 4px;
        }
        .breadcrumb-item-current {
            color: rgba(255,255,255,0.95);
            font-size: 0.8rem;
        }
        @media (max-width: 767px) {
            .breadcrumb-nav { top: 58px; }
            .breadcrumb-item-link { font-size: 0.7rem; }
            .breadcrumb-sep { font-size: 0.7rem; }
            .breadcrumb-item-current,
            .breadcrumb-nav > *:nth-last-child(2) { display: none; }
        }
    </style>
@endif
