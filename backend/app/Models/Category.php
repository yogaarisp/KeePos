<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stokGudang()
    {
        return $this->hasMany(StokGudang::class);
    }

    public function kitchenStocks()
    {
        return $this->hasMany(KitchenStock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
