<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        // Read non md static files
        $uri = $request->getPathInfo();
        if (!empty($uri) && pathinfo($uri, PATHINFO_EXTENSION) != 'md') {
            $dir = config('mdocs.dir');
            $file = $dir . $uri;

            if (is_file($file)) {
                $mime = mime_content_type($file);
                return response()->file($file, [
                    'Content-Type' => $mime
                ]);
            }
        }

        // Show page layout
        return view('index');
    }
}
