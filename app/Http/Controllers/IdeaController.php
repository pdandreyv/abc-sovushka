<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdeaController extends Controller
{
    /**
     * Показать страницу с идеями
     */
    public function index()
    {
        $ideas = Idea::orderBy('created_at', 'desc')->get();
        $userId = Auth::id();

        // Помечаем, какие идеи уже лайкнуты текущим пользователем
        foreach ($ideas as $idea) {
            $idea->is_liked = $idea->isLikedBy($userId);
        }

        return view('ideas.index', compact('ideas'));
    }

    /**
     * Лайкнуть/убрать лайк с идеи
     */
    public function like(Request $request, Idea $idea)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Необходима авторизация'], 401);
        }

        // Проверяем, лайкнул ли уже пользователь эту идею
        $isLiked = $idea->isLikedBy($user->id);

        if ($isLiked) {
            // Убираем лайк
            $idea->likedByUsers()->detach($user->id);
            $idea->decrement('likes');
            $liked = false;
        } else {
            // Добавляем лайк
            $idea->likedByUsers()->attach($user->id);
            $idea->increment('likes');
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $idea->fresh()->likes,
        ]);
    }
}
