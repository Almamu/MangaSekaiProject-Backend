<?php

namespace App\Services;

use Symfony\Component\Mime\MimeTypes;

/**
 * Centralisation of image handling code
 */
class ImageHandlerService
{
    /**
     * @var MimeTypes Used for guessing mime type based on file extension
     */
    private MimeTypes $mimeTypeGuesser;

    /**
     * @param  string[]  $mimeTypes
     */
    public function __construct(
        private readonly array $mimeTypes = [],
    ) {
        $this->mimeTypeGuesser = new MimeTypes();
    }

    public function isMimeTypeSupported(string $mimeType): bool
    {
        return in_array($mimeType, $this->mimeTypes);
    }

    /**
     * Guesses the mime type of the file based on the filename
     */
    public function guessMimeType(string $filename): string
    {
        $filename = basename($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (trim($extension) === '') {
            return 'application/octet-stream';
        }

        $types = $this->mimeTypeGuesser->getMimeTypes($extension);

        if ($types === []) {
            return 'application/octet-stream';
        }

        return reset($types);
    }
}
