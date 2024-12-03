<?php

namespace Tests\Unit;

use App\Media\Storage\Storage;
use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $vfs = new \VirtualFileSystem\FileSystem;
        $vfs->createDirectory('/storage1/testfolder', true);
        $vfs->createDirectory('/storage2');
        $vfs->createFile('/storage1/testfolder/test.txt', '');
        $vfs->createFile('/storage2/test2.txt', '');

        // add some default settings to the database for these tests
        $firstuuid = Settings::addScannerDir([
            'driver' => 'local',
            'root' => $vfs->path('storage1'),
        ]);

        $seconduuid = Settings::addScannerDir([
            'driver' => 'local',
            'root' => $vfs->path('storage2'),
        ]);

        $storage = $this->app->make(Storage::class);

        $firstfile = $firstuuid.'://testfolder/test.txt';
        $secondfile = $seconduuid.'://test2.txt';

        $this->assertNotNull($storage->storage($firstfile));
        $this->assertNotNull($storage->storage($secondfile));

        $this->assertEquals('testfolder/test.txt', Storage::path($firstfile));
        $this->assertEquals('test2.txt', Storage::path($secondfile));
    }
}
