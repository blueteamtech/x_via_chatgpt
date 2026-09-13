<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'tool', 'success', 'duration_ms', 'error'])]
class ToolInvocation extends Model
{
    public $timestamps = false;

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];
}
