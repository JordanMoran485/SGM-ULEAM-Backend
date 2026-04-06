<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Resources\Incidents\Pages\ManageIncidents;
use App\Models\Incidents;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncidentsResource extends Resource
{
    protected static ?string $model = Incidents::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Incidente';
    protected static ?string $recordTitleAttribute = 'Incidents';
    protected static ?string $navigationLabel = 'Incidentes';
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->label('Título'),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('Descripción'),
                FileUpload::make('image')
                    ->image()
                    ->label('Imagen'),
                TextInput::make('location')
                    ->required()
                    ->label('Ubicación'),
                Select::make('status')
                    ->options(['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completado'])
                    ->default('pending')
                    ->required()
                    ->label('Estado'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Título'),
                TextEntry::make('description')
                    ->columnSpanFull()
                    ->label('Descripción'),
                ImageEntry::make('image')
                    ->placeholder('-')
                    ->label('Imagen'),
                TextEntry::make('location')
                    ->label('Ubicación'),
               TextEntry::make('status')
                ->label('Estado')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => 'Pendiente',
                    'in_progress' => 'En progreso',
                    'completed' => 'Completado',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'danger',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                    default => 'gray',
                }),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('Creado en '),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('Actualizado en '),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Incidents')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                     ->label('Título'),
                ImageColumn::make('image')
                 ->label('Imagen'),
                TextColumn::make('location')
                    ->searchable()
                     ->label('Ubicación'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed' => 'Completado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'danger',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIncidents::route('/'),
        ];
    }
}
