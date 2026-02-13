<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'name',
        'video_id',
        'is_active',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
