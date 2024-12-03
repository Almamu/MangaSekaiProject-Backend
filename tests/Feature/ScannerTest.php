<?php

namespace Tests\Feature;

use App\Jobs\ScanMedia;
use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ScannerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private \VirtualFileSystem\FileSystem $vfs;

    private static function baseVfs(): \VirtualFileSystem\FileSystem
    {
        $vfs = new \VirtualFileSystem\FileSystem;

        // create some directories to simulate media structure
        $vfs->createDirectory('/storage1/Bakuman/Chapter 1', true);
        $vfs->createDirectory('/storage2/Bakuman/Chapter 2', true);

        $vfs->createFile('storage1/Bakuman/Chapter 1/001.jpg', '');
        $vfs->createFile('storage2/Bakuman/Chapter 2/001.jpg', '');

        return $vfs;
    }

    public function setup(): void
    {
        parent::setup();

        $this->vfs = self::baseVfs();

        // add some default settings to the database for these tests
        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage1'),
        ]);

        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage2'),
        ]);

        $this->assertDatabaseCount('settings', 1);
    }

    /**
     * A basic test example.
     */
    #[TestDox('Adds some media, adds new chapters to that library and then adds new series to it')]
    public function test_media_start_add_chapters_update(): void
    {
        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 2);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 2);
        $this->assertDatabaseCount('pages', 2);

        // setup vfs for the new detection
        $this->vfs->createDirectory('/storage1/Bakuman/Chapter 3', true);
        $this->vfs->createDirectory('/storage2/Bakuman/Chapter 3', true);
        $this->vfs->createFile('/storage1/Bakuman/Chapter 3/001.jpg', '');
        $this->vfs->createFile('/storage2/Bakuman/Chapter 3/002.jpg', '');

        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 4);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 3);
        $this->assertDatabaseCount('pages', 4);

        // setup vfs for the new detection
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 1', true);
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 2', true);
        $this->vfs->createFile('/storage1/Death Note/Chapter 1/001.jpg', '');
        $this->vfs->createFile('/storage1/Death Note/Chapter 2/001.jpg', '');

        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 6);
        $this->assertDatabaseCount('series', 2);
        $this->assertDatabaseCount('chapters', 5);
        $this->assertDatabaseCount('pages', 6);
    }

    public function test_media_create(): void
    {
        // setup vfs for the new detection
        $this->vfs->createDirectory('/storage1/Bakuman/Chapter 3', true);
        $this->vfs->createDirectory('/storage2/Bakuman/Chapter 3', true);
        $this->vfs->createFile('/storage1/Bakuman/Chapter 3/001.jpg', '');
        $this->vfs->createFile('/storage2/Bakuman/Chapter 3/002.jpg', '');
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 1', true);
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 2', true);
        $this->vfs->createFile('/storage1/Death Note/Chapter 1/001.jpg', '');
        $this->vfs->createFile('/storage1/Death Note/Chapter 2/001.jpg', '');

        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 6);
        $this->assertDatabaseCount('series', 2);
        $this->assertDatabaseCount('chapters', 5);
        $this->assertDatabaseCount('pages', 6);
    }

    public function test_media_removal(): void
    {
        // use whole media creation test as base to simplify this one a bit
        $this->test_media_create();

        $this->vfs->container()->remove('storage1/Bakuman/Chapter 3', true);
        $this->vfs->container()->remove('storage1/Death Note', true);

        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 3);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 3);
        $this->assertDatabaseCount('pages', 3);
    }

    public function test_media_zip(): void
    {
        // create a mock zip file and add it to the vfs
        $zip = new \PhpZip\ZipFile;
        $zip->addFromString('Chapter 3/001.jpg', '');
        $zip->addFromString('Chapter 3/002.jpg', '');

        $this->vfs->createFile('/storage1/Bakuman.zip', $zip->outputAsString());

        ScanMedia::dispatchSync();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 3);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 3);
        $this->assertDatabaseCount('pages', 4);
    }
}
