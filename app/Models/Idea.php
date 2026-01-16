<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Idea extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'pdf_file',
        'zip_file',
        'likes',
    ];

    /**
     * Пользователи, которые лайкнули эту идею
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_likes')
            ->withTimestamps();
    }

    /**
     * Проверить, лайкнул ли пользователь эту идею
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likedByUsers()->where('user_id', $userId)->exists();
    }
}
