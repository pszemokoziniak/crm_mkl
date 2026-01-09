<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolFile extends Model
{
    protected $fillable = [
        'type',
        'filename',
        'tool_id',
    ];
}
