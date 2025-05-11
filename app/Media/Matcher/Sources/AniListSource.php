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
                !is_int($series['id']) ||
                !is_array($series['startDate']) ||
                !is_array($series['title']) ||
                !is_int($series['averageScore']) ||
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

        return new \App\Media\Matcher\Data\SeriesMatch(
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
            if ($author instanceof \App\Media\Matcher\Data\AuthorMatch) {
                $authors[] = $author;
            }
        }

        return $authors;
    }

    private function parseAuthor(mixed $staff): null|AuthorMatch
    {
        if (
            !is_array($staff) ||
                !is_int($staff['id']) ||
                !is_string($staff['role']) ||
                !is_array($staff['node']) ||
                !is_array($staff['node']['name']) ||
                !is_array($staff['node']['image']) ||
                !is_string($staff['node']['name']['full']) ||
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
