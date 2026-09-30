{{-- 楽曲ページの一覧の絞り込み。
     ・表記：別表記（alternative_title）で演奏されたことがある曲だけ「All / 表記1 / 表記2」を出し、選んだ表記で
       演奏した行だけにする（セトリやスタンプに出ている表記と同じ公演だけを見られるようにするため）。
       選んだ表記は見出し（.database-title）にも出し、Allなら曲名に戻す。
     ・DOUBLE ENCOREを除く：福山雅治の曲だけ。DOUBLE ENCORE（弾き語り）でだけ演奏した行を隠す。チェックボックスはタブの下（songs._double_encore_toggle）に置く。
     一覧の行には data-titles（その行で使われた表記のJSON）と、DOUBLE ENCOREでだけ演奏した行には
     data-double-encore-only を付けておく。隠した行は#の連番に数えられないので、番号は絞り込んだ後の回数になる。
     必要な変数: $performanceTitles（空なら表記ボタンは出さない）、$initialTitle、$songTitle、$showDoubleEncoreToggle（任意） --}}
@php $showDoubleEncoreToggle = $showDoubleEncoreToggle ?? false; @endphp
@if ($performanceTitles || $showDoubleEncoreToggle)
    @if ($performanceTitles)
    <div class="song-performance-tabs song-title-filter" style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px;">
        @foreach ($performanceTitles ? array_merge([null], $performanceTitles) : [] as $filterTitle)
            @php $isActive = $filterTitle === $initialTitle; @endphp
            <button type="button" class="song-title-filter-btn" data-title="{{ $filterTitle }}"
                style="padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 500; cursor: pointer; {{ $isActive ? 'border: none; background: #764ba2; color: white;' : 'border: 1px solid #764ba2; background: white; color: #764ba2;' }}">
                {{ $filterTitle ?? 'All' }}
            </button>
        @endforeach
    </div>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var titleButtons = document.querySelectorAll('.song-title-filter-btn');
            var excludeCheckbox = document.getElementById('excludeDoubleEncore');
            var headingEls = document.querySelectorAll('.database-title');
            var songTitle = @json($songTitle);
            var currentTitle = @json($initialTitle);

            function applyFilters() {
                var excludeDoubleEncore = excludeCheckbox ? excludeCheckbox.checked : false;
                if (titleButtons.length) {
                    headingEls.forEach(function (el) { el.textContent = currentTitle || songTitle; });
                }
                titleButtons.forEach(function (b) {
                    var active = (b.dataset.title || null) === currentTitle;
                    b.style.background = active ? '#764ba2' : 'white';
                    b.style.color = active ? 'white' : '#764ba2';
                    b.style.border = active ? 'none' : '1px solid #764ba2';
                });
                document.querySelectorAll('tr[data-titles]').forEach(function (row) {
                    var titles = JSON.parse(row.dataset.titles || '[]');
                    var titleMatch = !currentTitle || titles.indexOf(currentTitle) !== -1;
                    var hiddenAsDoubleEncore = excludeDoubleEncore && row.dataset.doubleEncoreOnly === '1';
                    row.style.display = titleMatch && !hiddenAsDoubleEncore ? '' : 'none';
                });
                // 絞り込んだ結果1行も無い一覧は、表を隠してメッセージを出す
                // （楽曲ページで一覧が空のときと同じ文言・見た目。Live Performances以外のタブは参加記録）
                document.querySelectorAll('table').forEach(function (table) {
                    var rows = table.querySelectorAll('tr[data-titles]');
                    if (!rows.length) return;
                    var empty = Array.prototype.every.call(rows, function (row) { return row.style.display === 'none'; });
                    var message = table.nextElementSibling && table.nextElementSibling.classList.contains('song-title-filter-empty')
                        ? table.nextElementSibling : null;
                    if (!message) {
                        message = document.createElement('p');
                        message.className = 'song-title-filter-empty';
                        message.style.cssText = 'color: #718096; text-align: center;';
                        message.textContent = table.closest('#live-performances-panel') ? '演奏記録がありません。' : '参加記録がありません。';
                        table.parentNode.insertBefore(message, table.nextSibling);
                    }
                    table.style.display = empty ? 'none' : '';
                    message.style.display = empty ? '' : 'none';
                });
                var url = new URL(window.location.href);
                if (currentTitle) {
                    url.searchParams.set('title', currentTitle);
                } else {
                    url.searchParams.delete('title');
                }
                if (excludeDoubleEncore) {
                    url.searchParams.set('exclude_double_encore', '1');
                } else {
                    url.searchParams.delete('exclude_double_encore');
                }
                history.replaceState(null, '', url);
            }

            titleButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    currentTitle = btn.dataset.title || null;
                    applyFilters();
                });
            });
            if (excludeCheckbox) {
                excludeCheckbox.addEventListener('change', applyFilters);
            }
            applyFilters();
        });
    </script>
@endif
