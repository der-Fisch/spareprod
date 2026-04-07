<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_code',
        'provider_name',
        'method_type',
        'account_name',
        'account_reference',
        'status',
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
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'connected' => 'Tersambung',
            'demo_ready' => 'Aktif',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getMaskedReferenceAttribute(): string
    {
        $reference = trim((string) $this->account_reference);
        if ($reference === '') {
            return 'Belum diatur';
        }

        if (strlen($reference) <= 4) {
            return $reference;
        }

        return str_repeat('*', max(strlen($reference) - 4, 0)) . substr($reference, -4);
    }
}
