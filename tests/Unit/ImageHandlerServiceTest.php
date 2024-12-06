<?php

namespace Tests\Unit;

use App\Services\ImageHandlerService;
use Tests\TestCase;

class ImageHandlerServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_extensionless_file(): void
    {
        $imageHandler = new ImageHandlerService;

        $mime = $imageHandler->guessMimeType('filename-without-extension');
        $this->assertEquals('application/octet-stream', $mime);
    }

    public function test_unknown_extension(): void
    {
        $imageHandler = new ImageHandlerService;

        $mime = $imageHandler->guessMimeType('filename.with-weird-extension');
        $this->assertEquals('application/octet-stream', $mime);
    }
}
