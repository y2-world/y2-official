<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbSingleResource\Pages;
use App\Filament\Resources\DbSingleResource\RelationManagers;
use App\Models\DbSingle;
use App\Models\DbSong;
use App\Support\AdminData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DbSingleResource extends Resource
{
    protected static ?string $model = DbSingle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 23;

    protected static ?string $navigationLabel = 'シングル';

    protected static ?string $modelLabel = 'シングル';

    // 管理者は公式のシングル、マイページの人は自分のシングル（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('single');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AdminData::query('single');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // 選択肢（読み書きしてよいアーティスト）にあるものだけ
                    ->in(fn () => array_keys(AdminData::artistOptions()))
                    ->required()
                    ->native(false)
                    ->searchable(),
                Forms\Components\TextInput::make('single_id')
                    ->label('シングルID')
                    ->numeric(),

                \App\Filament\Forms\TypedDate::make('date', '発売日')
                    ->required(),

                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Toggle::make('download')
                    ->label('配信シングル')
                    ->onColor('success')
                    ->offColor('gray'),

                Forms\Components\Toggle::make('ep')
                    ->label('EP')
                    ->onColor('warning')
                    ->offColor('gray'),

                // 収録曲
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        // 曲名（常に表示）
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn(Forms\Get $get) => AdminData::query('song')->ofArtists([$get('../../artist_id')])->orderBy('sort_order')->pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),

                        // 詳細設定（折りたたみ式）
                        Forms\Components\Section::make('詳細設定')
                            ->collapsible()
                            ->collapsed() // ← 初期状態で閉じる
                            ->schema([
                                Forms\Components\TextInput::make('exception')
                                    ->label('例外 (Instrumentalなど)')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1) // 曲名→詳細設定（縦並び）
                    ->defaultItems(1)
                    ->reorderable()
                    ->columnSpanFull()
                    ->itemLabel(function (array $state, $component): string {
                        $items = array_values($component->getState() ?? []);
                        foreach ($items as $i => $item) {
                            if ($item === $state) {
                                return (string) ($i + 1);
                            }
                        }
                        return (string) (count($items) ?: 1);
                    }),

                // コメント（収録曲の後に移動）
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('single_id')
                    ->label('シングルID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('発売日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('download')
                    ->label('配信')
                    ->onColor('success')
                    ->offColor('gray'),
                Tables\Columns\ToggleColumn::make('ep')
                    ->label('EP')
                    ->onColor('warning')
                    ->offColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // マイページのテーブルは項目名が違うので、どちらのモデルにもある ofArtist で絞る
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofArtist($data['value']) : $query)
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('download')
                    ->label('種別')
                    ->placeholder('すべて')
                    ->trueLabel('配信中')
                    ->falseLabel('CDシングル'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListDbSingles::route('/'),
            'create' => Pages\CreateDbSingle::route('/create'),
            'edit' => Pages\EditDbSingle::route('/{record}/edit'),
        ];
    }
}
