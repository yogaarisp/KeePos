<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'recipe_id', 'ingredient_type', 'ingredient_id',
        'quantity', 'unit', 'cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'cost' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        if ($this->ingredient_type === 'gudang') {
            return $this->belongsTo(StokGudang::class, 'ingredient_id');
        }
        return $this->belongsTo(KitchenStock::class, 'ingredient_id');
    }
}
