<?php

namespace App\Media\Matcher\Sources;

use App\Media\Matcher\Data\AuthorMatch;
use App\Media\Matcher\Data\SeriesMatch;
use Illuminate\Support\Facades\Http;

class AniListSource implements Source
{
    const string ANILIST_URL = 'https://graphql.anilist.co';

    const string MATCHER_NAME = 'anilist';

    const string MATCH_REQUEST = '
query (
    $isAdult: Boolean = false,
    $search: String,
) {
    MANGA: Media (
        type: MANGA,
        search: $search,
        sort: SEARCH_MATCH,
        isAdult: $isAdult
    ) {
        id
        title {
            userPreferred
        }
        coverImage {
            large: extraLarge
            color
        }
        startDate {
            year
            month
            day
        }
        endDate {
            year
            month
            day
        }
        season
        description
        type
        format
        status
        genres
        isAdult
        averageScore
        popularity
        mediaListEntry {
            status
        }
        nextAiringEpisode {
            airingAt
            timeUntilAiring
            episode
        }
        studios (isMain: true) {
            edges {
                isMain
                node {
                    id
                    name
                }
            }
        }
        staff (perPage: 8) {
            edges {
                id
                role
                node {
                    id
                    name {
                        full
                    }
                    image {
                        large
                    }
                    description
                }
            }
        }
    }
}';

    public function match(string $search): array
    {
        $request = [
            'variables' => [
                'search' => $search,
            ],
            'query' => self::MATCH_REQUEST,
        ];

        try {
            $response = Http::acceptJson()->asJson()->post(self::ANILIST_URL, $request);

            if ($response->failed()) {
                \Log::warning('HTTP request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [];
            }

            $result = $response->json();
        } catch (\Exception $exception) {
            \Log::error('Error occurred during HTTP request.', ['exception' => $exception->getMessage()]);
            return [];
        }

        if (!is_array($result) || !isset($result['data'])) {
            \Log::warning('The API response does not contain the "data" key.', ['response' => $result]);
            return [];
        }

        if (!is_array($result['data']) || !isset($result['data']['MANGA'])) {
            \Log::warning('The "data.MANGA" key is missing or has an unexpected format.', ['response' => $result]);
            return [];
        }

        try {
            return [$this->buildSeries($result['data']['MANGA'])];
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            \Log::error('Error occurred during series data parsing.', ['exception' => $exception->getMessage()]);
            return [];
        }
    }

    /**
     * @throws \Throwable
     */
    private function buildSeries(mixed $series): SeriesMatch
    {
        throw_if(
            !is_array($series) ||
                !array_key_exists('id', $series) ||
                !is_int($series['id']) ||
                !array_key_exists('startDate', $series) ||
                !is_array($series['startDate']) ||
                !array_key_exists('title', $series) ||
                !is_array($series['title']) ||
                !array_key_exists('averageScore', $series) ||
                !is_int($series['averageScore']) ||
                !array_key_exists('userPreferred', $series['title']) ||
                !is_string($series['title']['userPreferred']),
            new \Exception('Invalid series data.'),
        );

        $authors = [];
        $genres = [];
        $coverImage = '';
        $description = '';

        if (array_key_exists('staff', $series) && is_array($series['staff']) && is_array($series['staff']['edges'])) {
            $authors = $this->buildAuthors($series['staff']['edges']);
        }

        if (array_key_exists('description', $series) && is_string($series['description'])) {
            $description = $series['description'];
        }

        if (is_array($series['coverImage']) && is_string($series['coverImage']['large'])) {
            $coverImage = $series['coverImage']['large'];
        }

        if (is_array($series['genres'])) {
            foreach ($series['genres'] as $genre) {
                if (!is_string($genre)) {
                    continue;
                }

                $genres[] = $genre;
            }
        }

        $startDate = $this->formatDate($series['startDate']);
        $endDate = $this->formatDate($series['endDate'] ?? []);

        return new SeriesMatch(
            $series['id'],
            self::MATCHER_NAME,
            $series['title']['userPreferred'],
            $coverImage,
            $description,
            $genres,
            $series['averageScore'],
            $startDate,
            $endDate,
            $authors,
        );
    }

    /**
     * @param array<mixed,mixed> $staffEdges
     *
     * @return AuthorMatch[]
     */
    private function buildAuthors(array $staffEdges): array
    {
        $authors = [];

        foreach ($staffEdges as $staff) {
            $author = $this->parseAuthor($staff);
            if ($author instanceof AuthorMatch) {
                $authors[] = $author;
            }
        }

        return $authors;
    }

    private function parseAuthor(mixed $staff): null|AuthorMatch
    {
        if (
            !is_array($staff) ||
                !array_key_exists('id', $staff) ||
                !is_int($staff['id']) ||
                !array_key_exists('role', $staff) ||
                !is_string($staff['role']) ||
                !array_key_exists('node', $staff) ||
                !is_array($staff['node']) ||
                !array_key_exists('name', $staff['node']) ||
                !is_array($staff['node']['name']) ||
                !array_key_exists('image', $staff['node']) ||
                !is_array($staff['node']['image']) ||
                !array_key_exists('full', $staff['node']['name']) ||
                !is_string($staff['node']['name']['full']) ||
                !array_key_exists('large', $staff['node']['image']) ||
                !is_string($staff['node']['image']['large'])
        ) {
            return null;
        }

        $description = '';

        if (array_key_exists('description', $staff['node']) && is_string($staff['node']['description'])) {
            $description = $staff['node']['description'];
        }

        return new AuthorMatch(
            $staff['id'],
            $staff['role'],
            $staff['node']['name']['full'],
            $staff['node']['image']['large'],
            $description,
        );
    }

    private function formatDate(mixed $date): string
    {
        if (is_array($date) && is_int($date['year']) && is_int($date['month']) && is_int($date['day'])) {
            return sprintf('%d/%d/%d', $date['year'], $date['month'], $date['day']);
        }

        return '';
    }
}
