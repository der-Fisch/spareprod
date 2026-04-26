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
        'nama_penerima',
        'nomor_whatsapp',
        'tipe',
        'nama_jalan',
        'nama_kota',
        'negara',
        'kode_pos',
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
        return "{$this->nama_jalan}, {$this->nama_kota}, {$this->negara} {$this->kode_pos}";
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: 'Alamat';
    }

    public function getNamaPenerimaAttribute(): ?string
    {
        return $this->attributes['recipient_name'] ?? null;
    }

    public function setNamaPenerimaAttribute(?string $value): void
    {
        $this->attributes['recipient_name'] = $value;
    }

    public function getNomorWhatsappAttribute(): ?string
    {
        return $this->attributes['phone_number'] ?? null;
    }

    public function setNomorWhatsappAttribute(?string $value): void
    {
        $this->attributes['phone_number'] = $value;
    }

    public function getTipeAttribute(): ?string
    {
        return $this->attributes['type'] ?? null;
    }

    public function setTipeAttribute(?string $value): void
    {
        $this->attributes['type'] = $value;
    }

    public function getNamaJalanAttribute(): ?string
    {
        return $this->attributes['street'] ?? null;
    }

    public function setNamaJalanAttribute(?string $value): void
    {
        $this->attributes['street'] = $value;
    }

    public function getNamaKotaAttribute(): ?string
    {
        return $this->attributes['city'] ?? null;
    }

    public function setNamaKotaAttribute(?string $value): void
    {
        $this->attributes['city'] = $value;
    }

    public function getNegaraAttribute(): ?string
    {
        return $this->attributes['state'] ?? null;
    }

    public function setNegaraAttribute(?string $value): void
    {
        $this->attributes['state'] = $value;
    }

    public function getKodePosAttribute(): ?string
    {
        return $this->attributes['zipcode'] ?? null;
    }

    public function setKodePosAttribute(?string $value): void
    {
        $this->attributes['zipcode'] = $value;
    }
}

