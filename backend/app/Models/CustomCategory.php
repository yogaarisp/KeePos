<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomCategory extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','name', 'type', 'is_required'];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(CustomOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_custom_categories');
    }
}
