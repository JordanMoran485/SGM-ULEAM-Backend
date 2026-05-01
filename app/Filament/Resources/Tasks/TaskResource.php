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
    protected static ?string $modelLabel = 'Tarea';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->label('Título')
                    ->maxLength(15),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Descripción')
                    ->maxLength(15)
                    ->required(),
                Select::make('user_id')
                    ->label('Asignar a Conserje')
                    ->relationship('user', 'name') 
                    ->searchable() 
                    ->preload() 
                    ->required(),
                Select::make('status')
                    ->label('Estado de la Tarea')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ])
                    ->default('Pendiente')
                    ->required(),
                Select::make('priority')
                    ->preload() 
                    ->label('Prioridad')
                    ->options([
                        'Baja' => 'Baja',
                        'Media' => 'Media',
                        'Alta' => 'Alta',
                    ])
                     ->default('Media')
                     ->required(),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->required()
                    ->maxLength(15),
                DatePicker::make('due_date')
                    ->label('Fecha de entrega')
                    ->required()
                    ->displayFormat('d/m/Y') 
                    ->native(false)
                    ->closeOnDateSelection() 
                    ->prefixIcon('heroicon-m-calendar-days'),
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
                TextEntry::make('priority')
                    ->label('Prioridad'),
                TextEntry::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                    }),
                    TextEntry::make('location')
                    ->placeholder('-')
                    ->label('Ubicación'),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-')
                    ->label('Fecha de entrega'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->label('Título'),
                TextColumn::make('description')
                    ->label('Descripción')
                     ->searchable(),
                TextColumn::make('user.email')
                    ->label('Asignado a')
                     ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                     ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                     ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                    }),
                TextColumn::make('location')
                    ->searchable()
                    ->label('Ubicación'),
                TextColumn::make('due_date')
                    ->label('Fecha de entrega')
                    ->date('d/m/Y') 
                    ->sortable() 
                    ->searchable(),
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
