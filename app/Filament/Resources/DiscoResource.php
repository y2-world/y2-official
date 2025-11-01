<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscoResource\Pages;
use App\Filament\Resources\DiscoResource\RelationManagers;
use App\Models\Disco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscoResource extends Resource
{
    protected static ?string $model = Disco::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Official';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'ディスコグラフィー';

    protected static ?string $modelLabel = 'ディスコグラフィー';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('subtitle')
                    ->label('サブタイトル')
                    ->maxLength(191),
                Forms\Components\DatePicker::make('date')
                    ->label('発売日')
                    ->native(false)
                    ->displayFormat('Y.m.d'),
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn() => \App\Models\Lyric::pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('exception')
                            ->label('例外')
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->maxLength(255),
                Forms\Components\TextInput::make('image')
                    ->label('画像')
                    ->maxLength(255),
                Forms\Components\Toggle::make('type')
                    ->label('アルバム')
                    ->onColor('success')
                    ->offColor('gray'),
                Forms\Components\Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->default(0)
                    ->formatStateUsing(fn ($state) => $state == 0)
                    ->dehydrateStateUsing(fn ($state) => $state ? 0 : 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('サブタイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('発売日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('type')
                    ->label('アルバム')
                    ->onColor('success')
                    ->offColor('gray'),
                Tables\Columns\ToggleColumn::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn ($record) => $record->visible == 0) // 0を公開（ON）として表示
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['visible' => $state ? 0 : 1]); // ONを0、OFFを1として保存
                        return $state;
                    }),
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
            'index' => Pages\ListDiscos::route('/'),
            'create' => Pages\CreateDisco::route('/create'),
            'edit' => Pages\EditDisco::route('/{record}/edit'),
        ];
    }
}
