<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'default_value',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_feature')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function getFormattedValue($value = null)
    {
        $value = $value ?? $this->default_value;
        
        switch ($this->type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return $value;
        }
    }
}
