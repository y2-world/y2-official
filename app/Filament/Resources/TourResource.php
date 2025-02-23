<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\RelationManagers;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Song;
use App\Models\Bio;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs::make('データ')
                            ->schema([
                                TextInput::make('title')->label('タイトル'),
                                Radio::make('type')
                                    ->label('ライブタイプ')
                                    ->options([
                                        0 => 'ツアー',
                                        1 => '単発ライブ',
                                        2 => 'イベント',
                                        3 => 'ap bank fes',
                                        4 => 'ソロ',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        switch ($state) {
                                            case 0:
                                                $set('tour_id', '');
                                                break;
                                            case 1:
                                                $set('venue', '');
                                                break;
                                            case 2:
                                                $set('event_id', '');
                                                break;
                                            case 3:
                                                $set('ap_id', '');
                                                break;
                                            case 4:
                                                $set('solo_id', '');
                                                break;
                                            default:
                                                // デフォルト処理
                                                break;
                                        }
                                    }),
                                Select::make('year')
                                    ->label('年')
                                    ->options(Bio::pluck('year', 'year')),
                            ]),
                        Tabs::make('セットリスト')
                            ->schema([
                                Repeater::make('setlist1')
                                    ->schema([
                                        Select::make('id')
                                            ->label('ID')
                                            ->options(Song::all()->pluck('title', 'id')),
                                        TextInput::make('number')->label('#')->numeric(),
                                        TextInput::make('exception')->label('例外'),
                                    ]),
                                Repeater::make('setlist2')
                                    ->schema([
                                        Select::make('id')
                                            ->label('ID')
                                            ->options(Song::all()->pluck('title', 'id')),
                                        TextInput::make('number')->label('#')->numeric(),
                                        TextInput::make('exception')->label('例外'),
                                    ]),
                                Repeater::make('setlist3')
                                    ->schema([
                                        Select::make('id')
                                            ->label('ID')
                                            ->options(Song::all()->pluck('title', 'id')),
                                        TextInput::make('number')->label('#')->numeric(),
                                        TextInput::make('exception')->label('例外'),
                                    ]),
                            ]),
                        Tabs::make('コメント')
                            ->schema([
                                Textarea::make('schedule')->label('スケジュール')->rows(15),
                                Textarea::make('text')->label('コメント')->rows(15),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('tour_id')->label('ツアーID'),
                TextColumn::make('title')->label('ツアータイトル'),
                TextColumn::make('date1')->date('Y.m.d')->label('開始日'),
                TextColumn::make('date2')->date('Y.m.d')->label('終了日'),
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
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
