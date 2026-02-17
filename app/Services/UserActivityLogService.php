<?php

namespace App\Services;

use App\Models\UserActivityLog;
use Illuminate\Support\Facades\DB;

class UserActivityLogService
{
    /**
     * Логирование входа: одна запись в сутки на пользователя, при повторных действиях только обновляется updated_at.
     */
    public static function logLogin(int $userId, string $ip): void
    {
        $today = now()->toDateString();

        $existing = UserActivityLog::query()
            ->where('user_id', $userId)
            ->where('action', UserActivityLog::ACTION_LOGIN)
            ->whereDate('created_at', $today)
            ->first();

        if ($existing) {
            $existing->update([
                'updated_at' => now(),
                'ip' => $ip,
            ]);
        } else {
            UserActivityLog::create([
                'user_id' => $userId,
                'ip' => $ip,
                'action' => UserActivityLog::ACTION_LOGIN,
                'topic_material_id' => null,
            ]);
        }
    }

    /**
     * Обновить время последнего действия по записи «вход» за сегодня (если есть).
     */
    public static function touchLoginRecord(int $userId): void
    {
        $today = now()->toDateString();
        UserActivityLog::query()
            ->where('user_id', $userId)
            ->where('action', UserActivityLog::ACTION_LOGIN)
            ->whereDate('created_at', $today)
            ->update(['updated_at' => now()]);
    }

    /**
     * Логирование скачивания материала темы (каждый раз новая запись).
     */
    public static function logMaterialDownload(int $userId, int $topicMaterialId, string $ip): void
    {
        UserActivityLog::create([
            'user_id' => $userId,
            'ip' => $ip,
            'action' => UserActivityLog::ACTION_MATERIAL_DOWNLOAD,
            'topic_material_id' => $topicMaterialId,
        ]);
    }

    /**
     * Логирование просмотра материала темы (каждый раз новая запись).
     */
    public static function logMaterialView(int $userId, int $topicMaterialId, string $ip): void
    {
        UserActivityLog::create([
            'user_id' => $userId,
            'ip' => $ip,
            'action' => UserActivityLog::ACTION_MATERIAL_VIEW,
            'topic_material_id' => $topicMaterialId,
        ]);
    }

    /**
     * Извлечь topic_material_id из пути вида /files/topic_materials/123/pdf/... или /files/topic_materials/123/...
     */
    public static function parseTopicMaterialIdFromPath(string $path): ?int
    {
        if (preg_match('#/files/topic_materials/(\d+)#', $path, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
