<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenStockTransaction extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'stock_dapur_transactions';

    protected $fillable = [
        'tenant_id',
        'kitchen_stock_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'source_gudang_id',
        'notes', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
    ];

    public function kitchenStock()
    {
        return $this->belongsTo(KitchenStock::class);
    }

    public function sourceGudang()
    {
        return $this->belongsTo(StokGudang::class, 'source_gudang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
