@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name)

@section('og_title', $artist->name . ' - Yuki Official')
@section('og_description', 'All setlists featuring ' . $artist->name)
@section('og_type', 'profile')

@section('content')
    <?php $artist_id = $artist->id; ?>
    <div class="database-hero database-hero--nav">
        <div class="container">
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $artist->name }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                    <button type="button" id="spSearchButtonArtist" class="sp" onclick="var form = document.getElementById('spSearchFormArtist'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                    </button>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Artists</option>
                        @foreach ($artists as $artistItem)
                            <option value="{{ url('/setlists/artists', $artistItem->id) }}" {{ $artistItem->id == $artist->id ? 'selected' : '' }}>{{ $artistItem->name }}</option>
                        @endforeach
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Years</option>
                        @foreach ($years as $year)
                            <option value="{{ url('/setlists/years', $year->year) }}">{{ $year->year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0; display: none;">
                    @livewire('song-search', ['artistId' => $artist->id])
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormArtist" style="margin-top: 15px; display: none;">
                @livewire('song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if($setlists->isEmpty())
            <p style="text-align: center; color: #999; margin-top: 40px;">セットリストがありません。</p>
        @endif
        @if(!$setlists->isEmpty())
            <table class="table table-striped count">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">開催日</th>
                        <th class="mobile">タイトル</th>
                        <th class="pc">会場</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($setlists as $setlist)
                        <tr>
                            <td></td>
                            <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                            <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
                            <td class="pc"><a
                                    href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
        <br>
    </div>
@endsection

@section('page-script')
@endsection
