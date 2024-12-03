<?php

return [
    'processors' => [
        \App\Scanner\Processors\ZipProcessor::class,
        \App\Scanner\Processors\FolderProcessor::class,
    ],
    'mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
    ],
];
