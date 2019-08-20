<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    private $dir;
    private $draftPath;

    public function __construct()
    {
        $this->dir = config('mdocs.dir');
        $this->draftPath = $this->dir . '/.draft.json';
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

        // Build tree
        $tree = [];
        foreach ($list as $file) {
            $parts = explode('/', $file['path']);
            $children =& $tree;
            $path = [];
            foreach ($parts as $key => $part) {
                $path[] = $part;
                // Check part exists in map and create
                if (!isset($children[strtolower($part)])) {
                    $children[strtolower($part)] = [
                        'name'     => $part,
                        'path'     => implode('/', $path),
                        'file'     => false,
                        'children' => [],
                    ];
                }

                // ADd path to last part
                if ($key == count($parts) - 1) {
                    $children[strtolower($part)]['file'] = true;

                // Continue in next part
                } else {
                    $children =& $children[strtolower($part)]['children'];
                }
            }
        }
        $treeArrayValues = function($tree) use (&$treeArrayValues) {
            foreach ($tree as $key => $val) {
                $tree[$key]['children'] = $treeArrayValues($val['children']);
            }
            return array_values($tree);
        };
        $tree = $treeArrayValues($tree);

        return ['files' => array_values($list), 'tree' => $tree];
    }

    public function search(Request $request)
    {
        $files = $this->list()['files'];

        $result = [];
        $query = trim($request->query->get('query', ''));
        if (!empty($query)) {
            $query = mb_strtolower($query);
            foreach ($files as $file) {
                $add = false;
                $match = [];

                // Check file name
                $test = explode('/', $file['path']);
                if (strpos(strtolower(end($test)), $query) !== false) {
                    $add = true;
                }

                // Check file content
                $lines = file($this->pathFile($file['path']));
                foreach ($lines as $key => $line) {
                    if (strpos(strtolower($line), $query) !== false) {
                        $add = true;
                        $match[] = ($key + 1) . '. ' . $line;
                    }
                }

                if ($add) {
                    $result[] = [
                        'path' => $file['path'],
                        'match' => $match,
                    ];
                }
            }
        }

        return ['result' => $result];
    }

    public function show($path)
    {
        // Find content
        $content = '';
        if ($path != '@empty') {
            $pathFile = $this->dir . '/' . $path . '.md';
            if (!\File::isFile($pathFile)) {
                abort(404, 'File not found.');
            }
            $content = \File::get($pathFile);
            $draft = $this->getDraft($path);
        } else {
            $draft = $this->getDraft('');
        }

        return ['content' => $content, 'draft' => $draft];
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

        // Remove draft
        $this->removeDraft($pathCur);

        return response()->json(['path' => $pathNew], $pathCur == '' ? 201 : 200);
    }

    public function saveDraft(Request $request)
    {
        $pathCur = $this->pathClean($request->request->get('path_cur'));
        $content = trim($request->request->get('content'));
        $this->putDraft($pathCur, $content);

        return response()->json(['result' => 1], 201);
    }

    public function putDraft($path, $content)
    {
        $list = $this->getDraft();
        foreach ($list as $key => $val) {
            if ($val['path'] == $path) {
                unset($list[$key]);
                break;
            }
        }
        $list[] = [
            'path' => $path,
            'content' => $content,
        ];
        $list = array_values($list);

        \File::put($this->draftPath, json_encode($list));
    }

    public function getDraft($path = null)
    {
        $list = [];
        if (\File::exists($this->draftPath)) {
            $json = json_decode(\File::get($this->draftPath), true);
            if (is_array($json))  {
                $list = $json;
            }
        }

        if ($path !== null) {
            $path = $this->pathClean($path);
            foreach ($list as $l) {
                if (isset($l['path']) && $l['path'] == $path) {
                    return $l['content'];
                }
            }
            return null;
        }

        return $list;
    }

    public function removeDraft($path)
    {
        $list = $this->getDraft();
        foreach ($list as $key => $val) {
            if ($val['path'] == $path) {
                unset($list[$key]);
                break;
            }
        }
        $list = array_values($list);
        \File::put($this->draftPath, json_encode($list));
    }

    public function toggleCheckbox(Request $request)
    {
        $pathCur     = $this->pathClean($request->request->get('path'));
        $pathCurFile = $this->pathFile($pathCur);
        $checkIndex  = $request->request->get('index');
        if (!\File::isFile($pathCurFile) || !is_numeric($checkIndex)) {
            abort(404, 'File not found.');
        }

        // Find and replace check state
        $content = \File::get($pathCurFile);
        $lines = explode("\n", $content);
        $index = 0;
        foreach ($lines as $k => $line) {
            if (preg_match('/\s*- \[([ xX])\] /', $line, $match)) {
                if ($index == $checkIndex) {
                    $checked = $match[1] == ' ';
                    $line = str_replace($match[0], str_replace('[' . $match[1] . ']', $match[1] == ' ' ? '[X]' : '[ ]', $match[0]), $line);
                    $lines[$k] = $line;
                    break;
                }
                $index++;
            }
        }
        $content = implode("\n", $lines);

        // Save
        \File::put($pathCurFile, $content);

        return response()->json(['content' => $content], 200);
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
                $key = explode('.md_', $match[1])[1];
                unlink($pathCurFile . '_' . $key);
            }
        }
        $content = implode("\n", $lines);

        // Move files
        $attachments = glob($pathCurFile . '_*');
        $keys = [];
        foreach ($attachments as $at) {
            if (preg_match('/[a-z0-9]{10}\.[a-zA-Z0-9]+$/', $at)) {
                \File::move($at, str_replace($pathCurFile, $pathNewFile, $at));
                $key = explode('.md_', $at);
                $keys[] = $key[1];
            }
        }

        // Replace content usage
        $content = str_replace(
            $this->attachmentUrl($pathCur) . '.md_',
            $this->attachmentUrl($pathNew) . '.md_',
            $content
        );

        // Add not reference files
        $title = "\n\n\n--\n\n**Attachments:**";
        $parts = explode($title, $content);
        $parts[1] = isset($parts[1]) ? $parts[1] : '';
        foreach ($keys as $key) {
            if (strpos($content, $key) === false) {
                $url = $this->attachmentUrl($pathNew) . '.md_' . $key;
                $parts[1] .= "\n - [X] [$url]($url \"Uncheck to delete\")";
            }
        }
        if (!empty($parts[1])) {
            $content = implode($title, $parts);
        } else {
            $content = $parts[0];
        }
    }

    public function attachmentShow($path, $key, $ext)
    {
        if (strpos(pathinfo($path, PATHINFO_BASENAME), 'new-file') !== false) {
            $path = pathinfo($path, PATHINFO_BASENAME);
        }
        $filePath = $this->dir . '/' . $this->pathDecode($path) . '.md_' . $key . '.' . $ext;
        if (!\File::exists($filePath)) {
            abort(400, 'File not found.');
        }
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
        $savePath = (empty($path) ? 'new-file' : $path);
        $savePath = $this->dir . '/' . $savePath . '.md';
        $filePath = $this->getAttachmentPath($savePath, 'png');
        \File::put($filePath, base64_decode($base64));

        return response()->json([
            'url' => $this->attachmentUrl($filePath)
        ], 201);
    }

    public function attachmentUploadUrl(Request $request)
    {
        $path = $this->pathClean($request->request->get('path'));
        $pathFile = $this->pathFile($path);
        // Check file exists
        if (!empty($path) && !is_file($pathFile)) {
            abort(400, 'Invalid file path.');
        }

        // Parse Check Url valid Mime
        $url = $request->request->get('url');
        $headers = get_headers($url);
        $validMimes = [
            'image/jpeg' => 'jpg',
            'image/gif'  => 'gif',
            'image/png'  => 'png',
        ];
        foreach ($headers as $header) {
            $header = strtolower($header);
            if (strpos($header, 'content-type') !== false) {
                $mime = trim(explode(':', $header)[1]);
                if (isset($validMimes[$mime])) {
                    // Set path, download and save file contents
                    $savePath = (empty($path) ? 'new-file' : $path);
                    $savePath = $this->dir . '/' . $savePath . '.md';
                    $filePath = $this->getAttachmentPath($savePath, $validMimes[$mime]);
                    $contents = file_get_contents($url);
                    \File::put($filePath, $contents);
                    return response()->json([
                        'url' => $this->attachmentUrl($filePath)
                    ], 201);
                } else {
                    abort(400, 'Invalid mime type: ' . $mime);
                }
            }
        }

        abort(400, 'Invalid url.');
    }

    private function getAttachmentPath($pathFile, $ext)
    {
        do {
            $filePath = $pathFile . '_' . uniqid() . '.' . $ext;
        } while (file_exists($filePath));
        return $filePath;
    }

    public function delete($path)
    {
        $pathFile = $this->dir . '/' . $path . '.md';
        if (\File::isFile($pathFile)) {
            // Remove attachments
            $attachments = glob($pathFile . '_*');
            foreach ($attachments as $at) {
                \File::delete($at);
            }

            // Remove real file
            \File::delete($pathFile);

            $this->removeEmptyDir($pathFile);
        }

        return ['path' => $path];
    }

    private function pathClean($path)
    {
        $path = preg_replace('/' . config('mdocs.char_regex') . '/', '', $path);
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

    private function pathEncode($path)
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function attachmentUrl($path)
    {
        return './' . rawurlencode(pathinfo($path, PATHINFO_BASENAME));
    }

    private function pathDecode($path)
    {
        return implode('/', array_map('rawurldecode', explode('/', $path)));
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
