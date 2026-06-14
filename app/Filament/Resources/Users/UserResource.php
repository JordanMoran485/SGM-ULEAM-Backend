<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Facultad;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Usuario';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('Nombre')
                    ->maxLength(30),
                TextInput::make('lastname')
                    ->required()
                    ->label('Apellido')
                    ->maxLength(30),
                FileUpload::make('profile_photo_path')
                    ->label('Foto de perfil')
                    ->image()
                    ->disk('public')
                    ->directory('profiles')
                    ->imageEditor()
                    ->columnSpanFull(),
                Select::make('facultad_id')
                    ->label('Facultad')
                    ->options(function (): array {
                        $user = auth()->user();

                        if ($user?->isSupervisor() && $user->facultad_id) {
                            return Facultad::query()
                                ->whereKey($user->facultad_id)
                                ->get()
                                ->mapWithKeys(fn (Facultad $facultad): array => [$facultad->id => $facultad->display_name])
                                ->all();
                        }

                        return Facultad::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Facultad $facultad): array => [$facultad->id => $facultad->display_name])
                            ->all();
                    })
                    ->afterStateHydrated(function (Select $component, ?User $record): void {
                        $component->state($record?->facultad_id ?? auth()->user()?->facultad_id);
                    })
                    ->disabled(fn (): bool => auth()->user()?->isSupervisor() ?? false)
                    ->live()
                    ->required(),
                TextInput::make('email')
                    ->label('Correo institucional')
                    ->email()
                    ->rule('regex:/^[A-Za-z0-9._%+-]+@live\.uleam\.edu\.ec$/i')
                    ->validationMessages([
                        'regex' => 'El correo debe pertenecer al dominio @live.uleam.edu.ec.',
                    ])
                    ->required()
                    ->maxLength(30),
                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(15),
                Select::make('roles')
                    ->relationship(
                        'roles',
                        'name',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $roles = auth()->user()?->isSupervisor()
                                ? ['conserje']
                                : ['super_admin', 'supervisor_general', 'supervisor', 'conserje'];

                            return $query->whereIn('name', $roles);
                        }
                    )
                    ->preload()
                    ->searchable()
                    ->live()
                    ->label('Rol de Usuario')
                    ->required()
                    ->rule(function (callable $get, ?User $record) {
                        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $roleId = is_array($value) ? reset($value) : $value;
                            $roleName = Role::query()->find($roleId)?->name;

                            if ($roleName !== 'supervisor') {
                                return;
                            }

                            $facultadId = $get('facultad_id');

                            if (! $facultadId) {
                                $fail('Debes seleccionar una facultad para asignar el rol de supervisor.');

                                return;
                            }

                            $supervisorExists = User::query()
                                ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                ->role('supervisor')
                                ->where('facultad_id', $facultadId)
                                ->exists();

                            if ($supervisorExists) {
                                $fail('Ya existe un supervisor asignado para esta facultad.');
                            }
                        };
                    })
                    ->afterStateUpdated(function (Set $set, $state): void {
                        $roleId = is_array($state) ? reset($state) : $state;
                        $roleName = Role::query()->find($roleId)?->name;

                        if ($roleName !== 'conserje') {
                            $set('tipo_conserje', null);
                        }
                    }),
                Select::make('tipo_conserje')
                    ->label('Tipo de conserje')
                    ->options(User::conserjeTypeOptions())
                    ->visible(function (Get $get): bool {
                        $roleId = $get('roles');
                        $roleId = is_array($roleId) ? reset($roleId) : $roleId;

                        return Role::query()->find($roleId)?->name === 'conserje';
                    })
                    ->required(function (Get $get): bool {
                        $roleId = $get('roles');
                        $roleId = is_array($roleId) ? reset($roleId) : $roleId;

                        return Role::query()->find($roleId)?->name === 'conserje';
                    })
                    ->native(false),
                Toggle::make('active_state')
                    ->label('Estado de cuenta')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-user-minus')
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText('Si se desactiva, el usuario no podrá acceder al sistema ni a la App.')
                    ->default(true),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('profile_photo_path')
                    ->label('Foto de perfil')
                    ->disk('public')
                    ->placeholder('-'),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('lastname')
                    ->label('Apellido')
                    ->placeholder('-'),
                TextEntry::make('facultad.name')
                    ->label('Facultad')
                    ->formatStateUsing(fn ($state, User $record): string => $record->facultad?->display_name ?? $state ?? '-')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Correo institucional')
                    ->placeholder('-'),
                TextEntry::make('roles.name')
                    ->label('Rol de Usuario'),
                TextEntry::make('tipo_conserje')
                    ->label('Tipo de conserje')
                    ->formatStateUsing(fn (?string $state): string => User::conserjeTypeOptions()[$state] ?? '-')
                    ->placeholder('-'),
                IconEntry::make('active_state')
                    ->boolean()
                    ->placeholder('-')
                    ->label('Estado'),
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
                ImageColumn::make('profile_photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->label('Nombre'),
                TextColumn::make('lastname')
                    ->searchable()
                    ->label('Apellido'),
                TextColumn::make('facultad.name')
                    ->formatStateUsing(fn ($state, User $record): string => $record->facultad?->display_name ?? $state ?? '-')
                    ->searchable(['name', 'code'])
                    ->sortable()
                    ->label('Facultad'),
                TextColumn::make('email')
                    ->label('Correo institucional')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rol Usuario')
                    ->searchable(),
                TextColumn::make('tipo_conserje')
                    ->label('Tipo de conserje')
                    ->formatStateUsing(fn (?string $state): string => User::conserjeTypeOptions()[$state] ?? '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'uleam' => 'info',
                        'ep' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('active_state')
                    ->boolean()
                    ->label('Estado'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creado en'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizado en'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => ! (auth()->user()?->isSupervisorGeneral() ?? false)),
                DeleteAction::make()
                    ->visible(fn (): bool => ! (auth()->user()?->isSupervisorGeneral() ?? false)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['facultad', 'roles'])
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', ['super_admin', 'supervisor_general', 'supervisor', 'conserje']));

        $user = auth()->user();

        if ($user?->isSupervisor()) {
            $query->role('conserje')->visibleTo($user);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isSupervisorGeneral() || $user?->isSupervisor() || false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isSupervisor() || false;
    }
}
