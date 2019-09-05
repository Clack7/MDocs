<?php

return [
    'dir' => env('MDOCS_DIR'),
    'char_regex' => '[^a-zA-Z0-9-_\. \/áéíóúÁÉÍÓÚñÑ]',
    'vim' => [
        'enabled' => env('MDOCS_VIM_ENABLED'),
    ],
];