<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'threads_url',
        'pinterest_url',
        'show_facebook',
        'show_instagram',
        'show_linkedin',
        'show_threads',
        'show_pinterest',
        'inquiry_settings',
        'hero_rotation_speed',
    ];

    protected $casts = [
        'hero_rotation_speed' => 'integer',
        'show_facebook' => 'boolean',
        'show_instagram' => 'boolean',
        'show_linkedin' => 'boolean',
        'show_threads' => 'boolean',
        'show_pinterest' => 'boolean',
        'inquiry_settings' => 'array',
    ];
}
