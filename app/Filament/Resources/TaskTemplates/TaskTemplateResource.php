<?php

namespace App\Filament\Resources\TaskTemplates;

use App\Filament\Resources\TaskTemplates\Pages\CreateTaskTemplate;
use App\Filament\Resources\TaskTemplates\Pages\EditTaskTemplate;
use App\Filament\Resources\TaskTemplates\Pages\ListTaskTemplates;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateItem;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskTemplateResource extends Resource
{
    protected static ?string $model = TaskTemplate::class;

    protected static ?string $modelLabel = 'Plantilla de tarea';

    protected static ?string $pluralModelLabel = 'Plantillas de tareas';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo_conserje')
                ->label('Tipo de conserje')
                ->options(function (): array {
                    $user = auth()->user();

                    if ($user->isSuperAdmin() || $user->isSupervisorGeneral()) {
                        return User::conserjeTypeOptions();
                    }

                    return ['uleam' => 'Uleam'];
                })
                ->placeholder('Todos')
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),

            Select::make('user_id')
                ->label('Conserje')
                ->options(fn (Get $get): array => User::conserjeOptions(tipoConserje: $get('tipo_conserje')))
                ->placeholder(fn (Get $get): string => blank($get('tipo_conserje'))
                    ? 'Selecciona un tipo primero'
                    : 'Selecciona un conserje'
                )
                ->disabled(fn (Get $get): bool => blank($get('tipo_conserje')))
                ->searchable()
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Conserje')
                    ->formatStateUsing(
                        fn ($state, TaskTemplate $record): string =>
                            $record->user?->getDisplayNameWithConserjeType() ?? '—'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days_covered')
                    ->label('Días configurados')
                    ->getStateUsing(
                        fn (TaskTemplate $record): string => $record->days_covered
                    ),

                TextColumn::make('items_count')
                    ->label('Total de tareas')
                    ->getStateUsing(
                        fn (TaskTemplate $record): int => $record->items_count
                    )
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('tipo_conserje')
                    ->label('Tipo de conserje')
                    ->options(function (): array {
                        $user = auth()->user();

                        if ($user->isSuperAdmin() || $user->isSupervisorGeneral()) {
                            return User::conserjeTypeOptions();
                        }

                        return ['uleam' => 'Uleam'];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! filled($value)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'user',
                            fn (Builder $q): Builder => $q->where('tipo_conserje', $value)
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTaskTemplates::route('/'),
            'create' => CreateTaskTemplate::route('/create'),
            'edit'   => EditTaskTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'taskTemplateItems'])
            ->visibleTo(auth()->user());
    }
}
