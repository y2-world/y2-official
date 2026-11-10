<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueAliasResource\Pages;
use App\Models\VenueAlias;
use App\Support\AdminData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// 会場の別名（改称する前の名前・書き方の違い => 今の会場名）。Database の会場のページ・会場検索・stats の Top Venues で同じ会場として数える。
// 建て替えた会場は別の会場なので入れない。公式の管理者だけ
class VenueAliasResource extends Resource
{
    protected static ?string $model = VenueAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'General';

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationLabel = '会場の別名';

    protected static ?string $modelLabel = '会場の別名';

    public static function canAccess(): bool
    {
        return !AdminData::isMember();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('alias')
                ->label('前の名前・別の書き方')
                ->helperText('スケジュールに書いてある会場名（うしろのかっこ書きは外す）')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\TextInput::make('venue')
                ->label('今の会場名')
                ->required()
                ->maxLength(255)
                ->different('alias'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alias')->label('前の名前・別の書き方')->searchable()->sortable()->wrap(),
                Tables\Columns\TextColumn::make('venue')->label('今の会場名')->searchable()->sortable()->wrap(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('venue')
            ->paginated([25, 50, 100, 'all']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVenueAliases::route('/'),
        ];
    }
}
