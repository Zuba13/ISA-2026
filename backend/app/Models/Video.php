<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'thumbnail_path',
        'video_path',
        'views',
        'tags',
        'location'
    ];

    protected $casts = [
        'tags' => 'array'
    ];

    protected $appends = ['video_url', 'thumbnail'];

    public function getThumbnailAttribute(): string
    {
        $path = $this->thumbnail_path;
        if (!$path) return '';

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/api/videos/' . $this->id . '/thumbnail');
    }

    public function getVideoUrlAttribute(): string
    {
        $path = $this->video_path;

        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Otherwise, build the streaming URL
        return url('/api/videos/' . $this->id . '/stream');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}
