<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * Boot the trait to add a global scope and observers.
     */
    protected static function bootBelongsToTenant()
    {
        // 1. Global Scope: Otomatis filter data berdasarkan tenant_id yang aktif
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = config('app.current_tenant_id');
            
            if ($tenantId) {
                // Gunakan table name agar tidak ambigu saat JOIN
                $builder->where($builder->getQuery()->from . '.tenant_id', $tenantId);
            } else {
                // Jika tidak ada tenant yang teridentifikasi, default ke platform level (tenant_id = null)
                // Ini penting untuk keamanan agar data tidak bocor antar tenant.
                $builder->whereNull($builder->getQuery()->from . '.tenant_id');
            }
        });

        // 2. Creating Observer: Otomatis isi tenant_id saat create data baru
        static::creating(function ($model) {
            $tenantId = config('app.current_tenant_id');
            if ($tenantId && !$model->tenant_id) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    /**
     * Relationship ke Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
