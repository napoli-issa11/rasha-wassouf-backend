<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'author',
        'text',
        'status',
        'avatar_color',
    ];

    /**
     * Comment belongs to a Project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for published comments visible publicly.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for pending comments awaiting admin moderation.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
