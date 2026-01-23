<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubscriptionLevel;
use App\Models\Topic;
use App\Models\TopicMaterial;

class SubjectController extends Controller
{
    /**
     * Список предметов для уровня подписки
     */
    public function index(int $level)
    {
        $levelModel = SubscriptionLevel::findOrFail($level);

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
    public function show(int $level, int $subject)
    {
        $levelModel = SubscriptionLevel::findOrFail($level);
        $subjectModel = Subject::where('display', true)
            ->where('subscription_level_id', $levelModel->id)
            ->findOrFail($subject);

        $topics = Topic::query()
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->orderBy('id')
            ->get();

        $materials = TopicMaterial::query()
            ->where('subscription_level_id', $levelModel->id)
            ->where('subject_id', $subjectModel->id)
            ->where('display', true)
            ->orderBy('id')
            ->get();

        $materialsByTopic = $materials->groupBy('topic_id');

        $topicsData = $topics->map(function (Topic $topic) use ($materialsByTopic) {
            $topicMaterials = $materialsByTopic->get($topic->id, collect());
            $hasVisible = $topicMaterials->contains(function (TopicMaterial $material) {
                return !$material->is_blocked;
            });

            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'keywords' => $topic->keywords,
                'is_disabled' => $topicMaterials->isEmpty() || !$hasVisible,
            ];
        })->values();

        $materialsData = $materialsByTopic->map(function ($items) {
            return $items->map(function (TopicMaterial $material) {
                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'is_blocked' => $material->is_blocked,
                    'pdf_url' => $this->resolveFileUrl($material, 'pdf_file', 'pdf'),
                    'zip_url' => $this->resolveFileUrl($material, 'zip_file', 'zip'),
                    'image_url' => $this->resolveFileUrl($material, 'image_file', 'image'),
                    'text_html' => $material->formatted_text,
                ];
            })->values();
        });

        return view('subjects.show', [
            'level' => $levelModel,
            'subject' => $subjectModel,
            'topics' => $topics,
            'topicsData' => $topicsData,
            'materialsData' => $materialsData,
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

        return '/files/' . $file;
    }
}
