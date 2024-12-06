<?php

namespace App\Media\Matcher\Sources;

use App\Media\Matcher\Data\AuthorMatch;
use Illuminate\Support\Facades\Http;

class AniListSource implements Source
{
    const string ANILIST_URL = 'https://graphql.anilist.co';

    private function buildGraphQLmatch(): string
    {
        return '
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
    }

    public function match(string $search): array
    {
        $request = [
            'variables' => [
                'search' => $search,
            ],
            'query' => $this->buildGraphQLmatch(),
        ];

        $response = Http::acceptJson()->asJson()->post(self::ANILIST_URL, $request);
        $result = $response->json();

        $resultList = [];

        if (! is_array($result)) {
            return [];
        }

        if (! array_key_exists('data', $result)) {
            return [];
        }

        if (! array_key_exists('MANGA', $result['data'])) {
            return [];
        }

        $series = $result['data']['MANGA'];
        $authors = [];

        if (is_null($series)) {
            return [];
        }

        foreach ($series['staff']['edges'] as $staff) {
            if (! array_key_exists('node', $staff)) {
                continue;
            }
            if (! array_key_exists('name', $staff['node'])) {
                continue;
            }
            if (! array_key_exists('full', $staff['node']['name'])) {
                continue;
            }
            if (! array_key_exists('role', $staff)) {
                continue;
            }

            $authors[] = new AuthorMatch(
                $staff['role'],
                $staff['node']['name']['full'],
                $staff['node']['image']['large'],
                $staff['node']['description'] ?? '',
            );
        }

        $startDate = $series['startDate']['year'].'/'.$series['startDate']['month'].'/'.$series['startDate']['day'];
        $endDate = '';

        if (! is_null($series['endDate']['year'])) {
            $endDate = $series['endDate']['year'].'/'.$series['endDate']['month'].'/'.$series['endDate']['day'];
        }

        $resultList[] = new \App\Media\Matcher\Data\SeriesMatch(
            $series['id'],
            $series['title']['userPreferred'],
            $series['coverImage']['large'] ?? '',
            $series['description'] ?? '',
            $series['genres'] ?? [],
            $series['averageScore'] ?? 0,
            $startDate,
            $endDate,
            $authors
        );

        return $resultList;
    }
}
