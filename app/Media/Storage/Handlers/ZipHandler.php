<?php

namespace App\Media\Storage\Handlers;

use App\Media\Storage\Exceptions\CannotReadFileException;
use App\Media\Storage\ParsedPath;
use App\Media\Storage\Storage;

readonly class ZipHandler implements Handler
{
    public function __construct(
        private Storage $storage,
    ) {
    }

    public function providable(ParsedPath $path): bool
    {
        // zip are container paths, so no container means not possible to read
        if ($path->hasContainer() === false) {
            return false;
        }

        // ensure the extension matches too
        return pathinfo($path->container, PATHINFO_EXTENSION) === 'zip';
    }

    public function provide(ParsedPath $path, callable $callback): void
    {
        $disk = $this->storage->storage($path);

        $stream = $disk->readStream($path->container);

        throw_if(is_null($stream), new CannotReadFileException($path));

        // open the zip file
        $zip = new \PhpZip\ZipFile();
        $zip->openFromStream($stream);

        $entry = $zip->getEntry($path->path);

        throw_if($entry->isDirectory(), new CannotReadFileException($path));

        $data = $entry->getData();

        throw_if(is_null($data), new CannotReadFileException($path));

        $callback($data->getDataAsStream());
    }
}
