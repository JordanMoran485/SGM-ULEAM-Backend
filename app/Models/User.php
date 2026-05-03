<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    use HasRoles;

    public const SYSTEM_ROLES = [
        'super_admin',
        'admin',
        'supervisor',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'carrera_id',
        'password',
        'active_state',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function conserjeOptions(): array
    {
        return static::queryConserjes()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function queryConserjes(): Builder
    {
        $query = static::query()->where('active_state', true);
        $guardName = (new static())->getDefaultGuardName();

        if (Role::query()->where('name', 'conserje')->where('guard_name', $guardName)->exists()) {
            $query->role('conserje');
        }

        return $query;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessSystem();
    }

    public function canAccessSystem(): bool
    {
        return $this->active_state
            && $this->hasAnyRole(self::SYSTEM_ROLES);
    }

    public function getSystemAccessDenialMessage(): ?string
    {
        if (! $this->active_state) {
            return 'Tu cuenta de la Uleam está desactivada. Por favor, contacta a soporte técnico.';
        }

        if ($this->hasRole('conserje')) {
            return 'Tu cuenta tiene el rol de conserje y no puede iniciar sesión en este panel.';
        }

        if (! $this->hasAnyRole(self::SYSTEM_ROLES)) {
            return 'Tu cuenta no tiene permisos para acceder al sistema.';
        }

        return null;
    }


    public function carrera() { return $this->belongsTo(Carrera::class); }
}
