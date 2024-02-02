<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeyboardData extends Model
{
    use HasFactory;

    protected $fillable = [
        'password',
        'user_id',
        'press_times',
        'interval_times',
        'array_times',
        'target_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
