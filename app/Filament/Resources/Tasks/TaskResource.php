<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $modelLabel = 'Tarea';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titulo')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(4)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Imagen de la incidencia')
                    ->image()
                    ->disk('public')
                    ->directory('incidents')
                    ->imageEditor()
                    ->visibility('public')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('Asignar a conserje')
                    ->options(fn (): array => User::conserjeOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->label('Estado de la tarea')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ])
                    ->default('Pendiente')
                    ->required(),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'Baja' => 'Baja',
                        'Media' => 'Media',
                        'Alta' => 'Alta',
                    ])
                    ->default('Media')
                    ->required(),
                TextInput::make('location')
                    ->label('Ubicacion')
                    ->maxLength(255),
                Toggle::make('all_day')
                    ->label('Todo el dia')
                    ->default(true)
                    ->required(),
                DateTimePicker::make('start_at')
                    ->label('Inicio')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Fin')
                    ->native(false)
                    ->seconds(false),
                DatePicker::make('due_date')
                    ->label('Fecha de vencimiento')
                    ->native(false)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image')
                    ->label('Imagen de la incidencia')
                    ->disk('public')
                    ->visibility('public')
                    ->height(320)
                    ->columnSpanFull(),
                TextEntry::make('title')
                    ->label('Titulo'),
                TextEntry::make('description')
                    ->label('Descripcion')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('user.name')
                    ->label('Conserje')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    }),
                TextEntry::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    }),
                TextEntry::make('location')
                    ->label('Ubicacion')
                    ->placeholder('-'),
                TextEntry::make('all_day')
                    ->label('Todo el dia')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Si' : 'No'),
                TextEntry::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(52),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Conserje')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'gray',
                        'En Proceso' => 'warning',
                        'Completada' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alta' => 'danger',
                        'Media' => 'warning',
                        'Baja' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label('Ubicacion')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Completada' => 'Completada',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'Alta' => 'Alta',
                        'Media' => 'Media',
                        'Baja' => 'Baja',
                    ]),
                SelectFilter::make('user_id')
                    ->label('Conserje')
                    ->options(fn (): array => User::conserjeOptions()),
                Filter::make('schedule')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('start_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('start_at', '<=', $data['until']),
                            );
                    }),
                Filter::make('active_only')
                    ->label('Solo activas')
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', 'Completada')),
                Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('status', '!=', 'Completada')
                            ->whereDate('due_date', '<', now()->toDateString())
                    ),
                Filter::make('attention_required')
                    ->label('Atención requerida')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('status', '!=', 'Completada')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereDate('due_date', '<', now()->toDateString())
                                    ->orWhere('priority', 'Alta');
                            })
                    ),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->visibleTo(auth()->user());
    }
}
