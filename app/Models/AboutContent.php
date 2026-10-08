<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_image',
        'main_title',
        'main_description',
        'secondary_description',
        'quote_text',
        'quote_author',
        'badge_number',
        'badge_label',
        'statistics',
        'pillars',
    ];

    protected $casts = [
        'statistics' => 'array',
        'pillars' => 'array',
    ];
}
