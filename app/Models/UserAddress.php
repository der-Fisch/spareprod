<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_checkout_id',
        'label',
        'recipient_name',
        'phone_number',
        'type',
        'street',
        'city',
        'state',
        'zipcode',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserCheckout::class, 'user_checkout_id');
    }

    public function getAddressAttribute(): string
    {
        return "{$this->street}, {$this->city}, {$this->state} {$this->zipcode}";
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: 'Alamat';
    }
}
