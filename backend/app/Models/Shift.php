<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id', 'opened_at', 'closed_at',
        'initial_cash', 'expected_cash', 'actual_cash',
        'variance', 'total_sales', 'total_transactions', 'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'initial_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'total_sales' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transactions()
    {
        return $this->hasMany(ShiftTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('closed_at');
    }

    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }

    public function isActive(): bool
    {
        return is_null($this->closed_at);
    }

    public function getDurationAttribute()
    {
        $end = $this->closed_at ?? now();
        return $this->opened_at->diffForHumans($end, true);
    }
}
