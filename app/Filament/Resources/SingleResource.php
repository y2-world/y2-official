<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SingleResource\Pages;
use App\Filament\Resources\SingleResource\RelationManagers;
use App\Models\Single;
use App\Models\Song;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SingleResource extends Resource
{
    protected static ?string $model = Single::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Mr.Children Database';

    protected static ?int $navigationSort = 23;

    protected static ?string $navigationLabel = 'シングル';

    protected static ?string $modelLabel = 'シングル';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('single_id')
                    ->label('シングルID')
                    ->numeric(),

                Forms\Components\DatePicker::make('date')
                    ->label('発売日')
                    ->required()
                    ->native(false)
                    ->displayFormat('Y.m.d'),

                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Toggle::make('download')
                    ->label('配信シングル')
                    ->onColor('success')
                    ->offColor('gray'),

                // 収録曲
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        // 曲名（常に表示）
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn() => Song::pluck('title', 'id'))
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
            ]);
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
            'index' => Pages\ListSingles::route('/'),
            'create' => Pages\CreateSingle::route('/create'),
            'edit' => Pages\EditSingle::route('/{record}/edit'),
        ];
    }
}
