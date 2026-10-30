<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbSetlistResource\Pages;
use App\Models\DbSetlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Illuminate\Validation\Rule;
use Filament\Tables;
use Filament\Tables\Table;
use App\Support\AdminData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class DbSetlistResource extends Resource
{
    protected static ?string $model = DbSetlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    // ソロプロジェクトとバンドは、お互いの曲も選択肢に含める（アーティストの編集画面の「ソロプロジェクト」）
    protected static function songArtistIds($artistId): array
    {
        return \App\Models\Artist::soloProjectArtistIds(AdminData::query('artist'), $artistId);
    }

    // 曲の選択肢の表示。アーティスト欄（credit）がある曲は「曲名 - アーティスト」にする（コラボ曲だと分かるように。SlSetlist の選択肢と同じ書き方）
    protected static function songOptionLabel(Model $song): string
    {
        $credit = trim((string) $song->credit);

        return $credit === '' ? $song->title : $song->title . ' - ' . e($credit);
    }

    protected static function bulkSongValue(string $title, $artistId): string|int
    {
        if (!$artistId) {
            return $title;
        }

        $matches = static::songQuery($artistId)
            ->whereRaw('LOWER(TRIM(title)) = LOWER(?)', [trim($title)])
            ->limit(2)
            ->pluck('id');
        if ($matches->count() === 1) {
            return (int) $matches->first();
        }

        // 曲の名前で合わなければ、今までのセットリストで使った別表記（「Unti-Unti」→「Anti-Anti」など）で探す
        return $matches->isEmpty() ? (static::songIdByAlternativeTitle($title, $artistId) ?? $title) : $title;
    }

    // 今までのセットリストで、その別表記が付いていた曲。別々の曲に同じ別表記があれば決められないので null
    protected static function songIdByAlternativeTitle(string $title, $artistId): ?int
    {
        $ids = array_keys(static::alternativeTitleMaps($artistId)['lower'][mb_strtolower(trim($title))] ?? []);

        return count($ids) === 1 ? (int) $ids[0] : null;
    }

    // 今までのセットリストの別表記。lower：小文字にした別表記 => 曲、exact：曲 => 別表記（書いたとおり）
    protected static function alternativeTitleMaps($artistId): array
    {
        static $maps = [];
        $key = (AdminData::memberId() ?? 0) . '|' . $artistId;
        if (!isset($maps[$key])) {
            $songIds = static::songQuery($artistId)->pluck('id')->map(fn ($id) => (string) $id)->flip();
            $tourIds = AdminData::query('concert')->whereIn(AdminData::column('concert', 'artist_id'), static::songArtistIds($artistId))->pluck('id');
            $lower = [];
            $exact = [];
            foreach (AdminData::query('setlist')->whereIn(AdminData::column('setlist', 'tour_id'), $tourIds)->get() as $setlist) {
                foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                    $alt = trim((string) ($item['alternative_title'] ?? ''));
                    $song = (string) ($item['song'] ?? '');
                    if ($alt !== '' && isset($songIds[$song])) {
                        $lower[mb_strtolower($alt)][$song] = true;
                        $exact[$song][$alt] = true;
                    }
                }
            }
            $maps[$key] = compact('lower', 'exact');
        }

        return $maps[$key];
    }

    // 一括入力の1行を、セットリストの1曲にする。打った表記が合った曲の名前と違い、今までのセットリストでその曲に登録した別表記と同じなら
    // （「I'LL BE」→「I'll be」など）、打った表記を別表記に入れる。知らない書き方（大文字・小文字だけ違う打ち間違いなど）はオリジナルの曲名のまま
    protected static function bulkItem(string $title, $artistId): array
    {
        $item = ['_uuid' => (string) \Illuminate\Support\Str::uuid(), 'song' => static::bulkSongValue($title, $artistId)];
        if (is_int($item['song'])
            && static::songQuery($artistId)->whereKey($item['song'])->value('title') !== $title
            && isset(static::alternativeTitleMaps($artistId)['exact'][(string) $item['song']][$title])) {
            $item['alternative_title'] = $title;
        }

        return $item;
    }

    // 一括入力で新しく入った行があるか（手動入力で直しただけ・一括入力の欄を開いたときのままなら、使っていない扱い）
    public static function usedBulkInput(array $data): bool
    {
        return !empty($data['_bulk_added_uuids']);
    }

    // 一括入力で入った行を、今のアーティストの曲で照らし合わせ直す。
    // すでにそのアーティストの曲につながっている行はそのまま。ほかのアーティストの曲・つながっていない行は、打った表記で照らし合わせ直す
    public static function rematchBulkSongs(Forms\Get $get, Forms\Set $set): void
    {
        $artistId = $get('data._artist_id', true);
        $added = array_fill_keys((array) $get('data._bulk_added_uuids', true), true);
        if (!$artistId || !$added) return;
        $rematch = function (array $item) use ($added, $artistId): array {
            $song = (string) ($item['song'] ?? '');
            if (!isset($added[$item['_uuid'] ?? '']) || $song === '') return $item;
            if (is_numeric($song) && static::songQuery($artistId)->whereKey((int) $song)->exists()) return $item;
            $alt = trim((string) ($item['alternative_title'] ?? ''));
            $title = $alt !== '' ? $alt : (is_numeric($song) ? (string) AdminData::query('song')->whereKey((int) $song)->value('title') : trim($song));
            $new = static::bulkItem($title, $artistId);
            $item['song'] = $new['song'];
            $item['alternative_title'] = $new['alternative_title'] ?? null;

            return $item;
        };
        $set('data.setlist', array_map($rematch, (array) $get('data.setlist', true)), true);
        $encore = array_map($rematch, \App\Support\EncoreFormGroups::toItems((array) $get('data._encore_groups', true)));
        $set('data._encore_groups', \App\Support\EncoreFormGroups::fromItems($encore), true);
        static::refreshBulkFromManual($get, $set);
    }

    // 手動入力の今の曲で、一括入力の欄を作り直す（一括入力で新しく入った行の印はそのまま）
    public static function refreshBulkFromManual(Forms\Get $get, Forms\Set $set): void
    {
        $setlist = array_values((array) $get('data.setlist', true));
        $encore = \App\Support\EncoreFormGroups::toItems((array) $get('data._encore_groups', true));
        $text = static::bulkTextFromItems($setlist, $encore);
        $set('data._bulk_input', $text, true);
        $set('data._bulk_setlist_uuids', array_column($setlist, '_uuid'), true);
        $set('data._bulk_encore_items', $encore, true);
        $set('data._bulk_encore_uuids', array_column($encore, '_uuid'), true);
    }

    // 登録済みの曲を、一括入力の書き方（1行1曲、本編とアンコール・アンコールの区切りは空行）にする。表記は別表記があればそれ
    public static function bulkTextFromItems(array $setlist, array $encore): string
    {
        $ids = collect(array_merge($setlist, $encore))->pluck('song')->filter(fn ($v) => is_numeric($v))->unique()->all();
        $titles = AdminData::query('song')->whereIn('id', $ids)->pluck('title', 'id');
        $line = fn ($item) => trim((string) ($item['alternative_title'] ?? '')) !== ''
            ? trim((string) $item['alternative_title'])
            : (is_numeric($item['song'] ?? null) ? (string) ($titles[(int) $item['song']] ?? $item['song']) : trim((string) ($item['song'] ?? '')));
        $blocks = [array_map($line, array_values($setlist))];
        foreach (array_values($encore) as $i => $item) {
            if ($i === 0 || !empty($item['encore_block_start'])) {
                $blocks[] = [];
            }
            $blocks[count($blocks) - 1][] = $line($item);
        }

        return implode("\n\n", array_map(fn ($lines) => implode("\n", array_filter($lines, fn ($l) => $l !== '')), $blocks));
    }

    public static function hasUnmatchedBulkSongs(array $data): bool
    {
        return static::unmatchedBulkSongs($data) !== [];
    }

    public static function shouldConfirmUnmatchedBulkSongs(array $data): bool
    {
        return static::usedBulkInput($data) && static::hasUnmatchedBulkSongs($data);
    }

    public static function unmatchedBulkSongsModalDescription(array $data): HtmlString
    {
        return new HtmlString(
            '以下の楽曲が合致しませんでした。<br>'
            . implode('<br>', array_map('e', static::unmatchedBulkSongs($data)))
            . '<br>既存の楽曲にリンクせずに登録します。よろしいですか？'
        );
    }

    // 「通常入力に反映」を押したとき、反映したら新しく入る行のうち、曲にリンクしないもの（ダイアログに出す）。
    // フォームの今の入力を写したもの（FormStatePreview）の上で反映してみるので、本物のフォームは変えない
    public static function unmatchedOnApply(array $data): array
    {
        $preview = new \App\Support\FormStatePreview($data);
        $before = (array) ($data['_bulk_added_uuids'] ?? []);
        static::syncBulkSetlist($data['_bulk_input'] ?? '', $preview->get(), $preview->set());
        $after = $preview->data();
        // 前に反映して確かめた行は出さない（今回新しく入る行だけ）
        $after['_bulk_added_uuids'] = array_values(array_diff((array) ($after['_bulk_added_uuids'] ?? []), $before));

        return static::unmatchedBulkSongs($after);
    }

    // 「通常入力に反映」のボタン。合致しない曲があるときだけ、反映する前にダイアログで確かめる
    protected static function bulkApplyAction(): Forms\Components\Actions\Action
    {
        $unmatched = fn ($livewire) => static::unmatchedOnApply($livewire->form->getRawState());

        return Forms\Components\Actions\Action::make('applyBulkSetlist')
            ->label('通常入力に反映')
            ->requiresConfirmation(fn ($livewire) => $unmatched($livewire) !== [])
            // 見出し・文を設定するとダイアログ付きのボタンになるので、合致しない曲が無いときはダイアログを出さずにそのまま反映する
            ->modalHidden(fn ($livewire) => $unmatched($livewire) === [])
            ->modalHeading('未一致の楽曲があります')
            ->modalDescription(fn ($livewire) => new HtmlString(
                '以下の楽曲が合致しませんでした。<br>'
                . implode('<br>', array_map('e', $unmatched($livewire)))
                . '<br>既存の楽曲にリンクせずに登録します。よろしいですか？'
            ))
            ->modalSubmitActionLabel('反映する')
            ->action(function (Forms\Get $get, Forms\Set $set, $livewire) {
                static::syncBulkSetlist($get('_bulk_input'), $get, $set);
                // 反映したら通常入力のタブを開く（タブの extraAlpineAttributes）
                $livewire->dispatch('setlist-bulk-applied');
            });
    }

    // 一括入力で新しく入った行のうち、曲にリンクしなかったもの。$withoutFeaturing なら、共演者・アーティストを入れた行（曲名の文字のまま入れる）を除く
    public static function unmatchedBulkSongs(array $data, bool $withoutFeaturing = false): array
    {
        $generated = array_fill_keys((array) ($data['_bulk_added_uuids'] ?? []), true);
        $items = array_merge(
            array_values((array) ($data['setlist'] ?? [])),
            \App\Support\EncoreFormGroups::toItems((array) ($data['_encore_groups'] ?? [])),
        );
        $unmatched = [];
        $number = 0;
        foreach ($items as $item) {
            $skipped = !empty($item['is_daily']) || !empty($item['medley']);
            if (!$skipped) $number++;
            $song = trim((string) ($item['song'] ?? ''));
            if (isset($generated[$item['_uuid'] ?? '']) && $song !== '' && !is_numeric($song) && (!$withoutFeaturing || blank($item['featuring'] ?? null))) {
                $unmatched[] = $number . '. ' . $song;
            }
        }

        return $unmatched;
    }

    protected static function syncBulkSongs($state, string $target, string $generatedState, $artistId, Forms\Get $get, Forms\Set $set): void
    {
        $chunks = is_array($state) ? $state : [$state];
        $titles = [];
        $blockStarts = [];
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkTitles = array_values(array_filter(array_map(
                fn ($line) => trim(preg_replace('/^\\s*(?:\\d+\\s*[.)．、]\\s*|[-*・]\\s*)/u', '', trim($line))),
                preg_split('/\\R/u', (string) $chunk) ?: []
            ), fn ($title) => $title !== ''));
            if ($chunkTitles && $chunkIndex > 0) {
                $blockStarts[] = count($titles);
            }
            $titles = array_merge($titles, $chunkTitles);
        }

        $generatedUuids = (array) $get('data.' . $generatedState, true);
        $currentItems = array_values((array) $get('data.' . $target, true));
        $generatedItems = array_values(array_filter(
            $currentItems,
            fn ($item) => in_array($item['_uuid'] ?? null, $generatedUuids, true)
        ));
        $baseItems = array_values(array_filter(
            $currentItems,
            fn ($item) => !in_array($item['_uuid'] ?? null, $generatedUuids, true)
                && (
                    trim((string) ($item['song'] ?? '')) !== ''
                    || trim((string) ($item['alternative_title'] ?? '')) !== ''
                    || trim((string) ($item['featuring'] ?? '')) !== ''
                    || trim((string) ($item['medley_title'] ?? '')) !== ''
                    || trim((string) ($item['daily_note'] ?? '')) !== ''
                    || !empty($item['is_daily'])
                    || !empty($item['medley'])
                    || !empty($item['encore_block_start'])
                )
        ));

        // 一括入力を編集しても、変更されていない行の詳細設定は保つ。
        $preservedByTitle = [];
        foreach ($generatedItems as $item) {
            $songValue = $item['song'] ?? '';
            // 打った表記（別表記に入れたもの）があればそれ、無ければ曲名で、同じ行かを見分ける
            $title = filled($item['alternative_title'] ?? null) ? (string) $item['alternative_title'] : (is_numeric($songValue)
                ? (string) (static::songQuery($artistId)->whereKey($songValue)->value('title') ?? $songValue)
                : (string) $songValue);
            $preservedByTitle[$title][] = $item;
        }

        $newItems = [];
        $added = (array) $get('data._bulk_added_uuids', true);
        foreach ($titles as $index => $title) {
            if (!empty($preservedByTitle[$title])) {
                $item = array_shift($preservedByTitle[$title]);
            } else {
                // 一括入力で新しく入った行（確認のダイアログはこの行だけを見る）
                $item = static::bulkItem($title, $artistId);
                $added[] = $item['_uuid'];
            }
            if (in_array($index, $blockStarts, true)) {
                $item['encore_block_start'] = true;
            } else {
                unset($item['encore_block_start']);
            }
            $newItems[] = $item;
        }

        $set('data.' . $target, \App\Support\RepeaterKeys::byUuid(array_merge($baseItems, $newItems)), true);
        $set('data.' . $generatedState, array_column($newItems, '_uuid'), true);
        $set('data._bulk_added_uuids', array_values(array_unique($added)), true);
    }

    protected static function syncBulkSetlist($state, Forms\Get $get, Forms\Set $set): void
    {
        $sections = preg_split('/\\R[ \\t]*\\R/u', trim((string) $state)) ?: [];
        $artistId = $get('data._artist_id', true);
        static::syncBulkSongs(array_slice($sections, 0, 1), 'setlist', '_bulk_setlist_uuids', $artistId, $get, $set);
        $set('data._bulk_encore_items', \App\Support\EncoreFormGroups::toItems((array) $get('data._encore_groups', true)), true);
        static::syncBulkSongs(array_slice($sections, 1), '_bulk_encore_items', '_bulk_encore_uuids', $artistId, $get, $set);
        $set('data._encore_groups', \App\Support\EncoreFormGroups::fromItems((array) $get('data._bulk_encore_items', true)), true);
    }

    // Yuki's Setlists の「パターンを追加」：参加記録のセットリストを、パターンの作成画面の入力にする（まだ保存しない）。
    // データベースの曲につながっている曲はその曲、つながっていない曲は一括入力と同じく曲名で照らし合わせる。
    // 合わなかった曲は一括入力で入った行と同じ印（_bulk_added_uuids）を付け、保存のときのダイアログに出す
    public static function formDataFromSlSetlist(\App\Models\SlSetlist $record, $artistId): array
    {
        $added = [];
        $toItems = function (array $items) use ($artistId, &$added) {
            $out = [];
            foreach ($items as $sl) {
                $slSong = is_numeric($sl['song'] ?? null) ? \App\Models\SlSong::find((int) $sl['song']) : null;
                if (!$slSong) {
                    continue;
                }
                $item = $slSong->db_song_id
                    ? ['_uuid' => (string) \Illuminate\Support\Str::uuid(), 'song' => (string) $slSong->db_song_id]
                    : static::bulkItem($slSong->title, $artistId);
                $item['song'] = (string) $item['song'];
                $item += ['is_daily' => false, 'medley' => !empty($sl['medley']), 'daily_note' => null,
                    'featuring' => $sl['featuring'] ?? null, 'featuring_type' => !empty($sl['featuring']) ? ($sl['featuring_type'] ?? 'guest') : null,
                    'alternative_title' => $sl['alternative_title'] ?? null];
                if (!is_numeric($item['song'])) {
                    $added[] = $item['_uuid'];
                }
                $out[] = $item;
            }

            return $out;
        };
        $data = static::splitEncoreForFormData(['setlist' => $toItems($record->setlist ?? []), 'encore' => $toItems($record->encore ?? [])]);
        $data['setlist'] = \App\Support\RepeaterKeys::byUuid($data['setlist']);
        $data['_bulk_added_uuids'] = $added;

        return $data;
    }

    // 保存のときに新しい曲としてデータベースに登録する行（Yuki's Setlists の「パターンを追加」から開いたとき）。
    // 合わなかった印の行のうち、文字のままのもの。カバー・共演の曲（featuring あり）は曲にせず、曲名の文字のまま入れる
    public static function slSetlistSongsToCreate(array $data): array
    {
        $added = array_fill_keys((array) ($data['_bulk_added_uuids'] ?? []), true);
        $titles = [];
        foreach (array_merge(array_values((array) ($data['setlist'] ?? [])), \App\Support\EncoreFormGroups::toItems((array) ($data['_encore_groups'] ?? []))) as $item) {
            $song = trim((string) ($item['song'] ?? ''));
            if (isset($added[$item['_uuid'] ?? '']) && $song !== '' && !is_numeric($song) && empty($item['featuring'])) {
                $titles[$song] = true;
            }
        }

        return array_keys($titles);
    }

    public static function splitEncoreForFormData(array $data): array
    {
        $items = array_values((array) ($data['encore'] ?? []));
        $data['_encore_groups'] = \App\Support\EncoreFormGroups::fromItems($items);
        $data['_bulk_encore_items'] = $items;
        $data['_bulk_encore_uuids'] = array_column($items, '_uuid');
        $data['_bulk_setlist_uuids'] = array_column((array) ($data['setlist'] ?? []), '_uuid');
        // 登録済みの曲を、一括入力にも入れておく
        $data['_bulk_input'] = static::bulkTextFromItems((array) ($data['setlist'] ?? []), $items);
        unset($data['encore']);
        return $data;
    }

    public static function mergeEncoreFormData(array $data): array
    {
        $data['encore'] = \App\Support\EncoreFormGroups::toItems((array) ($data['_encore_groups'] ?? []));
        unset($data['_encore_groups'], $data['_bulk_encore_items'], $data['_bulk_encore_uuids']);
        return $data;
    }

    protected static function encoreGroupsField(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('_encore_groups')
            ->label('アンコール')
            ->extraAttributes(['class' => 'setlist-encore-groups'])
            ->schema([
                Forms\Components\Hidden::make('_uuid')->default(fn() => (string) \Illuminate\Support\Str::uuid()),
                Forms\Components\Repeater::make('songs')->hiddenLabel()->extraAttributes(['class' => 'setlist-encore-songs'])->schema([
                    Forms\Components\Hidden::make('_uuid')->default(fn() => (string) \Illuminate\Support\Str::uuid()),
                    Forms\Components\Select::make('song')->label('曲名')->required()
                        ->options(fn(Forms\Get $get) => static::songOptions($get('../../../../_artist_id')))
                        ->searchable()->native(false)->allowHtml()->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('alternative_title', null))
                        ->getOptionLabelUsing(function ($value) {
                            if (!is_numeric($value)) return $value;
                            $song = AdminData::query('song')->find($value);
                            return $song ? static::songOptionLabel($song) : $value;
                        })
                        ->createOptionForm([Forms\Components\TextInput::make('title')->label('曲名')->required()->maxLength(255)])
                        ->createOptionUsing(fn(array $data) => $data['title'])
                        ->columnSpanFull(),
                    Forms\Components\Section::make('詳細設定')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('medley')->label('メドレー')->default(false)->live(),
                            Forms\Components\Toggle::make('is_daily')->label('日替わり')->default(false)->live(),
                            Forms\Components\TextInput::make('medley_title')->label('メドレーのタイトル')->visible(fn(Forms\Get $get) => (bool) $get('medley'))->maxLength(255),
                            Forms\Components\TextInput::make('daily_note')->label('日替わり情報')->maxLength(255),
                        ]),
                        Forms\Components\Radio::make('featuring_type')->label('表示形式')->options([
                            'guest' => '共演者（feat.表記のまま表示）', 'artist' => '別名義アーティスト（「 / 」区切りで表示）', 'credit' => 'アーティスト表記あり',
                        ])->default('guest')->inline(),
                        Forms\Components\TextInput::make('featuring')->label('共演者・アーティスト')->maxLength(255),
                        Forms\Components\TextInput::make('alternative_title')->label('別表記')->maxLength(255),
                    ])->collapsible()->collapsed()->columnSpanFull(),
                ])->defaultItems(0)->live()->reorderable()
                    ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                        $items = (array) $get('data.setlist', true);
                        foreach ((array) $get('data._encore_groups', true) as $group) $items = array_merge($items, (array) ($group['songs'] ?? []));
                        $number = 0;
                        foreach ($items as $item) {
                            $skipped = !empty($item['is_daily']) || !empty($item['medley']);
                            if (($item['_uuid'] ?? null) === ($state['_uuid'] ?? null)) return $skipped ? '' : (string) ($number + 1);
                            if (!$skipped) $number++;
                        }
                        return null;
                    })
                    ->addActionLabel('曲を追加')->columnSpanFull(),
            ])
            ->defaultItems(1)->live()->reorderable()
            ->itemLabel(function (array $state, $component): ?string {
                $position = 0;
                $groups = (array) $component->getState();
                if (count($groups) <= 1) return null;
                foreach ($groups as $group) {
                    $position++;
                    if (($group['_uuid'] ?? null) === ($state['_uuid'] ?? null)) return 'アンコール' . $position;
                }
                return null;
            })
            ->addActionLabel('アンコールを追加')->columnSpanFull();
    }

    protected static ?string $navigationLabel = 'セットリスト';

    protected static ?string $modelLabel = 'セットリスト';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 21;

    // 管理者は公式のセットリスト、マイページの人は自分のセットリスト（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('setlist');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AdminData::query('setlist');
    }

    public static function tourManagementUrl($tourId): string
    {
        $setlists = AdminData::query('setlist')->ofTour($tourId)->limit(2)->get(['id']);

        if ($setlists->count() === 1) {
            return static::getUrl('edit', ['record' => $setlists->first()->id]);
        }

        return static::getUrl('manage-tour', ['tourId' => $tourId]);
    }

    // そのアーティストの曲（セットリストの曲の選択肢）
    protected static function songQuery($artistId)
    {
        return AdminData::query('song')->when($artistId, fn ($q, $id) => $q->ofArtists(static::songArtistIds($id)));
    }

    // 同じアーティストの候補一覧は、フォーム内の全曲欄で共有する。
    protected static function songOptions($artistId): array
    {
        $artistIds = $artistId ? static::songArtistIds($artistId) : [];
        $cacheKey = 'db_setlist_song_options:' . AdminData::table('song') . ':' . implode(',', $artistIds);
        $request = request();

        if (!$request->attributes->has($cacheKey)) {
            $options = static::songQuery($artistId)
                ->get()
                ->mapWithKeys(fn ($song) => [$song->id => static::songOptionLabel($song)])
                ->all();

            $request->attributes->set($cacheKey, \App\Support\JapaneseNameSorter::sortOptions($options));
        }

        return $request->attributes->get($cacheKey);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        Forms\Components\Select::make('_artist_id')
                            ->label('アーティスト')
                            ->default(fn () => request()->query('artist_id')
                                ?? AdminData::query('concert')->find(request()->query('tour_id'))?->artist_id)
                            ->options(fn() => AdminData::artistOptions())
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // アーティストを切り替えたら、今のツアー選択とスケジュール候補をクリアする。
                                // さもないと、以前選んでいた別アーティストの tour_id が残り、
                                // 例のように同名・近いタイトルのツアーが並ぶケースで「選んだのに変わらない」状態になる。
                                $set('tour_id', null);
                                $set('_schedule_pick', []);
                                // 一括入力のあとでアーティストを選んだ・変えたときは、一括入力で入った曲をそのアーティストで照らし合わせ直す
                                static::rematchBulkSongs($get, $set);
                            })
                            ->afterStateHydrated(function ($state, $set, $record) {
                                if ($record && $record->tour) {
                                    $set('_artist_id', $record->tour->artist_id);
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\Select::make('tour_id')
                            ->label('ツアー')
                            ->default(fn () => request()->query('tour_id'))
                            ->options(fn(Get $get) => AdminData::query('concert')->when(
                                $get('_artist_id'),
                                fn($q, $artistId) => $q->ofArtist($artistId)
                            )->orderBy('date1', 'asc')->pluck('title', 'id'))
                            ->live()
                            // 読み書きしてよいツアーだけ
                            ->in(fn () => AdminData::query('concert')->pluck('id')->map(fn ($id) => (string) $id)->all())
                            ->searchable()
                            ->native(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Get $get): void {
                                if (!$state || filled($get('order_no'))) return;

                                $row = (int) ($get('row') ?: 1);
                                $set('order_no', (int) AdminData::query('setlist')->ofTour($state)->where('row', $row)->max('order_no') + 1);
                            })
                            ->required(),

                        Forms\Components\TextInput::make('row')
                            ->label('段')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('order_no')
                            ->label('パターン番号')
                            ->numeric()
                            ->default(fn (Get $get) => $get('tour_id')
                                ? (int) AdminData::query('setlist')->ofTour($get('tour_id'))->where('row', (int) ($get('row') ?: 1))->max('order_no') + 1
                                : null)
                            ->required()
                            ->rules(fn(Get $get, $record) => [
                                Rule::unique(AdminData::table('setlist'), 'order_no')
                                    ->where(AdminData::column('setlist', 'tour_id'), $get('tour_id'))
                                    ->where('row', $get('row') ?? 1)
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => 'この段ではすでに使われているパターン番号です。',
                            ]),

                        Forms\Components\TextInput::make('row_title')
                            ->label('段のグループタイトル')
                            ->placeholder('例: アリーナ公演')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($set, $record) {
                                if (!$record) {
                                    return;
                                }
                                $title = AdminData::rowTitle($record->tour_id, $record->row, $record->order_no);
                                $set('row_title', $title ?? '');
                            }),

                        // ツアーにスケジュールがあるときは、スケジュールから日付（と地名）を選んでタイトルに入れられる。入れた後も手で直せる
                        Forms\Components\Select::make('_schedule_pick')
                            ->label('スケジュールから選ぶ')
                            ->multiple()
                            ->options(fn (Get $get) => static::scheduleOptions($get('tour_id')))
                            ->visible(fn (Get $get) => count(static::scheduleOptions($get('tour_id'))) > 0)
                            ->dehydrated(false)
                            ->live()
                            // 選んだ日は今のタイトルの後ろに足し、選択を外した日はタイトルから取る（手で書いた部分はそのまま）
                            ->afterStateUpdated(function ($state, $old, Get $get, $set) {
                                $state = (array) $state;
                                $old = (array) $old;
                                $subtitle = (string) $get('subtitle');
                                foreach (array_diff($old, $state) as $removed) {
                                    $subtitle = preg_replace('/(^|\s)' . preg_quote($removed, '/') . '(?=\s|$)/u', '', $subtitle);
                                }
                                foreach (array_diff($state, $old) as $added) {
                                    $subtitle = rtrim($subtitle) . ($subtitle !== '' && trim($subtitle) !== '' ? ' ' : '') . $added;
                                }
                                $set('subtitle', trim($subtitle));
                            })
                            ->helperText('選んだ順に「7.17 大阪」のように日付と地名がタイトルの後ろに足されます（単発のライブは日付だけ）。地名が違うときは手で直してください。')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('subtitle')
                            ->label('タイトル（日付や説明）')
                            ->helperText('例: 「7.17 大阪」のように入力すると、最初の空白より後ろ（地名など）が自動で小さいグレー文字になります。')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Tabs::make('setlist_input_mode')
                    ->contained(false)
                    ->extraAttributes(['class' => 'setlist-input-tabs'])
                    // 一括入力の「通常入力に反映」が終わったら、通常入力のタブ（最初のタブ）を開く
                    ->extraAlpineAttributes(['x-on:setlist-bulk-applied.window' => 'tab = getTabs()[0]'])
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('通常入力')
                            ->schema([
                Forms\Components\Section::make('セットリスト')
                    ->extraAttributes(['class' => 'setlist-input-frame'])
                    ->schema([
                        Forms\Components\Repeater::make('setlist')
                            ->live(debounce: 0)
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),

                                // 曲名（横いっぱい）
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->options(fn(Get $get) => static::songOptions($get('../../_artist_id')))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->allowHtml()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        // 曲を差し替えたのに別表記（前の曲用のライブバージョン名等）が
                                        // 残ってしまい、差し替え後の曲と噛み合わない表記のまま表示され続ける
                                        // 事故を防ぐため、曲を変更したタイミングで別表記はクリアする
                                        $set('alternative_title', null);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if (is_numeric($value)) {
                                            $song = AdminData::query('song')->find($value);
                                            return $song ? static::songOptionLabel($song) : $value;
                                        }
                                        return $value;
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['title'];
                                    })
                                    ->columnSpanFull(),

                                // ⬇️ 詳細設定
                                Forms\Components\Section::make('詳細設定')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_daily')
                                            ->label('日替わり (2曲目以降)')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\Toggle::make('medley')
                                            ->label('メドレー (2曲目以降)')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->live(),
                                        // メドレーにチェックしたときだけ出す（タイトルのあるメドレーは少ない）。メドレーのどの曲に入れても、メドレーの曲の上にタイトルの行を出し、曲番はその行に付ける
                                        Forms\Components\TextInput::make('medley_title')
                                            ->label('メドレーのタイトル')
                                            ->visible(fn (Forms\Get $get) => (bool) $get('medley'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('daily_note')
                                            ->label('日替わり情報')
                                            ->placeholder('例: 1')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),

                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                                // 共演したときだけ、曲のアーティスト欄（credit。例：絢香 × コブクロ）を「曲名 / アーティスト」で出す（ふだんは出さない）
                                                'credit' => 'アーティスト表記あり',
                                            ])
                                            ->default('guest')
                                            ->inline(),

                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->placeholder('例: I\'LL BE')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $items = $component->getState();
                                if (!is_array($items)) return '1';

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = 0;
                                foreach ($items as $item) {
                                    $isSkipped = !empty($item['is_daily']) || !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isSkipped) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isSkipped) {
                                        $number++;
                                    }
                                }

                                return '1';
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),


                        // ──────────────── アンコール ────────────────
                        static::encoreGroupsField(),
                    ])
                    ->columns(1),
                            ]),
                        Forms\Components\Tabs\Tab::make('一括入力')
                            ->schema([
                                Forms\Components\Textarea::make('_bulk_input')
                                    ->label('本編・アンコール')
                                    ->rows(30)
                                    ->placeholder("曲名を1行ずつ入力\n\n")
                                    ->helperText('本編とアンコールの間に空行を1行入れてください。')
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                // 打つたびに反映すると、曲の行を全部作り直すので入力が止まる。ボタンを押したときだけ反映する
                                Forms\Components\Actions::make([
                                    static::bulkApplyAction(),
                                ])->columnSpanFull(),
                                Forms\Components\Hidden::make('_bulk_setlist_uuids')->default([])->dehydrated(false),
                                // 一括入力で新しく入った行（確認のダイアログはこの行だけを見る）
                                Forms\Components\Hidden::make('_bulk_added_uuids')->default([])->dehydrated(false),
                                Forms\Components\Hidden::make('_bulk_encore_uuids')->default([])->dehydrated(false),
                                Forms\Components\Hidden::make('_bulk_encore_items')->default([])->dehydrated(false),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tour.artist.name')
                    ->label('アーティスト')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tour.title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('row')
                    ->label('段')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_no')
                    ->label('パターン')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('サブタイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    ->query(fn($query, array $data) =>
                        $data['value']
                            ? $query->whereHas('tour', fn($q) => $q->ofArtist($data['value']))
                            : $query
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tour')
                    // 選択肢は読み書きしてよいツアーだけ（マイページの人は自分のツアー）
                    ->options(fn () => AdminData::query('concert')->orderBy('date1', 'desc')->pluck('title', 'id')->all())
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofTour($data['value']) : $query)
                    ->label('ツアー')
                    ->searchable()
                    // 文字を入れなくても、開いたときに候補を出す
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->modalHidden()
                    ->beforeReplicaSaved(function ($replica) {
                        $maxOrderNo = (int) AdminData::query('setlist')->ofTour($replica->tour_id)
                            ->where('row', $replica->row)
                            ->max('order_no');
                        $replica->order_no = $maxOrderNo + 1;
                        $replica->subtitle = null;
                    })
                    ->after(function ($replica) {
                        redirect(static::getUrl('edit', ['record' => $replica]));
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // 更新日時の新しい順。PostgreSQL は新しい順だと更新日時が空の行（古いデータ）をいちばん上に出すので、最後に回す
            ->defaultSort(fn ($query) => $query->orderByRaw($query->getModel()->getTable() . '.updated_at desc nulls last'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDbSetlists::route('/'),
            'manage-tour' => Pages\ManageTourSetlists::route('/tour/{tourId}'),
            'create' => Pages\CreateDbSetlist::route('/create'),
            'edit' => Pages\EditDbSetlist::route('/{record}/edit'),
        ];
    }

    // ツアーのスケジュール（「2021/5/1(土) 会場」「06/13 会場」「7月13日 会場」「10.6,7 会場」など）を、
    // パターンのタイトルに使う選択肢にする。表示は「月.日 会場名」、選んだときに入る文字（キー）は「月.日 地名」。
    // 同じ地名が2日以上あれば日付順に「仙台1」「仙台2」、単発のライブは日付だけ
    public static function scheduleOptions($tourId): array
    {
        $concert = $tourId ? AdminData::query('concert')->find($tourId) : null;
        $dates = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $concert?->schedule) as $line) {
            // 日付は「8.31,9.1」「1.28,29,31,2.1」のように、月が途中で変わりながら並ぶことがある
            $day = '\d{1,2}日?(?:\s*[(（][^)）]*[)）])?';
            if (!preg_match('/^\s*(?:\d{4}\s*[\/.年]\s*)?(\d{1,2}\s*[\/.月]\s*' . $day . '(?:\s*[,，、・]\s*(?:\d{1,2}\s*[\/.月]\s*)?' . $day . ')*)\s*(.*)$/u', $line, $m)) {
                continue;
            }
            // 会場名から、かっこ書き（延期・振替・県名などの注記）を外す
            $venue = trim(preg_replace('/\s*[(（][^)）]*[)）]|【[^】]*】|《[^》]*》/u', '', $m[2]));
            $month = null;
            foreach (preg_split('/\s*[,，、・]\s*/u', preg_replace('/\s*[(（][^)）]*[)）]|日/u', '', $m[1])) as $item) {
                if (preg_match('/^(\d{1,2})\s*[\/.月]\s*(\d{1,2})$/u', $item, $md)) {
                    $month = (int) $md[1];
                    $item = $md[2];
                }
                $dates[$month . '.' . (int) $item] = $venue;
            }
        }

        // 同じ地名が続く日をひとまとまりにし、まとまりの中で1から番号を付ける（離れて同じ地名に戻ったら1から数え直す）
        $runs = [];
        foreach ($dates as $date => $venue) {
            $place = static::venuePlace($venue);
            $last = count($runs) - 1;
            if ($last >= 0 && $runs[$last]['place'] === $place) {
                $runs[$last]['dates'][$date] = $venue;
            } else {
                $runs[] = ['place' => $place, 'dates' => [$date => $venue]];
            }
        }
        $options = [];
        foreach ($runs as $run) {
            $n = 0;
            foreach ($run['dates'] as $date => $venue) {
                $n++;
                $key = (int) $concert->type === 1 || $run['place'] === ''
                    ? $date
                    : $date . ' ' . $run['place'] . (count($run['dates']) > 1 ? $n : '');
                $options[$key] = $date . ($venue !== '' ? ' ' . $venue : '');
            }
        }
        return $options;
    }

    // 会場名から、パターンタイトルに使う地名を決める。config/venue_places.php（これまでのタイトルから集めた対応）に
    // あればそれ、無ければ会場名の頭の地名（「名古屋国際会議場」→名古屋）や「○○市」、分からなければ会場名のまま
    public static function venuePlace(string $venue): string
    {
        $map = config('venue_places', []);
        // 「※公演中止」のような注記は外す
        $venue = trim(preg_replace('/\s*※.*$/u', '', $venue));
        if ($venue === '' || isset($map[$venue])) {
            return $map[$venue] ?? '';
        }
        // 「盛岡市民文化ホール 大ホール」のように、知っている会場名にホール名などが付いているもの
        $known = array_filter(array_keys($map), fn ($name) => mb_strlen($name) >= 4 && str_starts_with($venue, $name));
        if ($known) {
            usort($known, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            return $map[$known[0]];
        }
        $prefs = ['北海道', '青森', '岩手', '宮城', '秋田', '山形', '福島', '茨城', '栃木', '群馬', '埼玉', '千葉', '東京', '神奈川', '新潟', '富山', '石川', '福井', '山梨', '長野', '岐阜', '静岡', '愛知', '三重', '滋賀', '京都', '大阪', '兵庫', '奈良', '和歌山', '鳥取', '島根', '岡山', '広島', '山口', '徳島', '香川', '愛媛', '高知', '福岡', '佐賀', '長崎', '熊本', '大分', '宮崎', '鹿児島', '沖縄'];
        $placeNames = array_unique(array_merge(array_values($map), $prefs));
        usort($placeNames, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($placeNames as $place) {
            if ($place !== '' && str_starts_with($venue, $place)) {
                return $place;
            }
        }
        if (preg_match('/^([^\s市]{1,4}?)市/u', $venue, $m)) {
            return $m[1];
        }
        return $venue;
    }
}
