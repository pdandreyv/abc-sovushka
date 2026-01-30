<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocial extends Model
{
    protected $table = 'user_socials';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'type',
        'uid',
        'email',
        'login',
        'gender',
        'name',
        'surname',
        'birthday',
        'avatar',
        'link',
        'last_visit',
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }
}
