{{-- スタンプ帳の見た目の切り替え：Stamp（マス目）／Hanko（大塚愛『愛 am BEST』の裏ジャケのような、はんこと曲名の表）。
     同じ台紙（.stamp-book）の見た目だけを変えるので、絞り込み・曲順・曲名の表記はどちらでも同じ。開いている方は ?view=hanko で URL に残す。
     最後の「アーティスト」は、スタンプ帳があるほかのアーティストに切り替える --}}
<div class="stats-tab-bar stamp-view-tabs">
    <button type="button" class="stats-tab-btn is-active" data-stamp-view="stamps">Stamp</button>
    <button type="button" class="stats-tab-btn" data-stamp-view="hanko">Hanko</button>
    @if (!empty($stampTabArtists))
    <select class="stats-tab-btn stats-tab-select" id="stampArtistSelect" aria-label="Artists">
        @foreach ($stampTabArtists as $tabArtist)
            <option value="{{ $tabArtist['url'] }}" {{ $tabArtist['current'] ? 'selected' : '' }}>{{ $tabArtist['name'] }}</option>
        @endforeach
    </select>
    @endif
</div>
