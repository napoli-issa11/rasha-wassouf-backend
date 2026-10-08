<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'symbol',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'integer',
        'sort_order' => 'integer',
    ];
}
