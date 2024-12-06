<?php

namespace Tests\Feature;

use App\Jobs\ScanMedia;
use App\Media\Matcher\Data\AuthorMatch;
use App\Media\Matcher\Data\SeriesMatch;
use App\Media\Matcher\Matcher;
use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PageReadTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private \VirtualFileSystem\FileSystem $vfs;

    private string $filecontents;

    protected function setUp(): void
    {
        parent::setUp();

        // setup a disk and some manga and scan it
        $this->vfs = new \VirtualFileSystem\FileSystem;

        $contents = file_get_contents(base_path('tests/Fixtures/media/sample-page.png'));

        $this->assertIsString($contents);

        $this->filecontents = $contents;

        $this->vfs->createDirectory('/storage1/Bakuman/Chapter 1', true);
        $this->vfs->createFile('/storage1/Bakuman/Chapter 1/001.png', $this->filecontents);

        $zip = new \PhpZip\ZipFile;
        $zip->addFromString('001.png', $this->filecontents);

        $this->vfs->createFile('/storage1/Bakuman/Chapter 2.zip', $zip->outputAsString());

        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage1'),
        ]);

        // mock answers from the matcher so we do not call outside
        $this->instance(
            Matcher::class,
            Mockery::mock(Matcher::class, function (MockInterface $mock) {
                $mock->shouldReceive('match')
                    ->with('Bakuman.zip')
                    ->andReturn([]);
                $mock->shouldReceive('match')
                    ->with('Bakuman')
                    ->andReturn([new SeriesMatch(
                        1000, 'anilist', '', 'http://dummy.test', '', [], 1, '', '', [
                            new AuthorMatch(1, 'role', 'name', 'http://dummy.test', 'description'),
                        ]
                    )]);
            })
        );

        // dispatch a scan so the data is available
        ScanMedia::dispatchSync();
    }

    public function test_unauthenticated(): void
    {
        // try to read anything without authentication first
        $response = $this->get('/api/v1/series');

        $response->assertStatus(401);
    }

    public function test_wrong_authentication_and_ratelimit(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'TOO_MANY_ATTEMPTS']);
    }

    /**
     * A basic feature test example.
     */
    public function test_list_series(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['token', 'token_type', 'expires_in']))
            ->json('token');

        $response = $this->get('/api/v1/series', ['Authorization' => 'Bearer '.$token]);

        $series = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                'data', 'current_page', 'records_per_page', 'last_page', 'total',
            ]))
            ->json('data');

        $this->assertIsArray($series);
        $this->assertCount(1, $series);

        // get first series id and request the list of chapters
        $serie = $series[0];

        $response = $this->get('/api/v1/series/'.$serie['id'].'/chapters', ['Authorization' => 'Bearer '.$token]);

        $chapters = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                'data', 'current_page', 'records_per_page', 'last_page', 'total',
            ]))
            ->json('data');

        $this->assertIsArray($chapters);
        $this->assertCount(2, $chapters);

        $chapter = $chapters[0];

        // get all chapter's  pages (should be just one)
        $response = $this->get('/api/v1/series/'.$serie['id'].'/chapters/'.$chapter['id'].'/pages', ['Authorization' => 'Bearer '.$token]);

        $pages = $response
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->json();

        $this->assertIsArray($pages);
        $this->assertCount(1, $pages);

        // finally, get one page
        $page = $pages[0];

        $this->assertIsString($page);

        $response = $this->get($page, ['Authorization' => 'Bearer '.$token]);

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png')
            ->assertStreamedContent($this->filecontents);

        // try the chapter from the zip file now
        $chapter = $chapters[1];

        // get all chapter's  pages (should be just one)
        $response = $this->get('/api/v1/series/'.$serie['id'].'/chapters/'.$chapter['id'].'/pages', ['Authorization' => 'Bearer '.$token]);

        $pages = $response
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->json();

        $this->assertIsArray($pages);
        $this->assertCount(1, $pages);

        // finally, get one page
        $page = $pages[0];

        $this->assertIsString($page);

        $response = $this->get($page, ['Authorization' => 'Bearer '.$token]);

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png')
            ->assertStreamedContent($this->filecontents);
    }
}
