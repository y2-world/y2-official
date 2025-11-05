@extends('layouts.app')
@section('title', 'Yuki Official - Database')
@section('content')
    <div class="database-hero">
        <div class="container">
            <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h1 class="database-title" style="margin-bottom: 0; text-align: center;">Mr.Children Database</h1>
                {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                <button type="button" id="spSearchButtonDatabase" class="sp sp-search-button" onclick="var form = document.getElementById('spSearchFormDatabase'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; align-items: center; justify-content: center; margin-bottom: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormDatabase" style="margin-top: 40px; display: none;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" id="searchInputSp" class="database-search-input typeahead" placeholder="楽曲を検索..." required>
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    </div>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 40px;">
                <form action="" method="GET">
                    <div class="search-wrapper">
                        <input type="text" id="searchInput" class="database-search-input typeahead" placeholder="楽曲を検索..." required>
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container database-content">
        <div class="row">
            <!-- Live Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-guitar"></i>
                    </div>
                    <h3 class="card-title">Live</h3>
                    <p class="card-description">すべてのツアー、イベント、公演情報</p>
                    <div class="card-links">
                        <a href="{{ url('/database/live') }}" class="database-link">
                            <span>All</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=1') }}" class="database-link">
                            <span>Tours</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=2') }}" class="database-link">
                            <span>Events</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=3') }}" class="database-link">
                            <span>ap bank fes</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=4') }}" class="database-link">
                            <span>Solo</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Discography Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <h3 class="card-title">Discography</h3>
                    <p class="card-description">すべての楽曲、シングル、アルバムを閲覧</p>
                    <div class="card-links">
                        <a href="{{ url('/database/songs') }}" class="database-link">
                            <span>Songs</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/singles') }}" class="database-link">
                            <span>Singles</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/albums') }}" class="database-link">
                            <span>Albums</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Biography Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <h3 class="card-title">Biography</h3>
                    <p class="card-description">年代ごとの歴史を探索</p>
                    <div class="card-links card-links-grid">
                        @foreach ($bios as $bio)
                            <a href="{{ url('/database/years', $bio->year) }}" class="database-link-year">
                                {{ $bio->year }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
<script>
    // PC表示とSP表示の両方でTypeaheadを初期化
    $(document).ready(function(){
        // 検索機能を初期化する関数
        function initTypeahead(inputId) {
            const $input = $(inputId);
            if ($input.length && !$input.data('typeahead')) {
                var songs = new Bloodhound({
                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    remote: {
                        url: '/find?q=%QUERY',
                        wildcard: '%QUERY'
                    },
                    limit: 20
                });

                $input.typeahead({
                    minLength: 1,
                    highlight: true,
                    hint: true,
                    classNames: {
                        menu: 'tt-menu-modern',
                        suggestion: 'tt-suggestion-modern',
                        cursor: 'tt-cursor-modern'
                    }
                },
                {
                    name: 'songs',
                    display: 'title',
                    source: songs,
                    limit: 20,
                    templates: {
                        empty: '<div class="tt-empty">該当する曲が見つかりません</div>',
                        suggestion: function(data) {
                            return '<div class="tt-suggestion-content"><span>' + data.title + '</span></div>';
                        }
                    }
                }).on('typeahead:selected', function(event, data) {
                    // 選択された曲の詳細ページにリダイレクト
                    window.location.href = '/database/songs/' + data.id;
                });
            }
        }

        // PC表示の検索フォームはsearch.jsが既に初期化しているので、重複初期化を避ける
        // search.jsが初期化に失敗した場合のみ初期化を試みる
        setTimeout(function() {
            const $searchInput = $('#searchInput');
            if ($searchInput.length && !$searchInput.data('typeahead')) {
                initTypeahead('#searchInput');
            }
        }, 500);

        // SP表示の検索フォームが表示された時にTypeaheadを初期化
        const spSearchForm = document.getElementById('spSearchFormDatabase');
        if (spSearchForm) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        if (spSearchForm.style.display === 'block') {
                            // 少し待ってから初期化（DOMが完全に表示されてから）
                            setTimeout(function() {
                                initTypeahead('#searchInputSp');
                            }, 100);
                        }
                    }
                });
            });
            observer.observe(spSearchForm, {
                attributes: true,
                attributeFilter: ['style']
            });
        }
    });
</script>
@endsection
