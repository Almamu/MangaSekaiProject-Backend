<?php

namespace Tests\Feature;

use App\Jobs\DownloadResources;
use App\Models\CoverDownloadQueue;
use App\Models\Serie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DownloadResourceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * A basic feature test example.
     */
    public function test_image_download(): void
    {
        Http::fake([
            'https://s4.anilist.co/file/anilistcdn/staff/large/n96888-S7t8RBq40Y70.png' => Http::response('IMAGE CONTENTS HERE', 200, ['Content-Type' => 'image/png']),
            'https://non-existant-domain.test' => Http::response(status: 404)
        ]);

        // add one serie
        $bakuman = Serie::insert([
            'matcher' => 'none',
            'name' => 'Bakuman',
            'description' => '',
            'synced' => true,
            'external_id' => 1,
        ]);

        // add one serie
        $deathnote = Serie::insert([
            'matcher' => 'none',
            'name' => 'Death Note',
            'description' => '',
            'synced' => true,
            'external_id' => 1,
        ]);

        CoverDownloadQueue::insert([
            'serie_id' => $bakuman,
            'url' => 'https://s4.anilist.co/file/anilistcdn/staff/large/n96888-S7t8RBq40Y70.png',
        ]);

        CoverDownloadQueue::insert([
            'serie_id' => $deathnote,
            'url' => 'https://non-existant-domain.test',
        ]);

        DownloadResources::dispatchSync();

        $this->assertDatabaseCount('cover_download_queue', 1);
        $this->assertDatabaseHas('series', ['image' => 'IMAGE CONTENTS HERE']);
    }
}
