@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tour->title . ' セットリストパターンを選択')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @php
                $artistRef = $kind . '-' . ($kind === 'official' ? $tour->artist_id : $tour->user_artist_id);
            @endphp
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $tour->artist->name, 'url' => route('mypage.attendances.tours', $artistRef)],
                ['label' => $tour->title],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <h1 class="database-title" style="text-align: center; overflow-wrap: break-word; word-break: break-word;">{{ $tour->title }}</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $attachTarget ? '参加予定にセットリストを追加：パターンを選択' : '参加したセットリストパターンを選択' }}</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- 開催期間が終わっていないツアーは、パターンを選ばずに「参加予定」として登録できる --}}
                @if ($canPlan)
                    <a href="{{ route('mypage.attendances.planned', $tourId) }}" class="select-card" style="margin-bottom: 24px;">
                        <span class="select-card-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                        <span class="select-card-body">
                            <span class="select-card-title">参加予定</span>
                            <span class="select-card-meta">セットリストはライブ後に追加</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>
                @endif

                @if ($tourSetlists->isEmpty())
                    {{-- 開催前のツアーにセットリストが無いのは当たり前なので、「まだ登録されていません」は出さない（参加予定だけ選べる） --}}
                    @if (!$tour->date1 || \Carbon\Carbon::parse($tour->date1)->lte(now()->startOfDay()))
                        <p>このツアーにはまだセットリストが登録されていません。</p>
                    @endif
                @else
                    @php
                        $groupTitlesByRowAndOrderNo = $kind === 'official'
                            ? \App\Models\DbSetlistRow::where('tour_id', $tour->id)
                                ->get()
                                ->groupBy('row')
                                ->map(fn($rows) => $rows->pluck('title', 'order_no'))
                            : collect();
                    @endphp
                    @foreach ($tourSetlists as $setlist)
                        @php
                            $songCount = countActualSongs($setlist->setlist ?? []) + countActualSongs($setlist->encore ?? []);
                            // 公演日（「5.30 大阪城ホール」1日1行）とタイトル
                            $labelLines = \App\Support\PatternTitle::htmlLines($setlist);
                            // 公演日の行はそれぞれ1行（display: block）なので、改行を足さずにつなぐ。タイトルの行（あれば）は改行で
                            $label = $labelLines ? preg_replace('/(<\/span>)<br>(?=<span class="setlist-schedule-date-line")/', '$1', implode('<br>', $labelLines)) : null;
                            $groupTitle = $groupTitlesByRowAndOrderNo[$setlist->row][$setlist->order_no] ?? null;
                        @endphp
                        @if ($groupTitle)
                            <h4 class="setlist-pick-group-title" style="text-align: center; font-size: 1rem; margin: {{ $loop->first ? '0 0 10px' : '24px 0 10px' }};">{{ $groupTitle }}</h4>
                        @endif
                        <div class="pick-card">
                            <button type="button" class="pick-card-header setlist-pick-expand" aria-expanded="{{ $tourSetlists->count() === 1 ? 'true' : 'false' }}" onclick="toggleSetlistPick(this)" style="width: 100%; background: none; border: none; cursor: pointer; text-align: left; font: inherit; color: inherit;">
                                <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                                <span class="select-card-body">
                                    @if ($label)
                                        <span class="select-card-title">{!! $label !!}</span>
                                    @endif
                                    <span class="select-card-meta">{{ $songCount }}曲</span>
                                </span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="setlist-pick-body" @if ($tourSetlists->count() !== 1) hidden @endif>
                                @include('db_concerts._setlist_rows', ['tourSetlists' => collect([$setlist]), 'songs' => $songs, 'noSongLinks' => true, 'noGroupTitles' => true])
                                <div class="setlist-pick-confirm">
                                    <a href="{{ route('mypage.attendances.daily_songs', $kind . '-' . $setlist->id) }}" class="btn-pill">
                                        選択
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <style>
    /* この画面だけ、カードの見出しの公演日（「5.30 大阪城ホール」）を黒にし、カードの見出しに近い大きさにする
       （ほかの画面は PatternTitle の、見出しの半分の大きさのグレー） */
    .select-card-title .setlist-schedule-date-line {
        color: #333 !important;
        font-size: 0.8rem !important;
    }
    </style>

    <script>
    function toggleSetlistPick(button) {
        const card = button.closest('.pick-card');
        const body = card.querySelector('.setlist-pick-body');
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        body.hidden = isExpanded;
    }
    </script>
@endsection
