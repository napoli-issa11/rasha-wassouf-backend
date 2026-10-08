<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInquiry extends Model
{
    use HasFactory;

    protected $table = 'project_inquiries';

    /**
     * Mass assignment protection: Only these fields can be mass assigned.
     */
    protected $fillable = [
        'full_name',
        'email',
        'telephone',
        'project_category',
        'project_vision',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for querying unread/new inquiries.
     */
    public function scopeNewInquiries($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for sorting by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
