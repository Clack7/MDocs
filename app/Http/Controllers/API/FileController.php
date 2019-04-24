<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    private $dir;

    public function __construct()
    {
        $this->dir = config('mdocs.dir');
    }

    public function list()
    {
        $files = \File::allFiles($this->dir);

        $list = [];
        foreach ($files as $file) {
            $pathinfo = pathinfo($file->getRelativePathname());
            if ($pathinfo['extension'] != 'md') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getRelativePathname());
            $path = substr($path, 0, -3);
            $list[strtolower($path)] = [
                'path' => $path
            ];
        }
        ksort($list);

        return ['files' => array_values($list)];
    }

    public function show($path)
    {
        $pathFile = $this->dir . '/' . $path . '.md';
        if (!\File::isFile($pathFile)) {
            abort(404, 'File not found.');
        }

        return ['content' => \File::get($pathFile)];
    }

    public function save($path, Request $request)
    {
        $pathCur     = $this->pathClean($request->request->get('path_cur'));
        $pathCurFile = $this->pathFile($pathCur);
        $pathNew     = $this->pathClean($path);
        $pathNewFile = $this->pathFile($pathNew);
        $content     = trim($request->request->get('content'));

        if ($pathNew == '') {
            abort(400, 'Please specify the file path.');
        }

        // Create dir
        $dirname = dirname($pathNewFile);
        if (!is_dir($dirname)) {
            \File::makeDirectory($dirname, 0755, true);
        }

        // New file
        if ($pathCur == '') {
            // Adjust attachments to a new location
            $this->attachmentMove('new-file', $pathNew, $content);

        // Move
        } elseif ($pathCurFile != $pathNewFile) {
            if (\File::isFile($pathNewFile)) {
                abort(400, 'Cannot overwrite existing file.');
            }

            // Move current file
            \File::move($pathCurFile, $pathNewFile);

            // Adjust attachments to a new location
            $this->attachmentMove($pathCur, $pathNew, $content);

            // Remove empty dirs
            $this->removeEmptyDir($pathCurFile);

        // Update
        } else {
            // Adjust attachments to a new location
            $this->attachmentMove($pathCur, $pathNew, $content);
        }

        // Save
        \File::put($pathNewFile, $content);

        return response()->json(['path' => $pathNew], $pathCur == '' ? 201 : 200);
    }

    private function attachmentMove($pathCur, $pathNew, &$content)
    {
        $pathCurFile = $this->pathFile($pathCur);
        $pathNewFile = $this->pathFile($pathNew);

        // Find files to remove
        $lines = explode("\n", $content);
        foreach ($lines as $k => $line) {
            if (preg_match('/^ - \[ \] \[([^\]]+)\]\([^\]]+ "Uncheck to delete"\)/', $line, $match)) {
                unset($lines[$k]);
                $key = explode(':', $match[1])[1];
                unlink($pathCurFile . '_' . $key);
            }
        }
        $content = implode("\n", $lines);

        // Move files
        $attachments = glob($pathCurFile . '_*');
        $keys = [];
        foreach ($attachments as $at) {
            \File::move($at, str_replace($pathCurFile, $pathNewFile, $at));
            $key = explode('_', $at);
            $keys[] = end($key);
        }

        // Replace content usage
        $content = str_replace('/a/' . urlencode($pathCur) . ':', '/a/' . urlencode($pathNew) . ':', $content);

        // Add not reference files
        $title = "\n\n\n--\n\n**Attachments:**";
        $parts = explode($title, $content);
        $parts[1] = isset($parts[1]) ? $parts[1] : '';
        foreach ($keys as $key) {
            if (strpos($content, $key) === false) {
                $url = '/a/' . urlencode($pathNew) . ':' . $key;
                $parts[1] .= "\n - [X] [$url]($url \"Uncheck to delete\")";
            }
        }
        if (!empty($parts[1])) {
            $content = implode($title, $parts);
        } else {
            $content = $parts[0];
        }
    }

    public function attachmentShow($path, $key)
    {
        $filePath = $this->dir . '/' . urldecode($path) . '.md_' . $key;
        return response()->file($filePath);
    }

    public function attachmentUpload(Request $request)
    {
        $path = $this->pathClean($request->request->get('path'));
        $pathFile = $this->pathFile($path);
        // Check file exists
        if (!empty($path) && !is_file($pathFile)) {
            abort(400, 'Invalid file path.');
        }

        // Parse base64
        $base64 = $request->request->get('base64');
        $start = 'data:image/png;base64,';
        if (strpos($base64, $start) === false) {
            abort(400, 'Invalid base64 format');
        }
        $base64 = substr($base64, strlen($start));

        // Save file
        $key = uniqid() . '.png';
        $savePath = (empty($path) ? 'new-file' : $path);
        \File::put($this->dir . '/' . $savePath . '.md_' . $key, base64_decode($base64));

        return response()->json(['url' => route('file.attachment.show', [
            'path' => urlencode($savePath),
            'key' => $key,
        ], false)], 201);
    }

    public function delete($path)
    {
        $pathFile = $this->dir . '/' . $path . '.md';
        if (\File::isFile($pathFile)) {
            \File::delete($pathFile);

            // Remove attachments
            $attachments = glob($pathFile . '_*');
            foreach ($attachments as $at) {
                unlink($at);
            }

            $this->removeEmptyDir($pathFile);
        }

        return ['path' => $path];
    }

    private function pathClean($path)
    {
        $path = preg_replace('/[^a-zA-Z0-9-_ \/]/', '', $path);
        $path = preg_replace('/\s+/', ' ', $path);
        $path = explode('/', $path);
        $path = array_map('trim', $path);
        $path = implode('/', $path);

        return trim($path, '/');
    }

    private function pathFile($path)
    {
        return $this->dir . '/' . trim(str_replace('..', '', $path), '/.') . '.md';
    }

    private function removeEmptyDir($pathFile)
    {
        $curDirname = dirname($pathFile);
        do {
            if (realpath($curDirname) == realpath($this->dir)) {
                break;
            }
            $curFiles = glob($curDirname . '/*');
            if (empty($curFiles)) {
                rmdir($curDirname);
                $curDirname = pathinfo($curDirname, PATHINFO_DIRNAME);
            } else {
                $curDirname = null;
            }
        } while ($curDirname != null);
    }
}
