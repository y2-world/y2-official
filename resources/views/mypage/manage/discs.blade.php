@extends('layouts.app')

@php $kindLabel = $kind === 'album' ? 'アルバム' : 'シングル'; @endphp
@section('title', $artist->name . ' ' . $kindLabel . 'を管理 - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' ' . $kindLabel . 'を管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => $kindLabel . 'を管理'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $kindLabel }}を管理</h1>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                {{-- 発売日の新しい順。「＋」で追加 --}}
                @foreach ([['kind' => $kind, 'items' => $discs, 'empty' => 'まだ' . $kindLabel . 'がありません。']] as $section)
                    <div style="text-align: center; margin-bottom: 24px;">
                        <a href="{{ route('mypage.manage.discs.create', ['artistId' => $artist->id, 'kind' => $section['kind']]) }}" class="mypage-add-button" title="{{ $kindLabel }}を追加">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    <div class="select-card-list disc-list">
                        @foreach ($section['items'] as $disc)
                            @php
                                $badges = array_filter([
                                    $section['kind'] === 'single' && $disc->ep ? 'EP' : null,
                                    $section['kind'] === 'album' && $disc->mini ? 'ミニアルバム' : null,
                                    $section['kind'] === 'album' && $disc->best ? 'ベストアルバム' : null,
                                ]);
                            @endphp
                            <div class="manage-artist-row">
                                <a href="{{ route('mypage.manage.discs.edit', [$artist->id, $section['kind'], $disc->id]) }}" class="select-card">
                                    <span class="select-card-body">
                                        <span class="select-card-title">{{ $disc->title }}</span>
                                        <span class="select-card-meta">
                                            {{ $disc->date?->format('Y.m.d') }} ・ {{ count($disc->tracklist ?? []) }}曲
                                            @if ($badges) ・ {{ implode(' / ', $badges) }} @endif
                                        </span>
                                    </span>
                                    <button type="button" class="manage-artist-delete" data-delete-url="{{ route('mypage.manage.discs.destroy', [$artist->id, $section['kind'], $disc->id]) }}" data-confirm="「{{ $disc->title }}」を削除しますか？（収録曲の曲そのものは残ります）" title="削除">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <p class="disc-empty" style="text-align: center; color: #999; margin: 0;" @if ($section['items']->isNotEmpty()) hidden @endif>{{ $section['empty'] }}</p>
                @endforeach
            </div>
        </div>
    </div>

    <script>
    (function () {
        document.querySelectorAll('.disc-list .manage-artist-delete').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!confirm(btn.dataset.confirm)) return;
                const row = btn.closest('.manage-artist-row');
                const list = row.closest('.disc-list');
                fetch(btn.dataset.deleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then((res) => res.json())
                    .then((data) => {
                        row.remove();
                        showAppToast(data.message);
                        if (!list.querySelector('.manage-artist-row')) {
                            list.nextElementSibling.hidden = false;
                        }
                    });
            });
        });
    })();
    </script>
@endsection
