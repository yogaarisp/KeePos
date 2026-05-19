<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteReport extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'source_type', 'source_id', 'item_name',
        'quantity', 'unit', 'cost_per_unit', 'estimated_loss',
        'reason', 'notes', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'estimated_loss' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedLossAttribute()
    {
        return 'Rp ' . number_format($this->estimated_loss, 0, ',', '.');
    }

    public function getFormattedCostAttribute()
    {
        return 'Rp ' . number_format($this->cost_per_unit, 0, ',', '.');
    }
}
