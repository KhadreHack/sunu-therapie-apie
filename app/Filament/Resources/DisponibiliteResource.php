<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisponibiliteResource\Pages;
use App\Filament\Resources\DisponibiliteResource\RelationManagers;
use App\Models\Disponibilite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisponibiliteResource extends Resource
{
    protected static ?string $model = Disponibilite::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('psychologue_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('jour_semaine')
                    ->required(),
                Forms\Components\TextInput::make('heure_debut')
                    ->required(),
                Forms\Components\TextInput::make('heure_fin')
                    ->required(),
                Forms\Components\Toggle::make('actif')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('psychologue_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jour_semaine'),
                Tables\Columns\TextColumn::make('heure_debut'),
                Tables\Columns\TextColumn::make('heure_fin'),
                Tables\Columns\IconColumn::make('actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListDisponibilites::route('/'),
            'create' => Pages\CreateDisponibilite::route('/create'),
            'edit' => Pages\EditDisponibilite::route('/{record}/edit'),
        ];
    }
}
