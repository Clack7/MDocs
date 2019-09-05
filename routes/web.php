<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => []], function () {
    // File http read
    foreach (['/update', '/create', ''] as $prefix) {
        $r = Route::get($prefix . '/{path}.md_{key}.{ext}', 'API\FileController@attachmentShow')
            ->where('path', '(.*)')
            ->where('key', '[a-zA-Z0-9]{13}')
            ->where('ext', '[a-z]+');
        if ($prefix == '') {
            $r->name('file.attachment.show');
        }
    }

    // API routes
    Route::group(['prefix' => 'api', 'namespace' => 'API'], function () {
        // File
        Route::get('/file', 'FileController@list')->name('file.list');
        Route::get('/file/search', 'FileController@search')->name('file.search');
        Route::post('/file/draft', 'FileController@saveDraft')->name('file.draft');
        Route::post('/file/attach', 'FileController@attachmentUpload')->name('file.attachment.upload');
        Route::post('/file/attach-url', 'FileController@attachmentUploadUrl')->name('file.attachment.upload-url');
        Route::post('/file/attach-svg', 'FileController@attachmentUploadSvg')->name('file.attachment.upload-svg');
        Route::post('/file/toggle', 'FileController@toggleCheckbox')->name('file.toggle');
        Route::get('/file/{path}', 'FileController@show')->name('file.show')->where('path', '(.*)');
        Route::post('/file/{path}', 'FileController@save')->name('file.save')->where('path', '(.*)');
        Route::delete('/file/{path}', 'FileController@delete')->name('file.delete')->where('path', '(.*)');
    });

    // SPA routes
    $spa = [
        '/'             => [
            'name'      => 'index',
            'component' => 'Index',
        ],
        '/create' => [
            'name'      => 'create',
            'component' => 'Editor',
        ],
        '/update/{path}' => [
            'name'      => 'update',
            'component' => 'Editor',
            'path'      => '/update/:path(.*)',
            'where'     => ['path' => '(.*)']
        ],
        // Load last
        '/{path}' => [
            'name'      => 'show',
            'component' => 'Show',
            'path'      => '/:path(.*)',
            'where'     => ['path' => '(.*)']
        ],
    ];
    $jsRoutes = [];
    foreach ($spa as $key => $val) {
        $r = Route::get($key, 'IndexController@index')->name($val['name']);
        if (!empty($val['where'])) {
            foreach ($val['where'] as $k => $v) {
                $r->where($k, $v);
            }
        }
        $jsRoutes[isset($val['path']) ? $val['path'] : $key] = $val['component'];
    }
    // Set config for JS access
    config(['app.spa.routes' => $jsRoutes]);
});
