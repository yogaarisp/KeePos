<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name', 'description', 'product_id', 'hpp',
        'selling_price', 'type', 'is_active',
    ];

    protected $casts = [
        'hpp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function productionRecipe()
    {
        return $this->hasOne(ProductionRecipe::class);
    }

    public function getProfitAttribute()
    {
        return $this->selling_price - $this->hpp;
    }

    public function getMarginAttribute()
    {
        if ($this->selling_price <= 0) return 0;
        return round(($this->profit / $this->selling_price) * 100, 2);
    }
}
