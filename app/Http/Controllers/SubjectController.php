<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubscriptionLevel;
use App\Models\Topic;
use App\Models\TopicMaterial;
use Illuminate\Support\Facades\DB;

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

                return redirect()->route('subjects.show', [
                    'level' => $subjectBySlug->subscription_level_id,
                    'subject' => $subjectSlug,
                ]);
            }

            abort(404);
        }

        $subjects = Subject::query()
            ->where('display', true)
            ->where('subscription_level_id', $levelModel->id)
            ->withCount('topics')
            ->orderByDesc('rating')
            ->orderBy('title')
            ->get();

        return view('subjects.index', [
            'level' => $levelModel,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Темы и материалы для выбранного предмета
     */
    public function show(string $level, string $subject)
    {
        $levelModel = $this->resolveLevel($level);
        $subjectModel = $this->resolveSubject($levelModel, $subject);

        $topics = Topic::query()
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->orderBy('id')
            ->get();

        $materialsStats = TopicMaterial::query()
            ->select('topic_id', DB::raw('SUM(CASE WHEN is_blocked = 0 THEN 1 ELSE 0 END) as available_count'))
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->where('display', true)
            ->groupBy('topic_id')
            ->get()
            ->keyBy('topic_id');

        $topicsData = $topics->map(function (Topic $topic) use ($materialsStats) {
            $availableCount = (int) data_get($materialsStats, $topic->id . '.available_count', 0);

            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'keywords' => $topic->keywords,
                'is_disabled' => $availableCount === 0,
            ];
        })->values();

        return view('subjects.show', [
            'level' => $levelModel,
            'subject' => $subjectModel,
            'topics' => $topics,
            'topicsData' => $topicsData,
        ]);
    }

    public function materials(string $level, string $subject, int $topic)
    {
        $levelModel = $this->resolveLevel($level);
        if (!$levelModel) {
            abort(404);
        }

        $subjectModel = $this->resolveSubject($levelModel, $subject);
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
            ->orderBy('id')
            ->get();

        $payload = $materials->map(function (TopicMaterial $material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'is_blocked' => $material->is_blocked,
                'pdf_url' => $this->resolveFileUrl($material, 'pdf_file', 'pdf'),
                'zip_url' => $this->resolveFileUrl($material, 'zip_file', 'zip'),
                'text_html' => $material->formatted_text,
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

    private function resolveSubject(SubscriptionLevel $level, string $subject): Subject
    {
        $subject = trim($subject);

        $query = Subject::query()
            ->where('display', true)
            ->where('subscription_level_id', $level->id);

        if (is_numeric($subject)) {
            return $query->findOrFail((int) $subject);
        }

        $slug = trim($subject, '/');

        $query->where(function ($q) use ($slug, $level) {
            $q->where('link', $slug)
                ->orWhere('link', '/' . $slug)
                ->orWhere('link', '/subjects/' . $level->id . '/' . $slug)
                ->orWhere('link', 'like', '%/' . $slug);
        });

        return $query->firstOrFail();
    }
}
