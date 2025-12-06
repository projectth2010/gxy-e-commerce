<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'session_id',
        'event_name',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
