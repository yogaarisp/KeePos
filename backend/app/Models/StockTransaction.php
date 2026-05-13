<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'item_id', 'type', 'quantity', 'supplier_id', 'purchase_price',
        'stock_before', 'stock_after', 'notes', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'purchase_price' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StokGudang::class, 'item_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
