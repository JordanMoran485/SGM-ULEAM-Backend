<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $modelLabel = 'Tareas';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->label('Título'),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Descripción'),
                Select::make('user_id')
                    ->label('Asignar a Conserje')
                    ->relationship('user', 'name') 
                    ->searchable() 
                    ->preload() 
                    ->required(),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Fecha de vencimiento')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                ->label('Título'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->label('Descripción'),
                TextEntry::make('user.name')
                    ->label('Conserje'),
                TextEntry::make('location')
                    ->placeholder('-')
                    ->label('Ubicación'),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-')
                    ->label('Fecha de vencimiento'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('Creado en'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('Actualizado en'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->numeric()
                    ->label('Conserje')
                     ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable()
                    ->label('Ubicación'),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                     ->searchable()
                    ->label('Fecha de vencimiento'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Creado en')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Actualizado en')
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
            'index' => ManageTasks::route('/'),
        ];
    }
}
