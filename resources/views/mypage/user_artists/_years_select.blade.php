{{-- Years（Biography の年へ）。公式の Database の一覧と同じ。$selectedYear があればその年を選んだ状態 --}}
@php $bioYears = \App\Support\Biography::years('user', $artist->id); @endphp
@if ($bioYears->isNotEmpty())
    <select class="year-select" name="select" onChange="location.href=value;">
        <option value="" disabled {{ empty($selectedYear) ? 'selected' : '' }}>Years</option>
        @foreach ($bioYears as $b)
            <option value="{{ route('mypage.user_artists.biography.year', [$artist->id, $b->year]) }}" {{ (int) ($selectedYear ?? 0) === $b->year ? 'selected' : '' }}>{{ $b->year }}</option>
        @endforeach
    </select>
@endif
