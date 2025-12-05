<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
        'primary_domain',
        'plan_id',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
