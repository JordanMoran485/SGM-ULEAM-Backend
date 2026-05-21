<?php

namespace App\Filament\Resources\Facultades;

use App\Filament\Resources\Facultades\Pages\ManageFacultades;
use App\Models\Facultad;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacultadResource extends Resource
{
    protected static ?string $model = Facultad::class;

    protected static ?string $modelLabel = 'Facultad';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Código')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20)
                ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(trim($state)) : null)
                ->placeholder('B-16'),
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->placeholder('Ciencias de la Vida'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Identificador')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFacultades::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() || false;
    }
}
