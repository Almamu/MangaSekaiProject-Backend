<?php

namespace App\Media\Storage\Handlers;

use App\Media\Storage\ParsedPath;

interface Handler
{
    /**
     * @return bool Indicates if this handler can provide the content's of the given file
     */
    public function providable(ParsedPath $path): bool;

    /**
     * Opens the requested file and calls $callback to provide access to it
     * The file is automatically closed after the callback is done
     *
     * @param  callable(resource): void  $callback  Function called with the valid resource to be used by the app
     */
    public function provide(ParsedPath $path, callable $callback): void;
}
