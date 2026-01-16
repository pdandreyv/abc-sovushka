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
        'rank',
    ];

    protected $casts = [
        'description' => 'array', // Преобразуем JSON в массив
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

    /**
     * Получить текстовое описание для поиска (извлекает текст из hypertext)
     */
    public function getDescriptionTextAttribute(): string
    {
        if (empty($this->description)) {
            return '';
        }

        // Если description - это массив (JSON), извлекаем текстовое содержимое
        if (is_array($this->description)) {
            $text = '';
            foreach ($this->description as $block) {
                if (isset($block['type']) && is_array($block)) {
                    if ($block['type'] === 'html' && isset($block['content'])) {
                        // Убираем HTML теги для поиска
                        $content = is_string($block['content']) ? $block['content'] : '';
                        $text .= strip_tags($content) . ' ';
                    } elseif ($block['type'] === 'img' && isset($block['content'])) {
                        $content = is_string($block['content']) ? $block['content'] : '';
                        $text .= strip_tags($content) . ' ';
                    } elseif ($block['type'] === 'video' && isset($block['content'])) {
                        $content = is_string($block['content']) ? $block['content'] : '';
                        $text .= strip_tags($content) . ' ';
                    }
                }
            }
            return trim($text);
        }

        // Если это обычный текст (строка), возвращаем как есть
        if (is_string($this->description)) {
            return $this->description;
        }

        return '';
    }

    /**
     * Получить отформатированное описание (для hypertext)
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if (empty($this->description)) {
            return '';
        }

        // Если description - это массив (JSON), обрабатываем как hypertext
        if (is_array($this->description)) {
            $content = '';
            foreach ($this->description as $k => $block) {
                if (isset($block['type'])) {
                    if ($block['type'] === 'html' && isset($block['content'])) {
                        $content .= $block['content'];
                    } elseif ($block['type'] === 'img' && isset($block['img'])) {
                        $imgPath = asset('files/ideas/' . $this->id . '/description/' . $k . '/img/' . $block['img']);
                        $content .= '<div class="img__wrapper">';
                        $content .= '<img src="' . $imgPath . '" alt="" />';
                        if (isset($block['content']) && !empty($block['content'])) {
                            $content .= '<p>' . $block['content'] . '</p>';
                        }
                        $content .= '</div>';
                    } elseif ($block['type'] === 'video' && isset($block['content'])) {
                        $content .= '<div class="video__wrapper">' . $block['content'] . '</div>';
                    } elseif ($block['type'] === 'images' && isset($block['images'])) {
                        $content .= '<div class="images__wrapper">';
                        foreach ($block['images'] as $imgKey => $img) {
                            if (isset($img['file'])) {
                                $imgPath = asset('files/ideas/' . $this->id . '/description/' . $k . '_' . $imgKey . '/img/' . $img['file']);
                                $content .= '<img src="' . $imgPath . '" alt="" />';
                            }
                        }
                        $content .= '</div>';
                    }
                }
            }
            return $content;
        }

        // Если это обычный текст, возвращаем как есть
        return nl2br(e($this->description));
    }
}
