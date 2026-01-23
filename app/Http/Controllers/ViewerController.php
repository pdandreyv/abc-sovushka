<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return view('viewer', [
            'doc' => $doc,
            'ext' => $ext,
        ]);
    }
}
