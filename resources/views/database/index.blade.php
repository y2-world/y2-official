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
                    @livewire('database-song-search')
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 40px;">
                @livewire('database-song-search')
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
@endsection
