@extends('layouts.app')
@section('title', 'Yuki Official - ' . ($attendance->dbSetlist->tour->title ?? '参加記録'))

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $attendance->dbSetlist->tour->title ?? '参加記録'],
            ]])
            <p class="database-subtitle" style="">
                @if ($attendance->dbSetlist->tour->artist)
                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}" style="color: white; text-decoration: none;">
                        {{ $attendance->dbSetlist->tour->artist->name }}
                    </a>
                @endif
            </p>
            <h1 class="database-title" style="">{{ $attendance->dbSetlist->tour->title ?? '' }}</h1>
            <p class="database-subtitle" id="attendanceDisplay" style="">
                @if ($attendance->attended_date)
                    {{ $attendance->attended_date->format('Y.m.d') }}
                @endif
                <br>
                {{ $attendance->venue }}
                <a href="#" id="attendanceEditToggle" style="color: white; margin-left: 6px;" title="参加日・会場を編集" onclick="event.preventDefault(); toggleAttendanceEdit();">
                    <i class="fa-solid fa-pen" style="font-size: 0.75em;"></i>
                </a>
            </p>

            <form method="POST" action="{{ route('mypage.attendances.update', $attendance) }}" id="attendanceEditForm" style="display: none; text-align: center;">
                @csrf
                @method('PUT')
                <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <input type="date" name="attended_date" value="{{ $attendance->attended_date?->format('Y-m-d') }}" style="border-radius: 6px; border: none; padding: 4px 8px; font-size: 0.85rem;">
                    <input type="text" name="venue" value="{{ $attendance->venue }}" placeholder="会場" style="border-radius: 6px; border: none; padding: 4px 8px; font-size: 0.85rem;">
                    <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;" title="保存">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </div>
            </form>

            <script>
            function toggleAttendanceEdit() {
                document.getElementById('attendanceDisplay').style.display = 'none';
                document.getElementById('attendanceEditForm').style.display = 'block';
            }
            </script>
        </div>
    </div>

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="setlist" style="width: 100%;">
                    @include('mypage.attendances._setlist_cards', ['setlistModel' => $attendance->dbSetlist, 'songs' => $songs])
                </div>

                {{-- 前後リンク --}}
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if (!empty($previous))
                        <a href="{{ route('mypage.attendances.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (!empty($next))
                        <a href="{{ route('mypage.attendances.show', $next->id) }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="{{ route('mypage.index') }}" style="color: #888; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back to My Page
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
