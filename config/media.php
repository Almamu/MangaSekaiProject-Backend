<?php

return [
    'processors' => [
        \App\Media\Scanner\Processors\ZipProcessor::class,
        \App\Media\Scanner\Processors\FolderProcessor::class,
    ],
    'mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
    ],
    'handlers' => [
        \App\Media\Storage\Handlers\ZipHandler::class,
        \App\Media\Storage\Handlers\FolderHandler::class,
    ],
    'matchers' => [
        \App\Media\Matcher\Sources\AniListSource::class,
    ],
];
