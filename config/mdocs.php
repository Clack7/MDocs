<?php

use App\Helpers\HostConfig;

// Get cached site data
$dir   = HostConfig::get('dir');
$name  = HostConfig::get('name');
$type  = HostConfig::get('type');
$theme = HostConfig::get('theme');
$theme = strtolower($theme);
$theme = in_array($theme, ['dark', 'light']) ? $theme : 'light';

return [
    'dir' => $dir,
    'char_regex' => '[^a-zA-Z0-9-_\. !\/áéíóúÁÉÍÓÚñÑ]',
    'vim' => [
        'enabled' => env('MDOCS_VIM_ENABLED'),
    ],
    'name' => $name,
    'type' => $type,
    'theme' => $theme,
];
