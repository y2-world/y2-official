@extends('layouts.app')

@section('title', $artist->name . ' - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' - Manage My Artists & Setlists - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">Manage My Artists & Setlists</p>
            <div style="display: flex; align-items: center; justify-content: center; gap: 16px;">
                <span style="width: 1rem; flex-shrink: 0;"></span>
                <h1 class="database-title" style="text-align: center; margin-bottom: 0;">
                    <span class="manage-artist-name" data-title="{{ $artist->name }}">{{ $artist->name }}</span>
                </h1>
                <input type="text" class="manage-artist-name-input form-control" value="{{ $artist->name }}" hidden style="max-width: 420px; width: 100%; font-size: 1rem; padding: 10px 14px;">
                <button type="button" class="manage-edit-btn" data-update-url="{{ route('mypage.manage.artists.update', $artist->id) }}" title="編集" style="color: white; font-size: 1rem; flex-shrink: 0; width: 1rem;">
                    <i class="fa-solid fa-pen"></i>
                    <i class="fa-solid fa-check" hidden></i>
                </button>
            </div>
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
        const editBtn = document.querySelector('.manage-edit-btn');
        if (!nameSpan || !nameInput || !editBtn) return;

        const penIcon = editBtn.querySelector('.fa-pen');
        const checkIcon = editBtn.querySelector('.fa-check');
        const updateUrl = editBtn.dataset.updateUrl;
        let isEditing = false;

        const startEdit = () => {
            isEditing = true;
            nameSpan.hidden = true;
            nameInput.hidden = false;
            penIcon.hidden = true;
            checkIcon.hidden = false;
            nameInput.value = nameSpan.dataset.title;
            nameInput.focus();
            nameInput.setSelectionRange(nameInput.value.length, nameInput.value.length);
        };

        const commitEdit = () => {
            isEditing = false;
            const newName = nameInput.value.trim();
            nameInput.hidden = true;
            nameSpan.hidden = false;
            penIcon.hidden = false;
            checkIcon.hidden = true;

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
        nameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); commitEdit(); }
            if (e.key === 'Escape') { nameInput.value = nameSpan.dataset.title; commitEdit(); }
        });
    })();
    </script>
@endsection
