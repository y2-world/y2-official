<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExternalUserResource\Pages;
use App\Filament\Resources\ExternalUserResource\RelationManagers;
use App\Models\ExternalUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExternalUserResource extends Resource
{
    protected static ?string $model = ExternalUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Setlists';

    protected static ?string $navigationLabel = '外部ユーザー';

    protected static ?string $modelLabel = '外部ユーザー';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('名前'),
                TextInput::make('email')->label('メールアドレス')->email(),
                // 「Yuki本人」のアカウントに立てるフラグ。/database配下の曲詳細ページで、
                // ログイン中の外部ユーザーがYuki本人ならYuki's Live Attendances（セットリスト
                // サイト全体の記録）を、それ以外の一般ユーザーならMy Live Attendances（自分の
                // 参加記録）を出し分けるために使う（DbSongController::show参照）。
                Toggle::make('is_yuki')
                    ->label('Yuki本人のアカウント')
                    ->helperText('ONにすると、このアカウントでログイン中はDatabase曲詳細ページで自分の参戦記録の代わりにYuki\'s Live Attendances（セットリストサイト全体の記録）が表示されます。')
                    ->onColor('success')
                    ->offColor('gray'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('名前'),
                TextColumn::make('email')->label('メールアドレス'),
                Tables\Columns\ToggleColumn::make('is_yuki')
                    ->label('Yuki本人')
                    ->onColor('success')
                    ->offColor('gray'),
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
            ])
            ->defaultSort('id', 'asc');
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
            'index' => Pages\ListExternalUsers::route('/'),
            'edit' => Pages\EditExternalUser::route('/{record}/edit'),
        ];
    }
}
