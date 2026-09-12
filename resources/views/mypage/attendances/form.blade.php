@extends('layouts.app')
@section('title', 'Yuki Official - 参加日・会場を入力')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $dbSetlist->tour->artist->name, 'url' => route('mypage.attendances.tours', $dbSetlist->tour->artist_id)],
                ['label' => $dbSetlist->tour->title, 'url' => route('mypage.attendances.setlists', $dbSetlist->tour_id)],
                ['label' => '参加日・会場を入力'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $dbSetlist->tour->artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $dbSetlist->tour->title }}</h1>
            @if ($dbSetlist->subtitle)
                <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $dbSetlist->subtitle }}</p>
            @endif
            <p class="database-subtitle" style="text-align: center;">参加日・会場を入力</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($scheduleOptions) === 1)
                    <form method="POST" action="{{ route('mypage.attendances.store') }}">
                        @csrf
                        <input type="hidden" name="db_setlist_id" value="{{ $dbSetlist->id }}">
                        <input type="hidden" name="attended_date" value="{{ $scheduleOptions[0]['date'] }}">
                        <input type="hidden" name="venue" value="{{ $scheduleOptions[0]['venue'] }}">
                        <div class="mb-3">
                            <label class="form-label">参加日</label>
                            <div class="form-control" style="background-color: #f8f9fa;">{{ \Carbon\Carbon::parse($scheduleOptions[0]['date'])->format('Y.m.d') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">会場</label>
                            <div class="form-control" style="background-color: #f8f9fa;">{{ $scheduleOptions[0]['venue'] }}</div>
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">登録</button>
                    </form>
                @elseif (count($scheduleOptions) > 1)
                    <form method="POST" action="{{ route('mypage.attendances.store') }}">
                        @csrf
                        <input type="hidden" name="db_setlist_id" value="{{ $dbSetlist->id }}">
                        <input type="hidden" id="attended_date" name="attended_date" value="{{ old('attended_date') }}">
                        <input type="hidden" id="venue" name="venue" value="{{ old('venue') }}">
                        <div class="mb-3">
                            <label for="schedule_select" class="form-label">参加日・会場を選択</label>
                            <select class="form-control" id="schedule_select" required>
                                <option value="" selected disabled>-- 選択してください --</option>
                                @foreach ($scheduleOptions as $index => $option)
                                    <option value="{{ $index }}" data-date="{{ $option['date'] }}" data-venue="{{ $option['venue'] }}">
                                        {{ \Carbon\Carbon::parse($option['date'])->format('Y.m.d') }} {{ $option['venue'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">登録</button>
                    </form>
                    <script>
                    document.getElementById('schedule_select').addEventListener('change', function (e) {
                        const selected = e.target.options[e.target.selectedIndex];
                        document.getElementById('attended_date').value = selected.getAttribute('data-date') || '';
                        document.getElementById('venue').value = selected.getAttribute('data-venue') || '';
                    });
                    </script>
                @else
                    <form method="POST" action="{{ route('mypage.attendances.store') }}">
                        @csrf
                        <input type="hidden" name="db_setlist_id" value="{{ $dbSetlist->id }}">
                        <div class="mb-3">
                            <label for="attended_date" class="form-label">参加日</label>
                            <input type="date" class="form-control" id="attended_date" name="attended_date" value="{{ old('attended_date', $defaultAttendedDate) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="venue" class="form-label">会場</label>
                            <input type="text" class="form-control" id="venue" name="venue" value="{{ old('venue', $dbSetlist->tour->venue) }}">
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">登録</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
