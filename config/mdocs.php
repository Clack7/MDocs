<?php

// $config = json_decode(file_get_contents(__DIR__ . '/../storage/app/default/config.json'), true);
// var_dump($config, file_get_contents(__DIR__ . '/../storage/app/default/config.json'));die;

$dir = env('MDOCS_DIR');
if (strpos($_SERVER['SERVER_NAME'], 'ahiw.local') !== false) {
    $dir = 'F:/Google Drive/AHIW/Docs';
}

return [
    'dir' => $dir,//env('MDOCS_DIR'),
    'char_regex' => '[^a-zA-Z0-9-_\. !\/áéíóúÁÉÍÓÚñÑ]',
    'vim' => [
        'enabled' => env('MDOCS_VIM_ENABLED'),
    ],
];