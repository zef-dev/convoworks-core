<?php

namespace Convo\FuzzySearch;

use Convo\Core\Util\Test\ConvoTestCase;

class FuzzySearchTest extends ConvoTestCase
{

    /**
     * @dataProvider fuzzySearchMatchProvider
     */
    public function testFuzzySearch($expected, $listOfSongs, $searchTerm)
    {
        $actual = $this->_getSongIndex($listOfSongs, $searchTerm);
        $this->assertEquals($expected, $actual);
    }

    private function _getSongIndex($songData, $searchTerm) {
        $searchQueryRating = [];
        foreach ($songData as $key => $song) {
            $this->_logger->info('Analyzing song ['.$song.']');
            $cleanSongData = preg_replace('/[^\da-z ]/i', '', $song);

            $this->_logger->info('Analyzing clean song ['.$cleanSongData.']');
            $fuzzyMatchScore = $this->_getSearchTermMatchScore(
                 preg_split('/\s+/', strtolower($searchTerm)),
                 preg_split('/\s+/', strtolower($cleanSongData))
            );
            if ($fuzzyMatchScore > 50) {
                $searchQueryRating[$key] = $fuzzyMatchScore;
            }
        }

        $index = -1;
        if (!empty($searchQueryRating)) {
            $largestTextSimilarity = max($searchQueryRating);
            $index = array_search($largestTextSimilarity, $searchQueryRating);
        }

        return $index;
    }

    private function _getSearchTermMatchScore($queryWords, $targetWords) {
        $score = 0;
        $queryWordsCount = 0;
        $matchedQueryWordsCount = 0;

        foreach ($queryWords as $queryWord) {
            $queryWordsCount++;
            foreach ($targetWords as $targetWord) {
                similar_text($queryWord, $targetWord, $percentage);
                // add score in percentage when the strings do fuzzy match
                if ($percentage > 75) {
                    $matchedQueryWordsCount++;
                    $score += round($percentage, 2);
                }
            }
        }

        $missedQueryWordsPercentage = round(($matchedQueryWordsCount / $queryWordsCount) * 100, 2) * ($queryWordsCount - $matchedQueryWordsCount);
        $score = $score - $missedQueryWordsPercentage;

        $this->_logger->info('Got score ['.$score.'] with matched query words count ['.$matchedQueryWordsCount.'], query words count ['.$queryWordsCount.'] and missed query words percentage ['.$missedQueryWordsPercentage.']');
        $this->_logger->info('Got final score ['.$score.']');

        return $score;
    }

    private function _getSongsData() {
        return json_decode(file_get_contents(__DIR__ . './data/songs/songs_playlist.json'));
    }

    public function fuzzySearchMatchProvider()
    {
        return [
            'Fail to find any song' => [
                -1,
                $this->_getSongsData(),
                "Chris Cash"
            ],
            'Get the first song of the playlist' => [
                0,
                $this->_getSongsData(),
                'I think you are freaky'
            ],
            'Get the first song of the playlist variant two' => [
                0,
                $this->_getSongsData(),
                'I think you freaky'
            ],
            "Get Die Antwoord Dis Iz Why I'm Hot" => [
                1,
                $this->_getSongsData(),
                'zef remix'
            ],
            'Get the first available song of Steve Cash' => [
                5,
                $this->_getSongsData(),
                'Steve Cash'
            ],
            'Get Johnny Cash Im An Easy Rider' => [
                7,
                $this->_getSongsData(),
                "I'm An Easy Rider"
            ],
            'Get Riva Rock Me' => [
                8,
                $this->_getSongsData(),
                "RIVA"
            ],
            "Get Mike Teardrop Trio Hangin' Around" => [
                9,
                $this->_getSongsData(),
                "Hanging Around"
            ],
            "Get The Lennerockers The Unknown Picture - Live" => [
                10,
                $this->_getSongsData(),
                "Unknown Picture Live"
            ],
            "Get Armin van Buuren, W&W D# Fat variant one" => [
                11,
                $this->_getSongsData(),
                "D Fat"
            ],
            "Get Armin van Buuren, W&W D# Fat variant two" => [
                11,
                $this->_getSongsData(),
                "Armin van Buuren D Fat"
            ],
            "Get Armin van Buuren, W&W D# Fat variant three" => [
                11,
                $this->_getSongsData(),
                "armin van buuren defect"
            ],
            "Get The Beach Boys Surfin' U.S.A." => [
                12,
                $this->_getSongsData(),
                "surfing USA"
            ],
            "Get Solence Good F**King Music" => [
                13,
                $this->_getSongsData(),
                "good fucking music"
            ],
            "Get MOTi, Domastic Bangalore" => [
                14,
                $this->_getSongsData(),
                "Bangalore"
            ],
            "Get first song of 666" => [
                15,
                $this->_getSongsData(),
                "666"
            ],
            "Get 666 ALARMA" => [
                15,
                $this->_getSongsData(),
                "alarm"
            ],
            "Get 666 Bomba!" => [
                16,
                $this->_getSongsData(),
                "bumba"
            ],
            "Get M.I.A. Paper Planes" => [
                17,
                $this->_getSongsData(),
                "Mia"
            ],
            "Get M.I.A. Paper Planes variant two" => [
                17,
                $this->_getSongsData(),
                "Mia"
            ],
            "Get M.I.A. Bad Girls" => [
                18,
                $this->_getSongsData(),
                "Bad Girls"
            ],
            "Could not get M.I.A. P.O.W.A" => [
                -1,
                $this->_getSongsData(),
                "pova"
            ],
            "Get M.I.A. P.O.W.A" => [
                19,
                $this->_getSongsData(),
                "powa"
            ]
        ];
    }
}
