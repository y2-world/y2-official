{{-- 福山雅治の楽曲一覧の弾き語り列。DOUBLE ENCORE（弾き語り）で演奏した公演はギター、
     同じ公演で通常（本編・1つ目のアンコール）でも演奏していればギターとマイクを横に並べて出す。
     マイクは斜めのハンドマイクで、Font Awesome（無料版）に無いのでSVGで描く --}}
<td class="mobile" style="width: 1%; text-align: center; white-space: nowrap;">
    @if ($hikigatari)
        <span style="display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
            <i class="fa-solid fa-guitar" title="弾き語り（DOUBLE ENCORE）" style="color: #764ba2;"></i>
            @if ($normal)
                <svg viewBox="0 0 16 16" width="1em" height="1em" fill="#764ba2" style="flex-shrink: 0;" role="img" aria-label="通常">
                    <title>通常</title>
                    {{-- マイクのヘッド（右上） --}}
                    <circle cx="10.5" cy="5.5" r="4.3" />
                    {{-- 持ち手（左下へ斜め） --}}
                    <path d="M6.5 7.3 L8.7 9.5 L3.2 14.6 A1.05 1.05 0 0 1 1.4 12.8 Z" />
                </svg>
            @endif
        </span>
    @endif
</td>
