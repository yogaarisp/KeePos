<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionTransaction extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'production_recipe_id', 'quantity_produced',
        'total_cost', 'user_id', 'notes',
    ];

    protected $casts = [
        'quantity_produced' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function productionRecipe()
    {
        return $this->belongsTo(ProductionRecipe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
