<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubscriptionLevel;
use App\Models\SubscriptionOrder;
use App\Models\Topic;
use App\Models\TopicMaterial;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Список предметов для уровня подписки
     */
    public function index(string $level)
    {
        $levelModel = $this->resolveLevel($level);
        if (!$levelModel) {
            $subjectBySlug = Subject::query()
                ->where('display', true)
                ->where('link', $level)
                ->orWhere('link', '/' . $level)
                ->first();

            if ($subjectBySlug) {
                $subjectSlug = $subjectBySlug->link ?: $subjectBySlug->id;
                $subjectSlug = ltrim((string) $subjectSlug, '/');

                $levelFromTopic = Topic::query()
                    ->where('subject_id', $subjectBySlug->id)
                    ->orderBy('subscription_level_id')
                    ->orderBy('id')
                    ->value('subscription_level_id');

                if ($levelFromTopic) {
                    return redirect()->route('subjects.show', [
                        'level' => $levelFromTopic,
                        'subject' => $subjectSlug,
                    ]);
                }
            }

            abort(404);
        }

        $subjects = Subject::query()
            ->where('display', true)
            ->whereHas('topics', function ($query) use ($levelModel) {
                $query->where('subscription_level_id', $levelModel->id);
            })
            ->withCount(['topics as topics_count' => function ($query) use ($levelModel) {
                $query->where('subscription_level_id', $levelModel->id);
            }])
            ->orderByDesc('rating')
            ->orderBy('title')
            ->get();

        $hasAccess = (bool) $levelModel->open || $this->hasActiveSubscriptionForLevel($levelModel->id);

        return view('subjects.index', [
            'level' => $levelModel,
            'subjects' => $subjects,
            'hasAccess' => $hasAccess,
        ]);
    }

    /**
     * Темы и материалы для выбранного предмета
     */
    public function show(string $level, string $subject)
    {
        $levelModel = $this->resolveLevel($level);
        $subjectModel = $this->resolveSubject($subject);

        $hasAccess = (bool) $levelModel->open || $this->hasActiveSubscriptionForLevel($levelModel->id);

        $topics = Topic::query()
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->where('display', true)
            ->orderByDesc('rank')
            ->orderBy('id')
            ->get();

        $topicsData = $topics->map(function (Topic $topic) use ($hasAccess) {
            return [
                'id' => $topic->id,
                'number' => $topic->topic_number,
                'title' => $topic->title,
                'text_html' => $this->formatTopicText($topic->text),
                'is_blocked' => !$hasAccess || (bool) $topic->is_blocked,
            ];
        })->values();

        return view('subjects.show', [
            'level' => $levelModel,
            'subject' => $subjectModel,
            'topics' => $topics,
            'topicsData' => $topicsData,
            'hasAccess' => $hasAccess,
        ]);
    }

    public function materials(string $level, string $subject, int $topic)
    {
        $levelModel = $this->resolveLevel($level);
        if (!$levelModel) {
            abort(404);
        }

        if (! (bool) $levelModel->open && ! $this->hasActiveSubscriptionForLevel($levelModel->id)) {
            return response()->json(['materials' => [], 'message' => 'Нет активной подписки на этот уровень.'], 403);
        }

        $subjectModel = $this->resolveSubject($subject);
        $topicModel = Topic::query()
            ->where('id', $topic)
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->firstOrFail();

        $materials = TopicMaterial::query()
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->where('topic_id', $topicModel->id)
            ->where('display', true)
            ->orderByDesc('rank')
            ->orderBy('id')
            ->get();

        $payload = $materials->map(function (TopicMaterial $material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'pdf_url' => $this->resolveFileUrl($material, 'pdf_file', 'pdf'),
                'zip_url' => $this->resolveFileUrl($material, 'zip_file', 'zip'),
            ];
        })->values();

        return response()->json([
            'materials' => $payload,
        ]);
    }

    private function resolveFileUrl(TopicMaterial $material, string $field, string $folder): ?string
    {
        $file = $material->{$field};
        if (!$file) {
            return null;
        }

        if (str_starts_with($file, '/') || str_contains($file, '/')) {
            if (str_starts_with($file, '/abc/files/')) {
                return str_replace('/abc/files/', '/files/', $file);
            }
            return $file;
        }

        $path = '/files/topic_materials/' . $material->id . '/' . $folder . '/' . $file;
        $fullPath = public_path(ltrim($path, '/'));

        if (file_exists($fullPath)) {
            return $path;
        }

        $legacyPath = '/files/topic_materials/' . $material->id . '/' . $field . '/' . $file;
        $legacyFullPath = public_path(ltrim($legacyPath, '/'));

        if (file_exists($legacyFullPath)) {
            return $legacyPath;
        }

        $adminPath = '/abc/files/topic_materials/' . $material->id . '/' . $field . '/' . $file;
        $adminFullPath = public_path(ltrim($adminPath, '/'));

        if (file_exists($adminFullPath)) {
            return $adminPath;
        }

        return '/files/' . $file;
    }

    private function formatTopicText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        $decoded = htmlspecialchars_decode($text, ENT_QUOTES);
        $decoded = str_replace(['\\r', '\\n'], '', $decoded);
        $decoded = stripslashes($decoded);

        return $decoded;
    }

    /**
     * Есть ли у текущего пользователя активная подписка на уровень (paid, date_till >= сегодня).
     * Пользователи с ролью админа имеют доступ ко всем уровням независимо от подписки.
     */
    private function hasActiveSubscriptionForLevel(int $levelId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $role = strtolower((string) $user->role);
        if (in_array($role, ['admin', 'administrator', 'superadmin', 'owner'], true)) {
            return true;
        }

        $today = now()->toDateString();

        return SubscriptionOrder::query()
            ->where('user_id', $user->id)
            ->where('paid', true)
            ->whereDate('date_till', '>=', $today)
            ->where(function ($q) use ($levelId) {
                $s = (string) $levelId;
                $q->where('levels', $s)
                    ->orWhere('levels', 'like', $s . ',%')
                    ->orWhere('levels', 'like', '%,' . $s . ',%')
                    ->orWhere('levels', 'like', '%,' . $s);
            })
            ->exists();
    }

    private function resolveLevel(string $level): ?SubscriptionLevel
    {
        $level = trim($level);

        if (is_numeric($level)) {
            return SubscriptionLevel::find((int) $level);
        }

        $slug = trim($level, '/');

        $byLink = SubscriptionLevel::query()
            ->where('link', $slug)
            ->orWhere('link', '/' . $slug)
            ->orWhere('link', '/subjects/' . $slug)
            ->orWhere('link', 'like', '%/subjects/' . $slug)
            ->first();

        return $byLink;
    }

    private function resolveSubject(string $subject): Subject
    {
        $subject = trim($subject);

        $query = Subject::query()
            ->where('display', true);

        if (is_numeric($subject)) {
            return $query->findOrFail((int) $subject);
        }

        $slug = trim($subject, '/');

        $query->where(function ($q) use ($slug) {
            $q->where('link', $slug)
                ->orWhere('link', '/' . $slug)
                ->orWhere('link', 'like', '%/' . $slug);
        });

        return $query->firstOrFail();
    }
}
