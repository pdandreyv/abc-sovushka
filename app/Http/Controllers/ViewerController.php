<?php

namespace App\Http\Controllers;

use App\Services\UserActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ViewerController extends Controller
{
    public function show(Request $request)
    {
        $doc = (string) $request->query('doc', '');
        $doc = rawurldecode($doc);

        if ($doc === '' || !str_starts_with($doc, '/files/')) {
            abort(404);
        }

        $clean = preg_replace('/[?#].*/', '', $doc);
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

        if (Auth::check()) {
            $materialId = UserActivityLogService::parseTopicMaterialIdFromPath($doc);
            if ($materialId !== null) {
                UserActivityLogService::logMaterialView(
                    (int) Auth::id(),
                    $materialId,
                    $request->ip() ?? ''
                );
                UserActivityLogService::touchLoginRecord((int) Auth::id());
            }
        }

        return view('viewer', [
            'doc' => $doc,
            'ext' => $ext,
        ]);
    }
}
