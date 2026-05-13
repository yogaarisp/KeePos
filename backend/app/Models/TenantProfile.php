<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'shop_name',
        'shop_logo',
        'shop_favicon',
        'shop_tagline',
        'shop_address',
        'shop_phone',
        'shop_email',
        'business_hours',
        'social_media',
        'primary_color',
        'secondary_color',
        'receipt_header',
        'receipt_footer',
        'show_logo_on_receipt',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'social_media' => 'array',
        'show_logo_on_receipt' => 'boolean',
    ];

    /**
     * Relationship to Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get full logo URL
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->shop_logo) {
            return null;
        }
        
        return asset('storage/' . $this->shop_logo);
    }

    /**
     * Get full favicon URL
     */
    public function getFaviconUrlAttribute()
    {
        if (!$this->shop_favicon) {
            return null;
        }
        
        return asset('storage/' . $this->shop_favicon);
    }
}
