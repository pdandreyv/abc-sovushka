<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicMaterial extends Model
{
    protected $fillable = [
        'title',
        'is_blocked',
        'display',
        'rank',
        'subscription_level_id',
        'subject_id',
        'topic_id',
        'text',
        'pdf_file',
        'zip_file',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'display' => 'boolean',
        'rank' => 'integer',
        'text' => 'array',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subscriptionLevel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionLevel::class);
    }

    public function getFormattedTextAttribute(): string
    {
        if (empty($this->text)) {
            return '';
        }

        if (is_array($this->text)) {
            $content = '';
            foreach ($this->text as $k => $block) {
                if (!isset($block['type'])) {
                    continue;
                }

                if ($block['type'] === 'html' && isset($block['content'])) {
                    $content .= $block['content'];
                } elseif ($block['type'] === 'img' && isset($block['img'])) {
                    $imgPath = asset('files/topic_materials/' . $this->id . '/text/' . $k . '/img/' . $block['img']);
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
                            $imgPath = asset('files/topic_materials/' . $this->id . '/text/' . $k . '_' . $imgKey . '/img/' . $img['file']);
                            $content .= '<img src="' . $imgPath . '" alt="" />';
                        }
                    }
                    $content .= '</div>';
                }
            }
            return $content;
        }

        if (is_string($this->text)) {
            return nl2br(e($this->text));
        }

        return '';
    }
}
