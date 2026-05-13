<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenStock extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant;

    protected $table = 'stock_dapur';

    protected $fillable = [
        'tenant_id',
        'name', 'manual_item_name', 'category_id', 'warehouse_item_id',
        'stock', 'unit', 'cost_price', 'min_stock', 'notes', 'is_manual',
    ];

    protected $casts = [
        'stock' => 'decimal:4',
        'min_stock' => 'decimal:4',
        'cost_price' => 'decimal:2',
        'is_manual' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function warehouseItem()
    {
        return $this->belongsTo(StokGudang::class, 'warehouse_item_id');
    }

    public function transactions()
    {
        return $this->hasMany(KitchenStockTransaction::class, 'kitchen_stock_id');
    }

    public function conversions()
    {
        return $this->hasMany(KitchenUnitConversion::class, 'kitchen_item_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock');
    }

    public function getDisplayNameAttribute()
    {
        return $this->is_manual ? ($this->manual_item_name ?: $this->name) : $this->name;
    }
}
