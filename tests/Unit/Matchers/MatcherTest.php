<?php

namespace Tests\Unit\Matchers;

use App\Media\Matcher\Data\SeriesMatch;
use App\Media\Matcher\Matcher;
use App\Media\Matcher\Sources\Source;
use Tests\TestCase;

class DummySource implements Source
{
    public function match(string $search): array
    {
        if ($search === 'first') {
            return [
                new SeriesMatch(0, 'dummy', '', '', '', [], 0, '', '', []),
            ];
        }

        return [];
    }
}

class DummySource2 implements Source
{
    public function match(string $search): array
    {
        if ($search === 'second') {
            return [
                new SeriesMatch(0, 'dummy2', '', '', '', [], 0, '', '', []),
            ];
        }

        return [];
    }
}

class MatcherTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_matcher(): void
    {
        $matcher = new Matcher([
            DummySource::class,
            DummySource2::class,
        ], $this->app);

        $result = $matcher->match('missing');
        $this->assertCount(0, $result);

        $result = $matcher->match('second');
        $this->assertCount(1, $result);

        $result = $matcher->match('first');
        $this->assertCount(1, $result);
    }
}
