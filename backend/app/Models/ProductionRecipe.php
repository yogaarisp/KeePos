<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionRecipe extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'recipe_id', 'output_quantity', 'output_unit', 'output_kitchen_stock_id',
    ];

    protected $casts = [
        'output_quantity' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function outputKitchenStock()
    {
        return $this->belongsTo(KitchenStock::class, 'output_kitchen_stock_id');
    }

    public function transactions()
    {
        return $this->hasMany(ProductionTransaction::class);
    }
}
