<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'creator_id', 'current_video_id', 'token'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function currentVideo()
    {
        return $this->belongsTo(Video::class, 'current_video_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
