<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->recordActivity('created');
        });

        static::updated(function ($model) {
            $model->recordActivity('updated');
        });

        static::deleted(function ($model) {
            $model->recordActivity('deleted');
        });
    }

    protected function recordActivity($event)
    {
        $tenantId = config('app.current_tenant_id') ?: (Auth::check() ? Auth::user()->tenant_id : null);
        
        if (!$tenantId && property_exists($this, 'tenant_id')) {
            $tenantId = $this->tenant_id;
        }

        if (!$tenantId) {
            return; // Don't log if we can't identify the tenant
        }

        $oldValues = $event === 'updated' ? array_intersect_key($this->getRawOriginal(), $this->getDirty()) : null;
        $newValues = $event === 'updated' ? $this->getDirty() : ($event === 'created' ? $this->getAttributes() : null);

        // Security: Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'access_token', 'google_service_account_json'];
        if ($oldValues) $oldValues = array_diff_key($oldValues, array_flip($sensitiveFields));
        if ($newValues) $newValues = array_diff_key($newValues, array_flip($sensitiveFields));

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
