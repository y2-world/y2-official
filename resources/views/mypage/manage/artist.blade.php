@extends('layouts.app')

@section('title', $artist->name . ' - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' - Manage My Artists & Setlists - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page has-hero-edit">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">Manage My Artists & Setlists</p>
            <div style="display: flex; align-items: center; justify-content: center; margin-top: 12px;">
                <h1 class="database-title" style="text-align: center; margin-bottom: 0;">
                    <span class="manage-artist-name" data-title="{{ $artist->name }}">{{ $artist->name }}</span>
                </h1>
                <input type="text" class="manage-artist-name-input form-control" value="{{ $artist->name }}" hidden style="max-width: 240px; font-size: 1rem; padding: 6px 12px;">
            </div>
            {{-- 編集中だけ、保存せずに閉じる×を入力欄の下の真ん中に（参加記録の画面と同じ） --}}
            <div id="artistNameEditClose" hidden style="height: 26px; margin-top: 14px; justify-content: center; align-items: center;">
                <button type="button" style="background: none; border: none; color: white; cursor: pointer; padding: 0 4px; line-height: 1;" title="閉じる">
                    <span class="close-x-thin" style="font-size: 18px;"></span>
                </button>
            </div>
            {{-- アーティスト名の編集。押すとその場で書き換えて「保存」になる --}}
            <button type="button" id="artistNameEditBtn" class="hero-edit-btn" data-update-url="{{ route('mypage.manage.artists.update', $artist->id) }}">編集</button>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="select-card-list">
                    <a href="{{ route('mypage.manage.songs', $artist->id) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-music" style="margin-right: 10px;"></i> 曲を管理</span>
                            <span class="select-card-meta">{{ $songsCount }}曲</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>

                    <a href="{{ route('mypage.manage.discs', [$artist->id, 'single']) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-compact-disc" style="margin-right: 10px;"></i> シングルを管理</span>
                            <span class="select-card-meta">{{ $singlesCount }}シングル</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>

                    <a href="{{ route('mypage.manage.discs', [$artist->id, 'album']) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-record-vinyl" style="margin-right: 10px;"></i> アルバムを管理</span>
                            <span class="select-card-meta">{{ $albumsCount }}アルバム</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>

                    <a href="{{ route('mypage.manage.concerts', $artist->id) }}" class="select-card">
                        <span class="select-card-body">
                            <span class="select-card-title"><i class="fa-solid fa-calendar-check" style="margin-right: 10px;"></i> ツアーを管理</span>
                            <span class="select-card-meta">{{ $concertsCount }}ツアー</span>
                        </span>
                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const nameSpan = document.querySelector('.manage-artist-name');
        const nameInput = document.querySelector('.manage-artist-name-input');
        const editBtn = document.getElementById('artistNameEditBtn');
        if (!nameSpan || !nameInput || !editBtn) return;

        const updateUrl = editBtn.dataset.updateUrl;
        let isEditing = false;
        const closeRow = document.getElementById('artistNameEditClose');
        // 編集中は×の行を出し、その下の余白を詰める（参加記録の画面と同じ）
        const setEditingLayout = (editing) => {
            closeRow.hidden = !editing;
            closeRow.style.display = editing ? 'flex' : '';
            document.querySelector('.database-hero').classList.toggle('is-hero-editing', editing);
        };

        const startEdit = () => {
            isEditing = true;
            nameSpan.hidden = true;
            nameInput.hidden = false;
            editBtn.textContent = '保存';
            setEditingLayout(true);
            nameInput.value = nameSpan.dataset.title;
            nameInput.focus();
            nameInput.setSelectionRange(nameInput.value.length, nameInput.value.length);
        };

        const commitEdit = () => {
            isEditing = false;
            const newName = nameInput.value.trim();
            nameInput.hidden = true;
            nameSpan.hidden = false;
            editBtn.textContent = '編集';
            setEditingLayout(false);

            if (newName === '' || newName === nameSpan.dataset.title) return;

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name: newName }),
            })
                .then((res) => res.json())
                .then((data) => {
                    document.title = document.title.replace(nameSpan.dataset.title, data.name);
                    nameSpan.textContent = data.name;
                    nameSpan.dataset.title = data.name;
                    showAppToast(data.message);
                });
        };

        editBtn.addEventListener('click', () => {
            if (isEditing) {
                commitEdit();
            } else {
                startEdit();
            }
        });
        // ×は保存せずに閉じる
        closeRow.querySelector('button').addEventListener('click', () => {
            nameInput.value = nameSpan.dataset.title;
            commitEdit();
        });
        nameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); commitEdit(); }
            if (e.key === 'Escape') { nameInput.value = nameSpan.dataset.title; commitEdit(); }
        });
    })();
    </script>
@endsection
