<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'employee_id', 'date',
        'check_in', 'check_out', 'status', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getWorkHoursAttribute(): ?float
    {
        if (!$this->check_in || !$this->check_out) return null;
        $in  = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_in);
        $out = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_out);
        return round($out->diffInMinutes($in) / 60, 2);
    }
}
