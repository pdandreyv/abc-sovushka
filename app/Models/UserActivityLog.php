<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    public const ACTION_LOGIN = 1;
    public const ACTION_MATERIAL_DOWNLOAD = 12;
    public const ACTION_MATERIAL_VIEW = 13;

    protected $fillable = [
        'user_id',
        'ip',
        'action',
        'topic_material_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'topic_material_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topicMaterial(): BelongsTo
    {
        return $this->belongsTo(TopicMaterial::class, 'topic_material_id');
    }

    public static function actionLabels(): array
    {
        return [
            self::ACTION_LOGIN => 'Вход',
            self::ACTION_MATERIAL_DOWNLOAD => 'Скачивание материала темы',
            self::ACTION_MATERIAL_VIEW => 'Просмотр материала темы',
        ];
    }
}
