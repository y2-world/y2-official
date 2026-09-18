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
            <p class="database-subtitle" style="text-align: center;">参加したセットリストパターンを選択</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if ($tourSetlists->isEmpty())
                    <p>このツアーにはまだセットリストが登録されていません。</p>
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
                            $songCount = count($setlist->setlist ?? []) + count($setlist->encore ?? []);
                            if ($setlist->subtitle) {
                                $labelRendered = renderSubtitleWithGreyedVenues($setlist->subtitle);
                                $label = implode('<br>', $labelRendered['lines']);
                                $labelFontSize = $labelRendered['font_size'];
                            } else {
                                $label = null;
                                $labelFontSize = null;
                            }
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
                                        <span class="select-card-title" @if ($labelFontSize) style="font-size: {{ $labelFontSize }};" @endif>{!! $label !!}</span>
                                    @endif
                                    <span class="select-card-meta">{{ $songCount }}曲</span>
                                </span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="setlist-pick-body" @if ($tourSetlists->count() !== 1) hidden @endif>
                                @include('db_concerts._setlist_rows', ['tourSetlists' => collect([$setlist]), 'songs' => $songs])
                                <div class="setlist-pick-confirm">
                                    <a href="{{ route('mypage.attendances.form', $kind . '-' . $setlist->id) }}" class="btn-pill">
                                        選択
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if ($kind === 'user')
                    <div style="margin-top: 24px; text-align: center;">
                        <form method="POST" action="{{ route('mypage.attendances.setlists.new_pattern', $tourId) }}">
                            @csrf
                            <button type="submit" class="mypage-add-button" title="新しいセットリストパターンを追加" style="border: none;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

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
