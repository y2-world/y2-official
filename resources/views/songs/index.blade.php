@extends('layouts.app')
@section('title', 'Yuki Official - Songs')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">Songs</h1>
            <p class="database-subtitle">すべての楽曲コレクション</p>

            <div class="year-navigation" style="display: flex; align-items: center; gap: 10px;">
                {{-- 虫眼鏡アイコン（SP表示のみ・ドロップダウンの左端） --}}
                <button type="button" id="spSearchButtonSongs" class="sp" onclick="var form = document.getElementById('spSearchFormSongs'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Discography</option>
                    <option value="{{ url('/database/singles') }}">Singles</option>
                    <option value="{{ url('/database/albums') }}">Albums</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ url('/database/live') }}">All</option>
                    <option value="{{ url('/database/live?type=1') }}">Tours</option>
                    <option value="{{ url('/database/live?type=2') }}">Events</option>
                    <option value="{{ url('/database/live?type=3') }}">ap bank fes</option>
                    <option value="{{ url('/database/live?type=4') }}">Solo</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('/database/years', $bio->year) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSongs" style="margin-top: 20px; display: none;">
                <div>
                    @livewire('database-song-search')
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    @livewire('database-song-search')
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                    <th class="mobile">シングル / アルバム</th>
                    <th class="pc">リリース日</th>
                </tr>
            </thead>
            <tbody id="songs-container">
                @include('songs._list', ['songs' => $songs])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $songs->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
@endsection
