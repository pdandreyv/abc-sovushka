<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'user_code',
        'email',
        'password',
        'phone',
        'role',
        'city',
        'organization',
        'about',
        'social_id',
        'social_provider',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->last_name, $this->first_name, $this->middle_name]);
        return implode(' ', $parts);
    }

    /**
     * Идентификатор пользователя в формате YYYYMMDD + id
     */
    public function getUserCodeAttribute(): string
    {
        if (!$this->id || !$this->created_at) {
            return '';
        }

        return $this->created_at->format('Ymd') . $this->id;
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $raw = $user->getRawOriginal('user_code');
            if ($raw === null || $raw === '') {
                $user->updateQuietly([
                    'user_code' => $user->created_at->format('Ymd') . $user->id,
                ]);
            }
        });
    }

    /**
     * Идеи, которые пользователь лайкнул
     */
    public function likedIdeas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_likes')
            ->withTimestamps();
    }

    public function socials(): HasMany
    {
        return $this->hasMany(UserSocial::class, 'user', 'id');
    }
}
