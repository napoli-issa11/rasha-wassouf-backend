<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_name',
        'title',
        'category',
        'description',
        'cover_image',
        'images',
        'likes',
    ];

    protected $casts = [
        'images' => 'array',
        'likes' => 'integer',
    ];

    /**
     * The accessors to append to the model's array and JSON form.
     */
    protected $appends = [
        'coverImage',
        'folderName',
    ];

    public function getCoverImageAttribute($value = null): ?string
    {
        return $value ?? ($this->attributes['cover_image'] ?? null);
    }

    public function getFolderNameAttribute($value = null): ?string
    {
        return $value ?? ($this->attributes['folder_name'] ?? ($this->id ? "project {$this->id}" : null));
    }

    /**
     * Get all comments for the project.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get only approved/published comments (for public view).
     */
    public function publishedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'published');
    }

    /**
     * Get pending comments (for Admin moderation).
     */
    public function pendingComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'pending');
    }
}
