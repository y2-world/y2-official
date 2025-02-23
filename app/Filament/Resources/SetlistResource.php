<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SetlistResource\Pages;
use App\Filament\Resources\SetlistResource\RelationManagers;
use App\Setlist;
use App\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use App\Models\Year;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Placeholder;

class SetlistResource extends Resource
{
    protected static ?string $model = Setlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('artist_id')
                ->label(__('アーティスト'))
                ->searchable()
                ->getSearchResultsUsing(
                    fn(string $query) => Artist::where('name', 'like', "%{$query}%")
                        ->limit(10)
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->getOptionLabelUsing(fn($value) => Artist::find($value)?->name ?? '不明なアーティスト'),

            TextInput::make('title')
                ->label(__('ツアータイトル'))
                ->required(),

            DatePicker::make('date')
                ->label(__('公演日'))
                ->native(false)
                ->displayFormat('Y/m/d')
                ->required(),

            TextInput::make('venue')
                ->label(__('会場'))
                ->required(),

            Radio::make('fes')
                ->label('ライブ形態')
                ->options([
                    0 => '単独ライブ',
                    1 => 'フェス',
                ])
                ->inline()
                ->reactive() // フォームをリアルタイム更新
                ->default(0), // デフォルトを単独ライブ（0）に設定

            // 右側は空欄（スペースとして配置）
            TextInput::make('empty')
                ->label('')
                ->disabled() // 編集不可
                ->columnSpan(1), // 右側に1カラム分の空き

            // 単独ライブ用
            Repeater::make('setlist')
                ->label(__('本編'))
                ->simple(
                    TextInput::make('song')
                        ->required(),
                )
                ->hidden(fn($get) => $get('fes') !== 0), // `hidden()` の書き方を修正,


            Repeater::make('encore')
                ->label(__('アンコール'))
                ->simple(
                    TextInput::make('song')
                        ->required(),
                )
                ->hidden(fn($get) => $get('fes') !== 0), // `hidden()` の書き方を修正

            Repeater::make('fes_setlist')
                ->label(__('本編'))
                ->schema([
                    Select::make('artist_id')
                        ->label(__('アーティスト'))
                        ->searchable()
                        ->getSearchResultsUsing(
                            fn(string $query) => Artist::where('name', 'like', "%{$query}%")
                                ->limit(10)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn($value) => Artist::find($value)?->name ?? '不明なアーティスト')
                        ->required(),

                    TextInput::make('song')->label(__('楽曲'))->required(),
                ])
                ->columns(2) // 2列に設定
                ->grid(1)
                ->hidden(fn($get) => $get('fes') === 0), // 単独ライブのときは非表示

            Repeater::make('fes_encore')
                ->label(__('アンコール'))
                ->schema([
                    Select::make('artist_id')
                        ->label(__('アーティスト'))
                        ->searchable()
                        ->getSearchResultsUsing(
                            fn(string $query) => Artist::where('name', 'like', "%{$query}%")
                                ->limit(10)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn($value) => Artist::find($value)?->name ?? '不明なアーティスト')
                        ->required(),
                    TextInput::make('song')->label(__('楽曲'))->required(),
                ])
                ->columns(2) // 2列に設定
                ->grid(1)
                ->hidden(fn($get) => $get('fes') === 0), // 単独ライブのときは非表示
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('artist.name')->label('アーティスト'),
                TextColumn::make('title')->label('タイトル'),
                TextColumn::make('date')->date('Y.m.d')->label('公演日'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSetlists::route('/'),
            'create' => Pages\CreateSetlist::route('/create'),
            'edit' => Pages\EditSetlist::route('/{record}/edit'),
        ];
    }
}
