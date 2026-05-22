<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'supervisor',
    ];

    public const CONSERJE_TYPES = [
        'uleam' => 'Uleam',
        'ep' => 'Empresa Publica EP',
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
        'facultad_id',
        'profile_photo_path',
        'password',
        'active_state',
        'tipo_conserje',
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

    protected $appends = [
        'profile_photo_url',
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
            'active_state' => 'boolean',
        ];
    }

    public static function conserjeTypeOptions(): array
    {
        return self::CONSERJE_TYPES;
    }

    public static function conserjeOptions(?self $viewer = null, ?string $tipoConserje = null): array
    {
        return static::queryConserjes($viewer, $tipoConserje)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $user): array => [$user->id => $user->getDisplayNameWithConserjeType()])
            ->all();
    }

    public static function queryConserjes(?self $viewer = null, ?string $tipoConserje = null): Builder
    {
        $viewer ??= auth()->user();

        $query = static::query()
            ->with('facultad')
            ->where('active_state', true);
        $guardName = (new static())->getDefaultGuardName();

        if (Role::query()->where('name', 'conserje')->where('guard_name', $guardName)->exists()) {
            $query->role('conserje');
        }

        if (filled($tipoConserje)) {
            $query->where('tipo_conserje', $tipoConserje);
        }

        return $query->visibleTo($viewer);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->lastname}");
    }

    public function getConserjeTypeLabel(): ?string
    {
        return self::CONSERJE_TYPES[$this->tipo_conserje] ?? null;
    }

    public function getDisplayNameWithConserjeType(): string
    {
        $typeLabel = $this->getConserjeTypeLabel();

        return $typeLabel
            ? "{$this->full_name} ({$typeLabel})"
            : $this->full_name;
    }

    public function scopeVisibleTo(Builder $query, ?self $viewer): Builder
    {
        if (! $viewer) {
            return $query;
        }

        if ($viewer->isSuperAdmin()) {
            return $query;
        }

        if ($viewer->isSupervisor()) {
            $facultadId = $viewer->facultad_id;

            if (! $facultadId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('facultad_id', $facultadId);
        }

        return $query->whereKey($viewer->getKey());
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

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
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

    public function facultad(): BelongsTo
    {
        return $this->belongsTo(Facultad::class);
    }

    public function belongsToFacultad(?int $facultadId): bool
    {
        return filled($facultadId) && $this->facultad_id === $facultadId;
    }

    public function belongsToSameFacultadAs(?self $user): bool
    {
        return $this->belongsToFacultad($user?->facultad_id);
    }

    public function canManageUser(self $user): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isSupervisor()
            && $user->hasRole('conserje')
            && $user->belongsToSameFacultadAs($this);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return 'storage/' . ltrim($this->profile_photo_path, '/');
    }
}
