<?php

namespace Tests\Feature;

use App\Jobs\DownloadResources;
use App\Jobs\ScanMedia;
use App\Media\Matcher\Data\AuthorMatch;
use App\Media\Matcher\Data\SeriesMatch;
use App\Media\Matcher\Matcher;
use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ScannerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private \VirtualFileSystem\FileSystem $vfs;

    private function baseVfs(): \VirtualFileSystem\FileSystem
    {
        $vfs = new \VirtualFileSystem\FileSystem();

        // create some directories to simulate media structure
        $vfs->createDirectory('/storage1/Bakuman/Chapter 1', true);
        $vfs->createDirectory('/storage2/Bakuman/Chapter 2', true);

        $vfs->createFile('storage1/Bakuman/Chapter 1/001.jpg', '');
        $vfs->createFile('storage2/Bakuman/Chapter 2/001.5.jpg', '');
        $vfs->createFile('storage1/Bakuman/Chapter 1/002.doc', '');
        $vfs->createFile('storage1/Bakuman/Chapter 1/Invalid Page.jpg', '');

        // add some directory noise so it's not detected by the scanner
        $vfs->createDirectory('/storage1/Bakuman/Invalid chapter', true);

        return $vfs;
    }

    private function dispatchScanMedia(): void
    {
        ScanMedia::dispatchSync($this->app->make(\Illuminate\Config\Repository::class));
    }

    protected function setup(): void
    {
        parent::setup();

        Queue::fake([
            DownloadResources::class,
        ]);

        $this->vfs = $this->baseVfs();

        // add some default settings to the database for these tests
        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage1'),
        ]);

        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage2'),
        ]);

        // scanner_dirs && complete_threshold
        $this->assertDatabaseCount('settings', 2);

        // mock the media matcher to return controlled data so we do not call external services anymore
        $this->instance(
            Matcher::class,
            Mockery::mock(Matcher::class, function (MockInterface $mock): void {
                $mock->shouldReceive('match')->with('Bakuman.zip')->andReturn([]);
                $mock
                    ->shouldReceive('match')
                    ->with('Bakuman')
                    ->andReturn([
                        new SeriesMatch(1000, 'anilist', '', 'http://dummy.test', '', [], 1, '', '', [
                            new AuthorMatch(1, 'role', 'name', 'http://dummy.test', 'description'),
                        ]),
                    ]);
                $mock->shouldReceive('match')->with('Death Note')->andReturn([]);
            }),
        );
    }

    /**
     * A basic test example.
     */
    #[TestDox('Adds some media, adds new chapters to that library and then adds new series to it')]
    public function test_media_start_add_chapters_update(): void
    {
        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 3);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 2);
        $this->assertDatabaseCount('pages', 2);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        // setup vfs for the new detection
        $this->vfs->createDirectory('/storage1/Bakuman/Chapter 3', true);
        $this->vfs->createDirectory('/storage2/Bakuman/Chapter 3', true);
        $this->vfs->createFile('/storage1/Bakuman/Chapter 3/001.jpg', '');
        $this->vfs->createFile('/storage2/Bakuman/Chapter 3/002.jpg', '');

        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 5);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 3);
        $this->assertDatabaseCount('pages', 4);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        // setup vfs for the new detection
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 1', true);
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 2', true);
        $this->vfs->createFile('/storage1/Death Note/Chapter 1/001.jpg', '');
        $this->vfs->createFile('/storage1/Death Note/Chapter 2/001.jpg', '');

        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 7);
        $this->assertDatabaseCount('series', 2);
        $this->assertDatabaseCount('chapters', 5);
        $this->assertDatabaseCount('pages', 6);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        Queue::assertPushed(DownloadResources::class, 3);
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

        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 7);
        $this->assertDatabaseCount('series', 2);
        $this->assertDatabaseCount('chapters', 5);
        $this->assertDatabaseCount('pages', 6);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        Queue::assertPushed(DownloadResources::class);
    }

    public function test_media_removal(): void
    {
        // use whole media creation test as base to simplify this one a bit
        $this->test_media_create();

        $this->vfs->container()->remove('storage1/Bakuman/Chapter 3', true);
        $this->vfs->container()->remove('storage1/Death Note', true);

        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 2);
        $this->assertDatabaseCount('chapters_scans', 4);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 3);
        $this->assertDatabaseCount('pages', 3);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        Queue::assertPushed(DownloadResources::class, 2);
    }

    public function test_media_zip(): void
    {
        // create a mock zip file and add it to the vfs
        $zip = new \PhpZip\ZipFile();
        $zip->addFromString('Chapter 3/001.jpg', '');
        $zip->addFromString('Chapter 3/002.5.jpg', '');
        $zip->addFromString('Chapter 3/003.doc', '');
        $zip->addFromString('Chapter 3/Invalid filename.jpg', '');
        $zip->addFromString('Invalid chapter/003.jpg', '');
        $zip->addFromString('__MACOSX/dummydata', '');

        $this->vfs->createFile('/storage1/Bakuman.zip', $zip->outputAsString());

        $zip = new \PhpZip\ZipFile();
        $zip->addFromString('__MACOSX/dummydata', '');
        $zip->addFromString('001.jpg', '');

        $this->vfs->createFile('/storage1/Bakuman/Chapter 4.zip', $zip->outputAsString());

        $this->dispatchScanMedia();

        // check that proper records were created
        $this->assertDatabaseCount('series_scans', 3);
        $this->assertDatabaseCount('chapters_scans', 5);
        $this->assertDatabaseCount('series', 1);
        $this->assertDatabaseCount('chapters', 4);
        $this->assertDatabaseCount('pages', 5);
        $this->assertDatabaseCount('staff', 1);
        $this->assertDatabaseCount('cover_download_queue', 2);

        Queue::assertPushed(DownloadResources::class);
    }
}
