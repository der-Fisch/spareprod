<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
    ];

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }
}
