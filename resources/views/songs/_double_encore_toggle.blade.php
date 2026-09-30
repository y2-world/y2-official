{{-- 福山雅治の楽曲ページだけ：DOUBLE ENCORE（弾き語り）でだけ演奏した行を隠す。
     見た目はstatsの「同ツアーを除く」と同じ（スマホではタブと同じく中央寄せ）。
     動きは songs._title_filter のスクリプトが持っている（id="excludeDoubleEncore" を見る） --}}
@if ($isHikigatariArtist ?? false)
    <div class="unique-tour-toggle double-encore-toggle" style="margin: -5px 0 12px;">
        <label class="unique-tour-label">
            <input type="checkbox" id="excludeDoubleEncore" class="unique-tour-checkbox" @if (request()->query('exclude_double_encore')) checked @endif>
            <span class="unique-tour-text">DOUBLE ENCOREを除く</span>
        </label>
    </div>
@endif
