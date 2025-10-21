<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['key','name','base_url','supports_query','meta','is_active','sort'];
    protected $casts = [
        'supports_query' => 'boolean',
        'is_active'      => 'boolean',
        'meta'           => 'array',
    ];
}
