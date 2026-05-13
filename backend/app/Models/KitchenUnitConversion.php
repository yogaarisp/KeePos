<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class KitchenUnitConversion extends Model
{
    use BelongsToTenant;
    protected $table = 'kitchen_unit_conversions';

    protected $fillable = [
        'tenant_id',
        'kitchen_item_id', 'base_unit', 'convert_to_unit', 'ratio',
    ];

    protected $casts = [
        'ratio' => 'decimal:3',
    ];

    public function kitchenItem()
    {
        return $this->belongsTo(KitchenStock::class, 'kitchen_item_id');
    }
}
