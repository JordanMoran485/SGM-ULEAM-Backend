<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Models\Facultad;
use App\Models\Carrera; 
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Usuario';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
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
                 Select::make('facultad_id')
                    ->label('Facultad')
                    ->options(Facultad::all()->pluck('name', 'id'))
                    ->live() 
                    ->afterStateUpdated(fn (callable $set) => $set('carrera_id', null))
                     ->required(),

                Select::make('carrera_id')
                    ->label('Carrera')
                    ->options(function (callable $get) {
                        $facultadId = $get('facultad_id');
                        if (!$facultadId) {
                            return [];
                        }
                        return Carrera::where('facultad_id', $facultadId)->pluck('name', 'id');
                    })
                    ->required(),
                TextInput::make('email')
                    ->label('Correo institucional')
                    ->email()
                    ->required()
                    ->maxLength(30),
                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->required()
                    ->maxLength(15),
                Select::make('roles')
                    ->relationship('roles', 'name') 
                    ->preload()
                    ->searchable()
                    ->label('Rol de Usuario')
                     ->required(),
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
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('lastname')
                    ->label('Apellido')
                    ->placeholder('-'),
                TextEntry::make('carrera.facultad.name')
                    ->label('Facultad')
                    ->placeholder('-'),
                TextEntry::make('carrera.name')
                    ->label('Carrera')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Correo institucional')
                    ->placeholder('-'),
                TextEntry::make('roles.name')
                    ->label('Rol de Usuario'),
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
                TextColumn::make('name')
                    ->searchable()
                    ->label('Nombre'),
                TextColumn::make('lastname')
                    ->searchable()
                    ->label('Apellido'),
                TextColumn::make('carrera.facultad.name')
                    ->searchable()
                     ->sortable()
                    ->label('Facultad'),
                TextColumn::make('carrera.name')
                    ->searchable()
                     ->sortable()
                    ->label('Carrera'),
                TextColumn::make('email')
                    ->label('Correo institucional')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rol Usuario')
                     ->searchable(),
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
