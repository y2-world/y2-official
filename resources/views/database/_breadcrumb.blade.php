@if (!empty($breadcrumbs))
    <nav class="db-breadcrumb" style="text-align: left; margin-bottom: 12px;">
        @foreach ($breadcrumbs as $i => $crumb)
            @if (!$loop->last)
                @if (!$loop->first)
                    <span class="bc-sep" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin: 0 4px;">›</span>
                @endif
                <a href="{{ $crumb['url'] }}" style="color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.7rem;">{{ $crumb['label'] }}</a>
            @else
                <span class="bc-sep" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin: 0 4px;">›</span>
                <span class="bc-current" style="color: rgba(255,255,255,0.95); font-size: 0.7rem;">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
    <style>
        .db-breadcrumb { display: flex; align-items: center; flex-wrap: nowrap; overflow: hidden; }
        .db-breadcrumb a, .bc-sep { flex-shrink: 0; }
        .bc-current { flex-shrink: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 768px) {
            .db-breadcrumb { display: none; }
        }
    </style>
@endif
