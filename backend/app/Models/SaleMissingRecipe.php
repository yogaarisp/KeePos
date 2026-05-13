<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class SaleMissingRecipe extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'sale_id', 'product_id', 'product_name', 'quantity',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
