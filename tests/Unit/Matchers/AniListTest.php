<?php

namespace Tests\Unit\Matchers;

use App\Media\Matcher\Sources\AniListSource;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AniListTest extends TestCase
{
    public function test_success_response(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(8, $value->extrainfo);
    }

    public function test_failed_match(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/failed-no-match.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_failed_response(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_failed_no_data(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/failed-no-data.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_failed_no_manga_data(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/failed-no-manga-data.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_failed_no_manga_data_object(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/failed-no-manga-data-object.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_success_no_staff_data(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman-without-staff.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(0, $value->extrainfo);
    }

    public function test_success_malformed_staff_data(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman-staff-malformed.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(0, $value->extrainfo);
    }

    public function test_success_malformed_staff_data_2(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman-staff-malformed-2.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(0, $value->extrainfo);
    }

    public function test_success_malformed_staff_data_3(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman-staff-malformed-3.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(0, $value->extrainfo);
    }

    public function test_success_malformed_staff_data_4(): void
    {
        Http::fake([
            AniListSource::ANILIST_URL => Http::response(
                file_get_contents(__DIR__.'/../../fixtures/anilist/bakuman-staff-malformed-4.json') ?: '',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $matcher = new AniListSource;
        $result = $matcher->match('Bakuman');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $value = reset($result);

        $this->assertIsObject($value);

        $this->assertCount(4, $value->genres);
        $this->assertCount(0, $value->extrainfo);
    }
}
