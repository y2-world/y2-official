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
            margin: 0 0 4px;
            padding: 0;
            line-height: 1;
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
            .breadcrumb-nav {
                position: absolute;
                top: 8px;
                left: 10px;
                right: 10px;
                margin-bottom: 0;
            }
            .breadcrumb-item-link { font-size: 0.7rem; }
            .breadcrumb-sep { font-size: 0.7rem; }
            .breadcrumb-item-current,
            .breadcrumb-nav > *:nth-last-child(2) { display: none; }
        }
    </style>
@endif
