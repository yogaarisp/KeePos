<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokGudang extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant;

    protected $table = 'stock_gudang';

    protected $fillable = [
        'tenant_id',
        'name', 'category_id', 'default_supplier_id', 'stock', 'unit',
        'price_per_unit', 'min_stock', 'notes',
    ];

    protected $casts = [
        'stock' => 'decimal:4',
        'price_per_unit' => 'decimal:2',
        'min_stock' => 'decimal:4',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_id');
    }

    public function unitConversions()
    {
        return $this->hasMany(UnitConversion::class, 'item_id');
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock');
    }

    // Accessors
    public function getTotalValueAttribute()
    {
        return $this->stock * $this->price_per_unit;
    }
}
