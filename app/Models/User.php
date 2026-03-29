<?php

namespace App\Models;

use App\Domain\Admin\Models\UserProfile;
use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Ventas\Models\Venta;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'first_name', 'last_name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function panelProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function cajasAbiertas(): HasMany
    {
        return $this->hasMany(CajaSesion::class, 'cajero_apertura_id');
    }

    public function cajasCerradas(): HasMany
    {
        return $this->hasMany(CajaSesion::class, 'cajero_cierre_id');
    }

    public function ventasRealizadas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cajero_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");

        if ($fullName !== '') {
            return $fullName;
        }

        return $this->name ?: ($this->username ?: $this->email);
    }
}
