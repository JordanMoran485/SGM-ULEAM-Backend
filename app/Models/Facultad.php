<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Facultad extends Model
{
    protected $fillable = ['code', 'name'];
    protected $table = 'facultades';

    protected $appends = [
        'display_name',
    ];

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value ? strtoupper(trim($value)) : null,
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(implode(' - ', array_filter([$this->code, $this->name]))),
        );
    }

    public function supervisor(): HasOne
    {
        return $this->hasOne(User::class, 'facultad_id')
            ->whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))
            ->where('active_state', true);
    }
}
