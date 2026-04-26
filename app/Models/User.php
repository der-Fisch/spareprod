<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'first_name',
        'last_name',
        'nama_depan',
        'nama_belakang',
        'is_active',
        'is_staff',
        'role',
        'date_joined',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_staff' => 'boolean',
            'date_joined' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('role')) {
                $user->is_staff = $user->role === 'admin';
            } elseif ($user->isDirty('is_staff') || ! filled($user->role)) {
                $user->role = $user->is_staff ? 'admin' : 'customer';
            }

            if (! $user->date_joined) {
                $user->date_joined = $user->created_at ?? now();
            }
        });
    }

    public function accountProfile(): HasOne
    {
        return $this->hasOne(AccountProfile::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function checkoutProfile(): HasOne
    {
        return $this->hasOne(UserCheckout::class);
    }

    public function getNameAttribute(): string
    {
        return trim($this->nama_depan . ' ' . $this->nama_belakang) ?: $this->username;
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role === 'admin' ? 'Admin' : 'Customer';
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public function getNamaDepanAttribute(): ?string
    {
        return $this->attributes['first_name'] ?? null;
    }

    public function setNamaDepanAttribute(?string $value): void
    {
        $this->attributes['first_name'] = $value;
    }

    public function getNamaBelakangAttribute(): ?string
    {
        return $this->attributes['last_name'] ?? null;
    }

    public function setNamaBelakangAttribute(?string $value): void
    {
        $this->attributes['last_name'] = $value;
    }
}

