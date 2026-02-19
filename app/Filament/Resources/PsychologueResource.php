<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PsychologueResource\Pages;
use App\Filament\Resources\PsychologueResource\RelationManagers;
use App\Models\Psychologue;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class PsychologueResource extends Resource
{
    protected static ?string $model = Psychologue::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Psychologues';
    
    protected static ?string $pluralLabel = 'Psychologues';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de connexion')
                    ->description('Créer le compte utilisateur pour le psychologue')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email de connexion')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Minimum 8 caractères. Laissez vide pour ne pas changer (modification uniquement)'),
                        Forms\Components\TextInput::make('telephone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Informations professionnelles')
                    ->schema([
                        Forms\Components\TextInput::make('numero_ordre')
                            ->label('Numéro d\'ordre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('diplome')
                            ->label('Diplôme')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('etablissement')
                            ->label('Établissement')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('annees_experience')
                            ->label('Années d\'expérience')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),

                Forms\Components\Section::make('Spécialités et bio')
                    ->schema([
                        Forms\Components\TagsInput::make('specialites')
                            ->label('Spécialités')
                            ->placeholder('Appuyez sur Entrée pour ajouter')
                            ->helperText('Ex: Anxiété, Dépression, Stress académique...')
                            ->suggestions([
                                'Anxiété',
                                'Dépression',
                                'Stress académique',
                                'Troubles du sommeil',
                                'Gestion des émotions',
                                'Confiance en soi',
                                'Relations interpersonnelles',
                                'Troubles alimentaires',
                                'Addiction',
                                'Traumatisme',
                            ]),
                        Forms\Components\Textarea::make('bio')
                            ->label('Biographie')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Présentation professionnelle visible par les étudiants'),
                    ]),

                Forms\Components\Section::make('Disponibilité')
                    ->schema([
                        Forms\Components\Toggle::make('disponible')
                            ->label('Disponible pour consultations')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Toggle::make('urgence')
                            ->label('Accepte les urgences')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Statistiques')
                    ->description('Ces champs sont calculés automatiquement')
                    ->schema([
                        Forms\Components\TextInput::make('note_moyenne')
                            ->label('Note moyenne')
                            ->numeric()
                            ->default(0.00)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_consultations')
                            ->label('Total consultations')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('numero_ordre')
                    ->label('N° ordre')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('diplome')
                    ->label('Diplôme')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('annees_experience')
                    ->label('Expérience')
                    ->suffix(' ans')
                    ->sortable(),
                Tables\Columns\IconColumn::make('disponible')
                    ->label('Disponible')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('urgence')
                    ->label('Urgence')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('note_moyenne')
                    ->label('Note')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('/5')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_consultations')
                    ->label('Consultations')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('disponible')
                    ->label('Disponible')
                    ->boolean()
                    ->trueLabel('Disponibles uniquement')
                    ->falseLabel('Indisponibles uniquement')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('urgence')
                    ->label('Urgence')
                    ->boolean()
                    ->trueLabel('Accepte les urgences')
                    ->falseLabel('N\'accepte pas les urgences')
                    ->native(false),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPsychologues::route('/'),
            'create' => Pages\CreatePsychologue::route('/create'),
            'edit' => Pages\EditPsychologue::route('/{record}/edit'),
        ];
    }
}
