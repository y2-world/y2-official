@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tour->title . ' セットリストパターンを選択')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'ライブの参加記録を追加', 'url' => route('mypage.attendances.create')],
                ['label' => $tour->artist->name, 'url' => route('mypage.attendances.tours', $tour->artist_id)],
                ['label' => $tour->title],
            ]])
            <h1 class="database-title" style="text-align: center;">{{ $tour->title }}</h1>
            <p class="database-subtitle" style="text-align: center;">参加したセットリストパターンを選択してください</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($tourSetlists->isEmpty())
                    <p>このツアーにはまだセットリストが登録されていません。</p>
                @else
                    @foreach ($tourSetlists as $setlist)
                        @php
                            $songCount = count($setlist->setlist ?? []) + count($setlist->encore ?? []);
                            $label = $setlist->subtitle ?: 'パターン ' . $setlist->order_no;
                        @endphp
                        <div class="setlist-pick-card">
                            <div class="setlist-pick-header">
                                <button type="button" class="setlist-pick-toggle" aria-expanded="false" onclick="toggleSetlistPick(this)">
                                    <span class="select-card-icon"><i class="fa-solid fa-check"></i></span>
                                    <span class="select-card-body">
                                        <span class="select-card-title">{{ $label }}</span>
                                        <span class="select-card-meta">{{ $songCount }}曲</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                                </button>
                                <span class="setlist-pick-select">
                                    <a href="{{ route('mypage.attendances.form', $setlist->id) }}" class="btn btn-outline-dark btn-sm">
                                        このパターンを選択
                                    </a>
                                </span>
                            </div>
                            <div class="setlist-pick-body" hidden>
                                @include('db_concerts._setlist_rows', ['tourSetlists' => collect([$setlist]), 'songs' => $songs])
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <script>
    function toggleSetlistPick(button) {
        const header = button.closest('.setlist-pick-header');
        const body = header.nextElementSibling;
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        body.hidden = isExpanded;
    }
    </script>
@endsection
