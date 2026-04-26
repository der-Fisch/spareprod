<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_number',
        'phone_number',
        'birth_date',
        'gender',
        'nomor_whatsapp',
        'tanggal_lahir',
        'jenis_kelamin',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNomorWhatsappAttribute(): ?string
    {
        return $this->attributes['whatsapp_number'] ?? $this->attributes['phone_number'] ?? null;
    }

    public function setNomorWhatsappAttribute(?string $value): void
    {
        $this->attributes['whatsapp_number'] = $value;
        $this->attributes['phone_number'] = $value;
    }

    public function getTanggalLahirAttribute(): mixed
    {
        return $this->birth_date;
    }

    public function setTanggalLahirAttribute(mixed $value): void
    {
        $this->attributes['birth_date'] = $value;
    }

    public function getJenisKelaminAttribute(): ?string
    {
        return $this->attributes['gender'] ?? null;
    }

    public function setJenisKelaminAttribute(?string $value): void
    {
        $this->attributes['gender'] = $value;
    }
}

