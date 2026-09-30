{{-- 別表記（alternative_title）で演奏されたことがある曲だけ、表記ごとに一覧を絞り込めるようにする。
     セトリやスタンプに出ている表記と同じ公演だけを見られるようにするため。
     一覧の行には data-titles（その行で使われた表記のJSON）を付けておく。
     選んだ表記は見出し（.database-title）にも出し、Allなら曲名に戻す。
     必要な変数: $performanceTitles（ボタンに出す表記。空なら何も出さない）、$initialTitle、$songTitle --}}
@if ($performanceTitles)
    <div class="song-performance-tabs song-title-filter" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
        @foreach (array_merge([null], $performanceTitles) as $filterTitle)
            @php $isActive = $filterTitle === $initialTitle; @endphp
            <button type="button" class="song-title-filter-btn" data-title="{{ $filterTitle }}"
                style="padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 500; cursor: pointer; {{ $isActive ? 'border: none; background: #764ba2; color: white;' : 'border: 1px solid #764ba2; background: white; color: #764ba2;' }}">
                {{ $filterTitle ?? 'All' }}
            </button>
        @endforeach
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 非表示の行は#の連番に数えられないので、番号はその表記での回数になる
            var titleButtons = document.querySelectorAll('.song-title-filter-btn');
            var headingEls = document.querySelectorAll('.database-title');
            var songTitle = @json($songTitle);
            function applyTitleFilter(title) {
                headingEls.forEach(function (el) { el.textContent = title || songTitle; });
                titleButtons.forEach(function (b) {
                    var active = (b.dataset.title || null) === title;
                    b.style.background = active ? '#764ba2' : 'white';
                    b.style.color = active ? 'white' : '#764ba2';
                    b.style.border = active ? 'none' : '1px solid #764ba2';
                });
                document.querySelectorAll('tr[data-titles]').forEach(function (row) {
                    var titles = JSON.parse(row.dataset.titles || '[]');
                    row.style.display = !title || titles.indexOf(title) !== -1 ? '' : 'none';
                });
                var url = new URL(window.location.href);
                if (title) {
                    url.searchParams.set('title', title);
                } else {
                    url.searchParams.delete('title');
                }
                history.replaceState(null, '', url);
            }
            titleButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyTitleFilter(btn.dataset.title || null);
                });
            });
            applyTitleFilter(@json($initialTitle));
        });
    </script>
@endif
