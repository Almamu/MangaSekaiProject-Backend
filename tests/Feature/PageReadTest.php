<?php

namespace Tests\Feature;

use App\Jobs\DownloadResources;
use App\Jobs\ScanMedia;
use App\Media\Matcher\Sources\AniListSource;
use App\Models\Settings;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PageReadTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private \VirtualFileSystem\FileSystem $vfs;

    private string $imageContents;

    private string $bakumanContent;

    protected function setUp(): void
    {
        parent::setUp();

        // setup a disk and some manga and scan it
        $this->vfs = new \VirtualFileSystem\FileSystem();

        $contents = file_get_contents(base_path('tests/Fixtures/media/sample-page.png'));
        $bakumanContent = file_get_contents(base_path('tests/Fixtures/anilist/bakuman.json'));

        $this->assertIsString($contents);
        $this->assertIsString($bakumanContent);

        $this->imageContents = $contents;
        $this->bakumanContent = $bakumanContent;

        $this->vfs->createDirectory('/storage1/Bakuman/Chapter 1', true);
        $this->vfs->createDirectory('/storage1/Death Note/Chapter 1', true);
        $this->vfs->createFile('/storage1/Bakuman/Chapter 1/001.png', $this->imageContents);
        $this->vfs->createFile('/storage1/Death Note/Chapter 1/001.png', $this->imageContents);

        $zip = new \PhpZip\ZipFile();
        $zip->addFromString('001.png', $this->imageContents);

        $this->vfs->createFile('/storage1/Bakuman/Chapter 2.zip', $zip->outputAsString());

        Settings::addScannerDir([
            'driver' => 'local',
            'root' => $this->vfs->path('storage1'),
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() === AniListSource::ANILIST_URL && $request->isJson()) {
                $data = $request->data();

                if (isset($data['variables']['search']) && str_starts_with($data['variables']['search'], 'Bakuman')) {
                    return Http::response($this->bakumanContent, headers: ['Content-Type' => 'application/json']);
                }

                return Http::response();
            }

            return Http::response($this->imageContents, headers: ['Content-Type' => 'image/png']);
        });

        // dispatch a scan so the data is available
        ScanMedia::dispatchSync($this->app->make(\Illuminate\Config\Repository::class));
        // run one of the download resources instances to fetch the fake covers
        DownloadResources::dispatchSync();
    }

    public function test_read_data_for_one_serie(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'token',
                'token_type',
                'expires_in',
            ]))
            ->json('token');

        $response = $this->get('/api/v1/series', ['Authorization' => 'Bearer ' . $token]);

        $series = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'data',
                'current_page',
                'records_per_page',
                'last_page',
                'total',
            ]))
            ->json('data');

        $this->assertIsArray($series);
        $this->assertCount(2, $series);

        // get first series id and request the list of chapters
        $serie = $series[0];

        $this->assertIsString($serie['image_url']);
        $this->assertIsInt($serie['id']);

        // read cover data
        $response = $this->get($serie['image_url'], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)->assertHeader('Content-Type', 'image/png')->assertContent($this->imageContents);

        // get specific serie's info and ensure all the metadata is present
        $response = $this->get('/api/v1/series/' . $serie['id'], ['Authorization' => 'Bearer ' . $token]);

        $serie = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'id',
                'matcher',
                'blocked_fields',
                'staff',
                'genres',
                'image_url',
                'synced',
                'description',
                'pages_count',
                'chapter_count',
                'name',
                'created_at',
                'updated_at',
            ]))
            ->json();

        // find one of the staff members and request it
        $this->assertIsArray($serie['staff']);
        $this->assertCount(8, $serie['staff']);

        $response = $this->get('/api/v1/series/' . $serie['id'] . '/chapters', ['Authorization' => 'Bearer ' . $token]);

        $chapters = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'data',
                'current_page',
                'records_per_page',
                'last_page',
                'total',
            ]))
            ->json('data');

        $this->assertIsArray($chapters);
        $this->assertCount(2, $chapters);

        $chapter = $chapters[0];

        // get all chapter's  pages (should be just one)
        $response = $this->get('/api/v1/series/' . $serie['id'] . '/chapters/' . $chapter['id'] . '/pages', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $pages = $response->assertStatus(200)->assertJsonIsArray()->json();

        $this->assertIsArray($pages);
        $this->assertCount(1, $pages);

        // finally, get one page
        $page = $pages[0];

        $this->assertIsString($page);

        $response = $this->get($page, ['Authorization' => 'Bearer ' . $token]);

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png')
            ->assertStreamedContent($this->imageContents);

        // try the chapter from the zip file now
        $chapter = $chapters[1];

        // get all chapter's  pages (should be just one)
        $response = $this->get('/api/v1/series/' . $serie['id'] . '/chapters/' . $chapter['id'] . '/pages', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $pages = $response->assertStatus(200)->assertJsonIsArray()->json();

        $this->assertIsArray($pages);
        $this->assertCount(1, $pages);

        // finally, get one page
        $page = $pages[0];

        $this->assertIsString($page);

        $response = $this->get($page, ['Authorization' => 'Bearer ' . $token]);

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png')
            ->assertStreamedContent($this->imageContents);
    }

    public function test_read_staff_data(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'token',
                'token_type',
                'expires_in',
            ]))
            ->json('token');

        $response = $this->get('/api/v1/staff', ['Authorization' => 'Bearer ' . $token]);

        $staff = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'data',
                'current_page',
                'records_per_page',
                'last_page',
                'total',
            ]))
            ->json('data');

        $this->assertIsArray($staff);
        $this->assertCount(8, $staff);

        $staff = $staff[0];

        // get specific serie's info and ensure all the metadata is present
        $response = $this->get('/api/v1/staff/' . $staff['id'], ['Authorization' => 'Bearer ' . $token]);

        $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'id',
                'name',
                'description',
                'image_url',
                'created_at',
                'updated_at',
            ]));

        $response = $this->get($staff['image_url'], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)->assertHeader('Content-Type', 'image/png')->assertContent($this->imageContents);
    }

    public function test_read_inexistent_cover_data_for_serie(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'token',
                'token_type',
                'expires_in',
            ]))
            ->json('token');

        $response = $this->get('/api/v1/series', ['Authorization' => 'Bearer ' . $token]);

        $series = $response
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->hasAll([
                'data',
                'current_page',
                'records_per_page',
                'last_page',
                'total',
            ]))
            ->json('data');

        $this->assertIsArray($series);
        $this->assertCount(2, $series);

        // get the second series which doesn't have a cover and try to fetch it
        $serie = $series[1];

        $this->assertNull($serie['image_url']);
        $this->assertIsInt($serie['id']);

        $response = $this->get('/images/series/cover/' . $serie['id'], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(404);
    }
}
