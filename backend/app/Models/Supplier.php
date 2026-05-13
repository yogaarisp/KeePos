<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    /**
     * Items that have this supplier as default.
     */
    public function stockItems()
    {
        return $this->hasMany(StokGudang::class, 'default_supplier_id');
    }

    /**
     * Transactions associated with this supplier.
     */
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'supplier_id');
    }
}
