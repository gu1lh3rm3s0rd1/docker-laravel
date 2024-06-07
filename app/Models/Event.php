<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'items' => 'array',
    ];

    protected $dates = [
        'date',
    ];

    protected $fillable = [
        'title', 'date', 'city', 'private', 'description', 'items', 'image', 'user_id',
    ];

    protected $guarded = [
        // 'id', 'created_at', 'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    // public function participants()
    {
        return $this->belongsToMany(User::class, 'event_user', 'event_id', 'user_id');
    }
}
