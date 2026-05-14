<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'external_id',
        'plan',
        'amount',
        'months',
        'status',
        'payment_method',
        'payment_token',
        'payment_url',
        'payment_proof_path',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['payment_proof_url'];

    protected $hidden = ['payment_proof_path'];

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
