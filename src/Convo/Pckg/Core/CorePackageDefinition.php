<?php

declare(strict_types=1);

namespace Convo\Pckg\Core;

use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Intent\SystemEntity;
use Convo\Core\Intent\EntityModel;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\Intent\SimpleEntityValueParser;



class CorePackageDefinition extends AbstractPackageDefinition
{
    const NAMESPACE    =    'convo-core';
    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var CacheInterface
     */
    private $_cache;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Convo\Core\Util\IHttpFactory $httpFactory,
        \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory,
        CacheInterface $cache
    ) {
        $this->_httpFactory                =    $httpFactory;
        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_cache                   =   $cache;

        parent::__construct($logger, self::NAMESPACE, __DIR__);

        $this->registerTemplate(__DIR__ . '/basic.template.json');
        $this->registerTemplate(__DIR__ . '/blank.template.json');
        $this->registerTemplate(__DIR__ . '/convo-daily-quotes.template.json');
    }

    protected function _initIntents()
    {
        return $this->_loadIntents(__DIR__ . '/system-intents.json');
    }

    protected function _initEntities()
    {
        $entities  =    [];
        $entities['number'] =   new SystemEntity('number');
        $entities['number']->setPlatformModel('amazon', new EntityModel('AMAZON.NUMBER', true));
        $entities['number']->setPlatformModel('dialogflow', new EntityModel('@sys.number-integer', true));
        $entities['number']->setPlatformModel('dialogflow_es', new EntityModel('@sys.number-integer', true));

        $entities['ordinal'] =   new SystemEntity('ordinal');
        $entities['ordinal']->setPlatformModel('amazon', new EntityModel('AMAZON.Ordinal', true));
        $entities['ordinal']->setPlatformModel('dialogflow', new EntityModel('@sys.ordinal', true));
        $entities['ordinal']->setPlatformModel('dialogflow_es', new EntityModel('@sys.ordinal', true));

        $entities['city'] = new SystemEntity('city');
        $entities['city']->setPlatformModel('amazon', new EntityModel('AMAZON.City', true));
        $entities['city']->setPlatformModel('dialogflow', new EntityModel('@sys.geo-city', true));
        $entities['city']->setPlatformModel('dialogflow_es', new EntityModel('@sys.geo-city', true));

        $entities['country'] = new SystemEntity('country');
        $entities['country']->setPlatformModel('amazon', new EntityModel('AMAZON.Country', true));
        $entities['country']->setPlatformModel('dialogflow', new EntityModel('@sys.geo-country', true));
        $entities['country']->setPlatformModel('dialogflow_es', new EntityModel('@sys.geo-country', true));

        $entities['any'] = new SystemEntity('any');
        $entities['any']->setPlatformModel('amazon', new EntityModel('AMAZON.SearchQuery', true));
        $entities['any']->setPlatformModel('dialogflow', new EntityModel('@sys.any', true));
        $entities['any']->setPlatformModel('dialogflow_es', new EntityModel('@sys.any', true));

        $entities['person'] = new SystemEntity('person');
        $entities['person']->setPlatformModel('amazon', new EntityModel('AMAZON.FirstName', true));
        $entities['person']->setPlatformModel('dialogflow', new EntityModel('@sys.person', true));
        $entities['person']->setPlatformModel('dialogflow_es', new EntityModel('@sys.person', true));

        $entities['person_first_and_lastname'] = new SystemEntity('person_first_and_lastname');
        $entities['person_first_and_lastname']->setPlatformModel('amazon', new EntityModel('AMAZON.Person', true));
        $entities['person_first_and_lastname']->setPlatformModel('dialogflow', new EntityModel('@sys.person', true));
        $entities['person_first_and_lastname']->setPlatformModel('dialogflow_es', new EntityModel('@sys.person', true));

        $entities['artist'] = new SystemEntity('artist');
        $entities['artist']->setPlatformModel('amazon', new EntityModel('AMAZON.Artist', true));
        $entities['artist']->setPlatformModel('dialogflow', new EntityModel('@sys.music-artist', true));
        $entities['artist']->setPlatformModel('dialogflow_es', new EntityModel('@sys.music-artist', true));

        $entities['song'] = new SystemEntity('song');
        $entities['song']->setPlatformModel('amazon', new EntityModel('AMAZON.MusicRecording', true));
        $entities['song']->setPlatformModel('dialogflow', new EntityModel('@sys.any', true));
        $entities['song']->setPlatformModel('dialogflow_es', new EntityModel('@sys.any', true));

        $entities['genre'] = new SystemEntity('genre');
        $entities['genre']->setPlatformModel('amazon', new EntityModel('AMAZON.Genre', true));
        $entities['genre']->setPlatformModel('dialogflow', new EntityModel('@sys.music-genre', true));
        $entities['genre']->setPlatformModel('dialogflow_es', new EntityModel('@sys.music-genre', true));

        $entities['music_playlist'] = new SystemEntity('music_playlist');
        $entities['music_playlist']->setPlatformModel('amazon', new EntityModel('AMAZON.SearchQuery', true));
        $entities['music_playlist']->setPlatformModel('dialogflow', new EntityModel('@sys.any', true));
        $entities['music_playlist']->setPlatformModel('dialogflow_es', new EntityModel('@sys.any', true));

        $entities['date'] = new SystemEntity('date');
        $entities['date']->setPlatformModel('amazon', new EntityModel('AMAZON.DATE', true));
        $entities['date']->setPlatformModel('dialogflow', new EntityModel('@sys.date', true));
        $entities['date']->setPlatformModel('dialogflow_es', new EntityModel('@sys.date', true));

        $entities['time'] = new SystemEntity('time');
        $entities['time']->setPlatformModel('amazon', new EntityModel('AMAZON.TIME', true));
        $entities['time']->setPlatformModel('dialogflow', new EntityModel('@sys.time', true));
        $entities['time']->setPlatformModel('dialogflow_es', new EntityModel('@sys.time', true));

        $entities['color'] = new SystemEntity('color');
        $entities['color']->setPlatformModel('amazon', new EntityModel('AMAZON.Color', true));
        $entities['color']->setPlatformModel('dialogflow', new EntityModel('@sys.color', true));
        $entities['color']->setPlatformModel('dialogflow_es', new EntityModel('@sys.color', true));

        $entities['language'] = new SystemEntity('language');
        $entities['language']->setPlatformModel('amazon', new EntityModel('AMAZON.Language', true));
        $entities['language']->setPlatformModel('dialogflow', new EntityModel('@sys.language', true));
        $entities['language']->setPlatformModel('dialogflow_es', new EntityModel('@sys.language', true));

        $entities['airport'] = new SystemEntity('airport');
        $entities['airport']->setPlatformModel('amazon', new EntityModel('AMAZON.Airport', true));
        $entities['airport']->setPlatformModel('dialogflow', new EntityModel('@sys.airport', true));
        $entities['airport']->setPlatformModel('dialogflow_es', new EntityModel('@sys.airport', true));

        $entities['duration'] = new SystemEntity('duration');
        $entities['duration']->setPlatformModel('amazon', new EntityModel('AMAZON.DURATION', true));
        $entities['duration']->setPlatformModel('dialogflow', new EntityModel('@sys.duration', true));
        $entities['duration']->setPlatformModel('dialogflow_es', new EntityModel('@sys.duration', true));

        $entities['phone_number'] = new SystemEntity('phone_number');
        $entities['phone_number']->setPlatformModel('amazon', new EntityModel('AMAZON.PhoneNumber', true));
        $entities['phone_number']->setPlatformModel('dialogflow', new EntityModel('@sys.phone-number', true));
        $entities['phone_number']->setPlatformModel('dialogflow_es', new EntityModel('@sys.phone-number', true));

        $entities['PlaybackDirection'] = new SystemEntity('PlaybackDirection');
        $playback_direction_model = new EntityModel('PlaybackDirection', false);
        $playback_direction_model->load([
            "name" => "PlaybackDirection",
            "values" => [
                [
                    "value" => "forward",
                    "synonyms" => [
                        "ahead",
                        "onward"
                    ]
                ],
                [
                    "value" => "backward",
                    "synonyms" => [
                        "back",
                        "backwards"
                    ]
                ]
            ]
        ]);
        $entities['PlaybackDirection']->setPlatformModel('amazon', $playback_direction_model);
        $entities['PlaybackDirection']->setPlatformModel('dialogflow', $playback_direction_model);
        $entities['PlaybackDirection']->setPlatformModel('dialogflow_es', $playback_direction_model);

        $entities['postalAddress'] =   new SystemEntity('postalAddress');
        $entities['postalAddress']->setPlatformModel('amazon', new EntityModel('AMAZON.PostalAddress', true));
        $entities['postalAddress']->setPlatformModel(
            ['dialogflow_es', 'dialogflow'],
            new EntityModel('@sys.location', true, new SimpleEntityValueParser('street-address'))
        );

        return $entities;
    }

    public function getFunctions()
    {
        $functions = [];

        $functions[] = ExpressionFunction::fromPhp('count');
        $functions[] = ExpressionFunction::fromPhp('rand');
        $functions[] = ExpressionFunction::fromPhp('strtolower');
        $functions[] = ExpressionFunction::fromPhp('strtoupper');
        $functions[] = ExpressionFunction::fromPhp('ucfirst');
        $functions[] = ExpressionFunction::fromPhp('ucwords');
        $functions[] = ExpressionFunction::fromPhp('date');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('is_int');
        $functions[] = ExpressionFunction::fromPhp('is_string');
        $functions[] = ExpressionFunction::fromPhp('is_float');
        $functions[] = ExpressionFunction::fromPhp('is_long');
        $functions[] = ExpressionFunction::fromPhp('is_countable');
        $functions[] = ExpressionFunction::fromPhp('is_null');
        $functions[] = ExpressionFunction::fromPhp('intval');
        $functions[] = ExpressionFunction::fromPhp('strval');
        $functions[] = ExpressionFunction::fromPhp('ceil');
        $functions[] = ExpressionFunction::fromPhp('floor');
        $functions[] = ExpressionFunction::fromPhp('strlen');
        $functions[] = ExpressionFunction::fromPhp('array_rand');
        $functions[] = ExpressionFunction::fromPhp('array_reverse');
        $functions[] = ExpressionFunction::fromPhp('array_values');
        $functions[] = ExpressionFunction::fromPhp('urlencode');
        $functions[] = ExpressionFunction::fromPhp('str_replace');
        $functions[] = ExpressionFunction::fromPhp('array_splice');
        $functions[] = ExpressionFunction::fromPhp('is_object');
        $functions[] = ExpressionFunction::fromPhp('is_array');
        $functions[] = ExpressionFunction::fromPhp('in_array');
        $functions[] = ExpressionFunction::fromPhp('array_search');
        $functions[] = ExpressionFunction::fromPhp('array_column');
        $functions[] = ExpressionFunction::fromPhp('array_keys');
        $functions[] = ExpressionFunction::fromPhp('array_merge');
        $functions[] = ExpressionFunction::fromPhp('array_diff');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('substr');
        $functions[] = ExpressionFunction::fromPhp('stripos');
        $functions[] = ExpressionFunction::fromPhp('str_word_count');
        $functions[] = ExpressionFunction::fromPhp('strtotime');
        $functions[] = ExpressionFunction::fromPhp('time');
        $functions[] = ExpressionFunction::fromPhp('explode');
        $functions[] = ExpressionFunction::fromPhp('implode');
        $functions[] = ExpressionFunction::fromPhp('array_filter');
        $functions[] = ExpressionFunction::fromPhp('unserialize');
        $functions[] = ExpressionFunction::fromPhp('serialize');
        $functions[] = ExpressionFunction::fromPhp('trim');
        $functions[] = ExpressionFunction::fromPhp('file_get_contents');
        $functions[] = ExpressionFunction::fromPhp('file_put_contents');
        $functions[] = ExpressionFunction::fromPhp('unlink');
        $functions[] = ExpressionFunction::fromPhp('json_last_error_msg');
        $functions[] = ExpressionFunction::fromPhp('json_last_error');
        $functions[] = ExpressionFunction::fromPhp('json_encode');
        $functions[] = ExpressionFunction::fromPhp('json_decode');
        $functions[] = ExpressionFunction::fromPhp('filter_var');
        $functions[] = ExpressionFunction::fromPhp('parse_url');
        $functions[] = ExpressionFunction::fromPhp('array_slice');
        $functions[] = ExpressionFunction::fromPhp('array_chunk');
        $functions[] = ExpressionFunction::fromPhp('array_map');
        $functions[] = ExpressionFunction::fromPhp('set_time_limit');
        $functions[] = ExpressionFunction::fromPhp('number_format');
        $functions[] = ExpressionFunction::fromPhp('round');
        $functions[] = ExpressionFunction::fromPhp('preg_replace');
        $functions[] = ExpressionFunction::fromPhp('htmlentities');
        $functions[] = ExpressionFunction::fromPhp('htmlspecialchars');
        $functions[] = ExpressionFunction::fromPhp('html_entity_decode');
        $functions[] = ExpressionFunction::fromPhp('str_getcsv');
        $functions[] = ExpressionFunction::fromPhp('rawurlencode');
        $functions[] = ExpressionFunction::fromPhp('base64_encode');
        $functions[] = ExpressionFunction::fromPhp('hash_hmac');
        $functions[] = ExpressionFunction::fromPhp('uniqid');
        $functions[] = ExpressionFunction::fromPhp('http_build_query');
        $functions[] = ExpressionFunction::fromPhp('function_exists');

        $convo_val = function ($args, $data) use (&$convo_val) {
            if (is_array($data)) {
                $clean = [];

                foreach ($data as $key => $val) {
                    $clean[$key] = $convo_val($args, $val);
                }

                return $clean;
            }

            if (is_a($data, '\Zef\Zel\IValueAdapter')) {
                /** @var \Zef\Zel\IValueAdapter $data */
                return $data->get();
            }

            return $data;
        };

        $functions[] = new ExpressionFunction(
            'convo_val',
            function ($data) {
                return sprintf('convo_val(%1$data)', $data);
            },
            $convo_val
        );

        $functions[] = new ExpressionFunction(
            'call_user_func',
            function ($callback, $parameter = []) {
                return sprintf('call_user_func(%s, %s)', var_export($callback, true), var_export($parameter, true));
            },
            function ($args, $callback, $parameter = []) {
                if (!function_exists($callback)) {
                    throw new \Exception('Function "' . $callback . '" does not exists.');
                }

                if (is_null($parameter) || (is_array($parameter) && empty($parameter))) {
                    return call_user_func($callback);
                }
                if (!is_array($parameter) || !isset($parameter[0])) {
                    $this->_logger->debug('Wrapping up param [' . gettype($parameter) . '] as array');
                    $parameter = [$parameter];
                }
                return call_user_func($callback, ...$parameter);
            }
        );

        $functions[] = new ExpressionFunction(
            'call_user_func_array',
            function ($callback, $parameter = []) {
                return sprintf('call_user_func_array(%s, %s)', var_export($callback, true), var_export($parameter, true));
            },
            function ($args, $callback, $parameter = []) {
                if (!function_exists($callback)) {
                    throw new \Exception('Function "' . $callback . '" does not exists.');
                }
                if (empty($parameter)) {
                    $parameter = [];
                }
                return call_user_func_array($callback, $parameter);
            }
        );

        $functions[] = new ExpressionFunction(
            'parse_cvs_file',
            function ($path, $separator = ",") {
                return sprintf('parse_cvs_file(%s, %s)', var_export($path, true), var_export($separator, true));
            },
            function ($args, $path, $separator = ",") {
                $fp = fopen($path, 'r');
                $array = array();

                while ($row = fgetcsv($fp, null, $separator)) {
                    $array[] = $row;
                }
                fclose($fp);
                return $array;
            }
        );


        $functions[] = new ExpressionFunction(
            'array_shuffle',
            function ($array) {
                return sprintf('is_array(%1$a) ? array_shuffle(%1$a) : %1$a', $array);
            },
            function ($args, $array) {
                if (!is_array($array)) {
                    return $array;
                }

                $copy = $array;
                shuffle($copy);
                return $copy;
            }
        );

        $functions[] = new ExpressionFunction(
            'print_r',
            function ($val) {
                return sprintf('print_r(%1)', $val);
            },
            function ($args, $val) {
                return print_r($val, true);
            }
        );

        $functions[] = new ExpressionFunction(
            'array_push',
            function ($array, $item) {
                return sprintf('is_array(%1$a) ? array_push(%1$a, %2$i) : %1$a', $array, $item);
            },
            function ($args, $array, $item) {
                if (!is_array($array)) {
                    return $array;
                }

                $copy = $array;
                $copy[] = $item;
                return $copy;
            }
        );

        $functions[] = new ExpressionFunction(
            'decode_special_chars',
            function ($string) {
                return sprintf('is_string(%1$a) ? decode_special_chars(%1$a) : %1$a', $string);
            },
            function ($args, $string) {
                if (!is_string($string)) {
                    return $string;
                }

                $string = html_entity_decode($string, ENT_QUOTES);
                $string = htmlspecialchars_decode($string);
                $string = urldecode($string);
                return $string;
            }
        );

        $functions[] = new ExpressionFunction(
            'array_sort_by_field',
            function ($array, $field) {
                return sprintf('array_sort_by_field(%1$a, %2$a)', $array, $field);
            },
            function ($args, $array, $field) {
                if (!is_array($array)) {
                    $this->_logger->warning('Not an array [' . print_r($array, true) . ']');
                    return $array;
                }
                usort($array, function ($a, $b) use ($field) {
                    if ($a[$field] === $b[$field]) {
                        return 0;
                    }
                    if (is_numeric($a[$field]) && is_numeric($b[$field])) {
                        return floatval($a[$field]) > floatval($b[$field]) ? 1 : -1;
                    }
                    return strcmp($a[$field], $b[$field]);
                });
                return $array;
            }
        );

        $functions[] = new ExpressionFunction(
            'ordinal',
            function ($string) {
                return sprintf('is_numeric(%1$a) ? ordinal(%1$a) : %1$a', $string);
            },
            function ($args, $string) {
                if (!is_numeric($string)) {
                    return $string;
                }

                $integer = intval($string);
                $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');

                if ((($integer % 100) >= 11) && (($integer % 100) <= 13)) {
                    return $integer . 'th';
                } else {
                    return $integer . $ends[$integer % 10];
                }
            }
        );

        $functions[] = new ExpressionFunction(
            'human_concat',
            function ($array,  $conjunction = null) {
                return sprintf('is_array(%1$a) ? human_concat(%1$a, %2$a) : %1$a', $array, $conjunction);
            },

            function ($args, $array, $conjunction = null) {
                if (!is_array($array)) {
                    return $array;
                }

                $last  = array_slice($array, -1);
                $first = join(', ', array_slice($array, 0, -1));
                $both  = array_filter(array_merge(array($first), $last), 'strlen');

                if ($conjunction) {
                    return join(' ' . $conjunction . ' ', $both);
                }

                return join(', ', $both);
            }
        );

        $functions[] = new ExpressionFunction(
            'empty',
            function ($var) {
                return sprintf('empty(%1$v)', $var);
            },

            function ($args, $var) {
                return empty($var);
            }
        );

        $functions[] = new ExpressionFunction(
            'relative_date',
            function ($date, $startDayOfWeek = 'monday') {
                return sprintf('relative_date(%1$d, %2$s)', $date, $startDayOfWeek);
            },
            function ($args, $date, $startDayOfWeek = 'monday') {
                return self::relativeDate($date, $startDayOfWeek);
            }
        );

        $functions[] = new ExpressionFunction(
            'date_tz',
            function ($format, $timestamp = null, $timezone = null) {
                return sprintf('date_tz(%1$s, %2$a, %3$d)', $format, $timestamp, $timezone);
            },
            function ($args, $format, $timestamp = null, $timezone = null) {
                if (!is_numeric($timestamp)) {
                    $timestamp = time();
                }

                $date_tz = \DateTime::createFromFormat($format, date($format, $timestamp));

                if (!is_bool($date_tz) && !empty($timezone)) {
                    $date_tz->setTimezone(new \DateTimeZone($timezone));
                }

                if (is_bool($date_tz)) {
                    return false;
                }

                return $date_tz->format($format);
            }
        );

        $functions[] = new ExpressionFunction(
            'strtotime_tz',
            function ($datetime, $baseTimestamp = null, $timezone = null) {
                return sprintf('strtotime_tz(%1$s, %2$a, %3$d)', $datetime, $baseTimestamp, $timezone);
            },
            function ($args, $datetime, $baseTimestamp = null, $timezone = null) {
                $datetimeStr = $datetime;

                if (!empty($timezone)) {
                    $datetimeStr .= ' ' . $timezone;
                }

                if (is_numeric($baseTimestamp)) {
                    return strtotime($datetimeStr, $baseTimestamp);
                }

                return strtotime($datetimeStr);
            }
        );

        $functions[] = new ExpressionFunction(
            'html_to_markdown',
            function ($html, $options = []) {
                return sprintf('html_to_markdown(%s, %s)', $html, var_export($options, true));
            },
            function ($args, $html, $options = []) {
                $converter = new HtmlConverter($options);
                $markdown = $converter->convert($html);
                return $markdown;
            }
        );

        $functions[] = new ExpressionFunction(
            'parse_date_time',
            function ($date, $platform = 'amazon') {
                return sprintf('parse_date_time(%1$d, %2$p)', $date, $platform);
            },
            function ($args, $date, $platform = 'amazon') {
                if (strtotime($date)) {
                    return strval($date);
                }
                switch ($platform) {
                    case 'amazon':
                        // fix specific date slots provided by Alexa
                        $date = str_replace('-WE', ' +5 days', $date);
                        $date = str_replace('X', '0', $date);
                        $date = str_replace('WI', '12', $date);
                        $date = str_replace('SP', '03', $date);
                        $date = str_replace('SU', '06', $date);
                        $date = str_replace('FA', '09', $date);

                        // fix specific time slots provided by Alexa
                        $date = str_replace('NI', '23:00', $date);
                        $date = str_replace('MO', '05:00', $date);
                        $date = str_replace('AF', '13:00', $date);
                        $date = str_replace('EV', '19:00', $date);

                        // check if the fixed format is still parsable
                        if (strtotime($date)) {
                            return $date;
                        }

                        return false;
                    default:
                        return false;
                }
            }
        );

        $functions[] = new ExpressionFunction(
            'parse_duration',
            function ($duration, $platform = 'amazon', $defaultDuration = 30) {
                return sprintf('parse_duration(%1$d, %2$p, %3$dD)', $duration, $platform, $defaultDuration);
            },
            function ($args, $duration, $platform = 'amazon', $defaultDuration = 30) {
                if (empty($duration)) {
                    return $defaultDuration;
                }
                switch ($platform) {
                    case 'amazon':
                        // $duration in amazon is an ISO 8601 value like PT30S
                        $dateInterval = new \DateInterval($duration);

                        $durationInSeconds =  ($dateInterval->d * 24 * 60 * 60) +
                            ($dateInterval->h * 60 * 60) +
                            ($dateInterval->i * 60) +
                            $dateInterval->s;
                        break;
                    default:
                        $durationInSeconds = 0;
                        break;
                }
                if (!empty($durationInSeconds)) {
                    return $durationInSeconds;
                }
                return $defaultDuration;
            }
        );

        $functions[] = new ExpressionFunction(
            'constant',
            function ($constantName) {
                return sprintf('constant(%s)', $constantName);
            },
            function ($args, $constantName) {
                if (defined($constantName)) {
                    return constant($constantName);
                }
            }
        );

        return $functions;
    }

    public static function relativeDate($date, $startDayOfWeek = 'monday')
    {
        $relativesArray = [
            'relative_available' => false,
            'yesterday' => false,
            'today' => false,
            'tomorrow' => false,
            'last_week' => false,
            'this_week' => false,
            'next_week' => false,
        ];

        $inputTime = is_int($date) ? $date : strtotime($date);
        $startDayOfWeek = strtolower($startDayOfWeek);
        if (!$inputTime) {
            return $relativesArray;
        }

        $currentTime = time();

        $inputTimeFormatted = date("Y-m-d H:i:s", $inputTime);

        $yesterdayFormatted = date("Y-m-d H:i:s", strtotime('yesterday', $currentTime));
        $todayFormatted = date("Y-m-d H:i:s", strtotime('today', $currentTime));
        $tomorrowFormatted = date("Y-m-d H:i:s", strtotime('tomorrow', $currentTime));

        $dayToStartWeek = 'monday';
        if ($startDayOfWeek === 'sunday') {
            $dayToStartWeek = 'sunday';
        }

        $dayToEndWeek = 'sunday';
        if ($startDayOfWeek === 'sunday') {
            $dayToEndWeek = 'saturday';
        }

        $lastWeekStartFormatted = date("Y-m-d H:i:s", strtotime('previous week ' . $dayToStartWeek . ' midnight', $currentTime));
        $lastWeekEndFormatted = date("Y-m-d H:i:s", strtotime('previous week ' . $dayToEndWeek . ' 23:59:59', $currentTime));

        $thisWeekStartFormatted = date("Y-m-d H:i:s", strtotime('last ' . $dayToStartWeek . ' midnight', $currentTime));
        $thisWeekEndFormatted = date("Y-m-d H:i:s", strtotime('this ' . $dayToEndWeek . ' 23:59:59', $currentTime));

        $nextWeekStartFormatted = date("Y-m-d H:i:s", strtotime('last ' . $dayToStartWeek . ' midnight', $currentTime));
        $nextWeekEndFormatted = date("Y-m-d H:i:s", strtotime('this ' . $dayToEndWeek . ' 23:59:59 + 1 week', $currentTime));

        $inputTimeFormattedDateOnly = trim(explode(' ', $inputTimeFormatted)[0]);
        $yesterdayFormattedDateOnly = trim(explode(' ', $yesterdayFormatted)[0]);
        $todayFormattedDateOnly = trim(explode(' ', $todayFormatted)[0]);
        $tomorrowFormattedDateOnly = trim(explode(' ', $tomorrowFormatted)[0]);

        if ($inputTimeFormattedDateOnly === $yesterdayFormattedDateOnly) {
            $relativesArray['relative_available'] = true;
            $relativesArray['yesterday'] = true;
        } else if ($inputTimeFormattedDateOnly === $todayFormattedDateOnly) {
            $relativesArray['relative_available'] = true;
            $relativesArray['today'] = true;
            $relativesArray['this_week'] = true;
        } else if ($inputTimeFormattedDateOnly === $tomorrowFormattedDateOnly) {
            $relativesArray['relative_available'] = true;
            $relativesArray['tomorrow'] = true;
        } else if ($inputTime >= strtotime($lastWeekStartFormatted) && $inputTime <= strtotime($lastWeekEndFormatted)) {
            $relativesArray['relative_available'] = true;
            $relativesArray['last_week'] = true;
        } else if ($inputTime >= strtotime($thisWeekStartFormatted) && $inputTime <= strtotime($thisWeekEndFormatted)) {
            $relativesArray['relative_available'] = true;
            $relativesArray['this_week'] = true;
        } else if ($inputTime >= strtotime($nextWeekStartFormatted) && $inputTime <= strtotime($nextWeekEndFormatted)) {
            $relativesArray['relative_available'] = true;
            $relativesArray['next_week'] = true;
        }

        return $relativesArray;
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Factory\AbstractPackageDefinition::_initDefintions()
     */
    protected function _initDefintions()
    {
        return array(
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\TextResponseElement',
                'Text Response',
                'Present the user with a text response. Use SSML for finer control.',
                array(
                    'type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('default' => 'Default', 'reprompt' => 'Reprompt', 'both' => 'Both'),
                        ),
                        'defaultValue' => 'default',
                        'name' => 'Type',
                        'description' => 'Type of response. "Default" is a standard message. "Reprompt" is what is said after some period of no user input.',
                        'valueType' => 'string'
                    ),
                    'text' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Text',
                        'description' => 'The message you wish to present.',
                        'valueType' => 'string'
                    ),
                    'append' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => false,
                        'name' => 'Append',
                        'description' => 'If true, text will be appended to the preceding sentence (if any) instead of creating a new one.',
                        'valueType' => 'boolean'
                    ),
                    'alexa_domain' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('normal' => 'Normal', 'conversational' => 'Conversational', 'long-form' => 'Long Form', 'music' => 'Music', 'news' => 'News'),
                        ),
                        'defaultValue' => 'normal',
                        'name' => 'Alexa Domain',
                        'description' => 'Change the speech style for Amazon Alexa.',
                        'valueType' => 'string'
                    ),
                    'alexa_emotion' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('neutral' => 'Neutral', 'excited' => 'Excited', 'disappointed' => 'Disappointed'),
                        ),
                        'defaultValue' => 'neutral',
                        'name' => 'Alexa Emotion',
                        'description' => 'Emotion of spoken text by Alexa.',
                        'valueType' => 'string'
                    ),
                    'alexa_emotion_intensity' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('low' => 'Low', 'medium' => 'Medium', 'high' => 'High'),
                        ),
                        'defaultValue' => 'medium',
                        'name' => 'Alexa Emotion Intensity',
                        'description' => 'Emotion intensity of spoken text by Alexa.',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="we-say">' .
                            '<div ng-if="component.properties.type != \'both\'"> {{ component.properties.type == \'default\' ? \'Say:\' : \'Repeat:\' }} <span class="we-say-text">{{component.properties.text}}</span> </div>' .
                            '<div ng-if="component.properties.type == \'both\'"> {{ \'Say and Repeat:\' }} <span class="we-say-text">{{component.properties.text}}</span> </div>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'text-response-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\CommentElement',
                'Editor Comment',
                'A simple element that only serves to leave a comment in the editor',
                array(
                    'comment' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => 'Your comment here',
                        'name' => 'Comment',
                        'description' => 'Comment to show in editor',
                        'valueType' => 'string'
                    ),
                    'context' => [
                        'editor_type' => 'select_context',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Context',
                        'description' => 'Context to select',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="editor-comment">' .
                            '{{ component.properties.comment }}' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'comment-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\LogElement',
                'Editor Log',
                'A simple element that only serves to log',
                array(
                    'log_message' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => 'Your log message here',
                        'name' => 'Log Message',
                        'description' => 'Log of the workflow to show in the log files.',
                        'valueType' => 'string'
                    ),
                    'log_level' => array(
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => [
                                LogLevel::DEBUG     => ucfirst(LogLevel::DEBUG),
                                LogLevel::INFO      => ucfirst(LogLevel::INFO),
                                LogLevel::NOTICE    => ucfirst(LogLevel::NOTICE),
                                LogLevel::WARNING   => ucfirst(LogLevel::WARNING),
                                LogLevel::ERROR     => ucfirst(LogLevel::ERROR),
                                LogLevel::CRITICAL  => ucfirst(LogLevel::CRITICAL),
                                LogLevel::ALERT     => ucfirst(LogLevel::ALERT),
                                LogLevel::EMERGENCY => ucfirst(LogLevel::EMERGENCY)
                            ]
                        ],
                        'defaultValue' => LogLevel::INFO,
                        'name' => 'Log Level',
                        'description' => 'PSR Log level',
                        'valueType' => 'string'
                    ),
                    'disable_test_view' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => false,
                        'name' => 'Disable test view',
                        'description' => 'By default, log entries will be visible in the Test view chat',
                        'valueType' => 'boolean'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="editor-comment">' .
                            '{{ component.properties.log_message }}' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'log-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\GoToElement',
                'Go To',
                'Stops current block execution and continues with read flow of selected block. You can call the same block, but only from process or failback flow.',
                array(
                    'value' => array(
                        'editor_type' => 'select_block',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Block',
                        'description' => 'Block to be executed next',
                        'valueType' => 'string'
                    ),
                    'next' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => false,
                        'name' => 'Wait for next request',
                        'description' => 'If true, the state won\'t be immediately changed, and will wait for the end of execution for the current read phase. The next request will change the state and will skip the read phase of that state. If false, the state will be immediately changed.',
                        'valueType' => 'boolean'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            "<span class=\"statement\">{{ component.properties.value == 'next' ? 'NEXT' : 'GOTO' }}</span> " .
                            "<span ng-if=\"!isBlockLinkable( component.properties.value)\" class=\"block-id\">{{ getBlockName( component.properties.value)}}</span>" .
                            "<a ng-if=\"isBlockLinkable( component.properties.value)\" class=\"block-id linked\" ui-sref=\"convoworks-editor-service.editor({ sb: component.properties.value, sv: 'steps' })\" ui-sref-opts=\"{inherit:true, reload:false, notify:true, location:true}\">{{ getBlockName( component.properties.value)}}</a>" .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'go-to-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ReadElementsFragment',
                'Include Read Fragment',
                'Includes referenced read fragment',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'read_fragment',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Read fragment name',
                        'description' => 'Name of the fragment to be read',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">INCLUDE</span> ' .
                            "<span ng-if=\"!isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id\">{{ getSubroutineName( component.properties.fragment_id)}}</span>" .
                            "<a ng-if=\"isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id linked\"" .
                            " ui-sref=\"convoworks-editor-service.editor({ sb: component.properties.fragment_id, sv: 'fragments' })\" ui-sref-opts=\"{ inherit:true, reload:false, notify:true, location:true }\">{{ getSubroutineName( component.properties.fragment_id)}}</a>" .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'read-elements-fragment.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\RunOnceElement',
                'Run Once',
                'Runs its child elements once per installation or per session',
                [
                    'scope_type' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => ['request' => 'Request', 'session' => 'Session', 'installation' => 'Installation', 'user' => 'User']
                        ],
                        'defaultValue' => 'session',
                        'name' => 'Run once per',
                        'description' => 'Run children once per either session or installation',
                        'valueType' => 'string'
                    ],
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for the component',
                        'valueType' => 'string'
                    ),
                    'child' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'Children',
                        'description' => 'Children to be run once per either installation or session',
                        'valueType' => 'class'
                    ],
                    'else' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'Else',
                        'description' => 'Children to be run if the run once flow has already been triggered',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">Run elements once per <span class="statement">{{ component.properties.scope_type }}</span></div>'
                    ],
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'run-once-element.html'
                    ),
                    '_workflow' => 'read',
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\SetParamElement',
                'Set Parameter',
                'Set up key-value pairs in a given scope',
                array(
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'request' => 'Request', 'user' => 'User'),
                        ),
                        'defaultValue' => 'request',
                        'name' => 'Scope type',
                        'description' => 'Scope under which to store parameters',
                        'valueType' => 'string'
                    ),
                    'parameters' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('parent' => 'Parent Params', 'block' => 'Block Params', 'service' => 'Service Params'),
                        ),
                        'defaultValue' => 'service',
                        'name' => 'Parameters',
                        'description' => 'Store in Block Params or in Service Params',
                        'valueType' => 'string'
                    ),
                    'properties' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Properties',
                        'description' => 'Stored parameters',
                        'valueType' => 'array'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">SET</span> parameters in <span class="statement">{{ component.properties.scope_type.toUpperCase() }}</span> at <span class="statement">{{ component.properties.parameters.toUpperCase() }}</span> level' .
                            '<span ng-if="!component.properties[\'_use_var_properties\']" ng-repeat="(key, val) in component.properties.properties track by key">' .
                            '
<span class="statement">LET</span> <b>{{ key}}</b> = <b>{{ val }};</b>' .
                            '</span>' .
                            '<span ng-if="component.properties[\'_use_var_properties\']">{{ component.properties.properties }}</span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'set-param-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\IfElement',
                'If Element',
                'Test against a condition and execute various flows depending on the result',
                [
                    'test' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Test',
                        'description' => 'An expression to evaluate and decide the flow',
                        'valueType' => 'string'
                    ],
                    'then' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Then',
                        'description' => 'Flow to be executed if test is evaluated as truthy',
                        'valueType' => 'class'
                    ],
                    'else_if' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Pckg\Core\Elements\ElseIfElement'],
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Else If',
                        'description' => 'Set of Else If elements to be checked if "then" fails',
                        'valueType' => 'class'
                    ],
                    'else' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Else',
                        'description' => 'Flow to be executed if test is evaluated as falsy',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">If</span> <b>{{ component.properties.test }}</b>' .
                            '</div>'
                    ],
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'if-element.html'
                    ),
                    '_workflow' => 'read'
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ElseIfElement',
                'Else If',
                'Test against an expression and run children if true',
                [
                    'test' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Test',
                        'description' => 'An expression to be evaluated',
                        'valueType' => 'string'
                    ],
                    'then' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Then',
                        'description' => 'Flow to be run if test is evaluated as a truthy value',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">Else If</span> <b>{{ component.properties.test }}</b>' .
                            '</div>'
                    ],
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'else-if-element.html'
                    ),
                    '_workflow' => 'read',
                    '_descend' => true
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\LoopElement',
                'For-each Loop',
                'Iterates over a collection and runs children for each item.',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'data_collection' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Items',
                        'description' => 'Collection of items over which to iterate',
                        'valueType' => 'string'
                    ),
                    'item' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'item',
                        'name' => 'Data collection item name',
                        'description' => 'Name under which to provide each item of the collection in parameters.',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => [\Convo\Core\Workflow\IConversationElement::class],
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed',
                        'valueType' => 'class'
                    ),
                    'offset' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Offset',
                        'description' => 'Skip this many elements from the beginning of the collection.',
                        'valueType' => 'string'
                    ],
                    'limit' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Limit',
                        'description' => 'Limit execution to this many elements of the collection.',
                        'valueType' => 'string'
                    ],
                    'loop_until' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Loop Until',
                        'description' => 'Expression to loop until.',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">FOR EACH</span> <b>{{ component.properties.data_collection || "data collection" }}</b> <span class="statement">AS</span> <b>{{ component.properties.item || "item" }}</b></div>'
                    ],
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'loop-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ForElement',
                'For Loop',
                'Loops through a block of code a specified number of times.',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'count' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Count',
                        'description' => 'Number of loop iterations',
                        'valueType' => 'string'
                    ),
                    'status_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'status',
                        'name' => 'Status variable',
                        'description' => 'Variable name for accessing loop iteration information, such as the current index',
                        'valueType' => 'string'
                    ),
                    'loop_until' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Loop Until',
                        'description' => 'Expression to loop until.',
                        'valueType' => 'string'
                    ],
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => [\Convo\Core\Workflow\IConversationElement::class],
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed',
                        'valueType' => 'class'
                    ),
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">LOOP</span> <b>{{ component.properties.count || "?" }}</b> <span class="statement">TIMES AS</span> <b>{{ component.properties.item || "item" }}</b></div>'
                    ],
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'for-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\EndSessionElement',
                'End Session',
                'Sends end session signal to device',
                array(
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'end-session-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\EndRequestElement',
                'End Request',
                'Stops current service execution',
                array(
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'end-request-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\NamedFunctionElement',
                'Function Element',
                'Defines a workflow that can be invoked as a function in expression language',
                [
                    'name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Function name',
                        'description' => 'Valid function name you will use to invoke it.',
                        'valueType' => 'string'
                    ],
                    'function_args' => [
                        'editor_type' => 'params',
                        'editor_properties' => ['multiple' => true],
                        'defaultValue' => [],
                        'name' => 'Function arguments',
                        'description' => 'Name and default value pairs of function arguments.',
                        'valueType' => 'array'
                    ],
                    'result_data' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '${function_result}',
                        'name' => 'Result variable name',
                        'description' => 'Variable storing the function result.',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => ['allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'], 'multiple' => true],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Executed once the operation is completed and the result variable is ready for use.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code"><span class="statement">FUNCTION</span> ' .
                            '<b>{{component.properties.name}}(' .
                            '<span ng-if="!isString(component.properties.function_args)" ng-repeat="(key, val) in component.properties.function_args track by key">' .
                            '{{$index ? ", " : ""}}{{ key }}</span>' .
                            '<span ng-if="isString(component.properties.function_args)">{{ component.properties.function_args }}</span>' .
                            ') => {{component.properties.result_data}}</b></div>'
                    ],
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'named-function-element.html'
                    ],
                ]
            ),

            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Processors\SimpleProcessor',
                'Simple Processor',
                'Process elements if child filters are activated',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'ok' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK flow',
                        'description' => 'Flow to be executed if filters are matched',
                        'valueType' => 'class',
                    ),
                    'request_filters' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IRequestFilter'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Request filters',
                        'description' => 'Filters to be applied against request',
                        'valueType' => 'class'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'simple-processor.html'
                    ),
                    '_workflow' => 'process',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Filters\IntentRequestFilter',
                'Intent Filter',
                'Intent capable platform request filter',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for the component',
                        'valueType' => 'string'
                    ),
                    'readers' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Intent\IIntentAdapter'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Intent readers',
                        'description' => 'Filters by skill definition and intents in it',
                        'valueType' => 'class'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'intent-request-filter.html'
                    ),
                    '_workflow' => 'filter',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Filters\PlatformIntentReader',
                'Platform Intent',
                'Reads platform intents. Use for matching specific platform intents.',
                array(
                    'intent' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Intent',
                        'description' => 'Name of the intent which activates this filter',
                        'valueType' => 'string'
                    ),
                    'disable' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Disable',
                        'description' => 'Optional expression to evaluate which wont trigger the intent even if it matches.',
                        'valueType' => 'string'
                    ),
                    'values' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Predefined values',
                        'description' => 'Predefined values which should be set in result',
                        'valueType' => 'array'
                    ),
                    'rename' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Rename values',
                        'description' => 'Use incoming values under a different name',
                        'valueType' => 'array'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">Catch platform intent <b>{{ component.properties.intent}}</b>' .
                            '<span ng-if="!component.properties[\'_use_var_values\']"><span ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span></span>' .
                            '<span ng-if="component.properties[\'_use_var_values\']">Use predefined value expression <b>{{ component.properties.values }}</b></span>' .
                            '<span ng-if="!component.properties[\'_use_var_rename\']"><span ng-repeat="(key,val) in component.properties.rename track by key">, rename slot <b>{{ key }} => result.{{ val }}</b></span></span>' .
                            '<span ng-if="!component.properties[\'_use_var_rename\']">Use rename expression <b>{{ component.properties.rename }}</b></span>' .
                            '<span ng-if="component.properties[\'disable\']"><br>Disable when <b>{{ component.properties.disable }}</b></span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'platform-intent-reader.html'
                    ),
                    '_workflow' => 'filter',
                    '_descend' => true,
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Filters\ConvoIntentReader',
                'Convo Intent',
                'Matches against Convo intent definitions',
                array(
                    'intent' => array(
                        'editor_type' => 'convo_intent',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Intent',
                        'description' => 'Name of the intent which activates this filter',
                        'valueType' => 'string'
                    ),
                    'disable' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Disable',
                        'description' => 'Optional expression to evaluate which wont trigger the intent even if it matches.',
                        'valueType' => 'string'
                    ),
                    'required_slots' => array(
                        'editor_type' => 'required_slots',
                        'editor_properties' => array(),
                        'defaultValue' => [],
                        'name' => 'Required slots',
                        'description' => 'List of slots, their types, and whether or not any of them are absolutely required for the reader to trigger.',
                        'valueType' => 'array'
                    ),
                    'values' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Predefined values',
                        'description' => 'Predefined values which should be set in result',
                        'valueType' => 'array'
                    ),
                    'rename' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Rename values',
                        'description' => 'Use values but with different name',
                        'valueType' => 'array'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">Catch Convoworks intent ' .
                            '<a ng-if="component.properties.intent && !isSystemIntent(component.properties.intent)" class="block-id linked" ui-sref="convoworks-editor-service.intent-details({ name: component.properties.intent })", ui-sref-opts="{ inherit: true, reload: false, notify: true, location: true }">{{ component.properties.intent}}</a>' .
                            '<b ng-if="component.properties.intent && isSystemIntent(component.properties.intent)">{{ component.properties.intent}}</b>' .
                            '<span ng-if="!component.properties[\'_use_var_values\']"><span ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span></span>' .
                            '<span ng-if="component.properties[\'_use_var_values\']"><br>Use predefined value expression <b>{{ component.properties.values }}</b></span>' .
                            '<span ng-if="!component.properties[\'_use_var_rename\']"><span ng-repeat="(key,val) in component.properties.rename track by key">, rename slot <b>{{ key }} => result.{{ val }}</b></span></span>' .
                            '<span ng-if="component.properties[\'_use_var_rename\']"><br>Use rename expression <b>{{ component.properties.rename }}</b></span>' .
                            '<span ng-if="component.properties[\'disable\']"><br>Disable when <b>{{ component.properties.disable }}</b></span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'convo-intent-reader.html'
                    ),
                    '_workflow' => 'filter',
                    '_descend' => true,
                    '_factory' => new class($this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct($packageProviderFactory)
                        {
                            $this->_packageProviderFactory    = $packageProviderFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Core\Filters\ConvoIntentReader($properties, $this->_packageProviderFactory);
                        }
                    }
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Processors\YesNoProcessor',
                'Yes/No Processor',
                'A simple yes/no junction processor',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'yes' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Yes flow',
                        'description' => 'Flow to be executed if the processor matches an affirmative value.',
                        'valueType' => 'class'
                    ),
                    'no' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'No flow',
                        'description' => 'Flow to be executed if the processor matches a negative value.',
                        'valueType' => 'class'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="user-say">' .
                            'User says: <b>"yes"</b>, <b>"sure"</b>, <b>"cool"</b>, <b>"no"</b>, <b>"nope"</b>,   ...' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'yes-no-processor.html'
                    ),
                    '_workflow' => 'process',
                    '_factory' => new class($this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct($packageProviderFactory)
                        {
                            $this->_packageProviderFactory    =    $packageProviderFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Core\Processors\YesNoProcessor($properties, $this->_packageProviderFactory, $service);
                        }
                    }
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ElementCollection',
                'Element collection',
                'Collection of conversation elements. It will execute sequentially all child elements',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement', '\Convo\Core\Workflow\IElementGenerator'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed',
                        'valueType' => 'class'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'element-collection.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ElementRandomizer',
                'Element Randomizer',
                'Picks just one child element end executes it',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'mode' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('wild' => 'Wild', 'smart'  => 'Smart'),
                        ),
                        'defaultValue' => 'wild',
                        'name' => 'Element storage mode',
                        'description' => '"Wild" executes elements completely at random. "Smart" will keep track of what\'s been read, and will avoid repetition untill all elements have been used up.',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement', '\Convo\Core\Workflow\IElementGenerator'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed at random',
                        'valueType' => 'class'
                    ),
                    'loop' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(
                            'dependency' => "component.properties.mode === 'smart'"
                        ),
                        'defaultValue' => true,
                        'name' => 'Loop',
                        'description' => 'Should use loop?',
                        'valueType' => 'boolean'
                    ),
                    'is_repeat' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'dependency' => "component.properties.mode === 'smart'"
                        ),
                        'defaultValue' => '',
                        'name' => 'Is Repeat',
                        'description' => 'Expression to evaluate if you want the Element Randomizer Element to repeat the same element.',
                        'valueType' => 'string'
                    ),
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'user' => 'User'),
                            'dependency' => "component.properties.mode === 'smart'"
                        ),
                        'defaultValue' => 'installation',
                        'name' => 'Scope type',
                        'description' => 'Dictates how long the smart mode pool will live. "Installation" is per-device, while "Session" lasts for only one given session.',
                        'valueType' => 'string'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'element-randomizer.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\GeneratorElement',
                'Element Generator',
                '',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for component',
                        'valueType' => 'string'
                    ),
                    'data_collection' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Items',
                        'description' => 'Collection of items over which to iterate',
                        'valueType' => 'string'
                    ),
                    'item' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'item',
                        'name' => 'Data collection item name',
                        'description' => 'Name under which to provide each item of the collection in parameters.',
                        'valueType' => 'string'
                    ),
                    'element' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'defaultOpen' => true,
                        'name' => 'Element',
                        'description' => 'Element to be generated',
                        'valueType' => 'class'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'element-randomizer.html'
                    ),
                    '_workflow' => 'read',
                    '_descend' => true
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ElementQueue',
                'Element Queue',
                'Execute elements in sequence, with an optional flow to read if all elements have been executed',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => null,
                        'name' => 'Name',
                        'description' => 'Optional name for the component',
                        'valueType' => 'string'
                    ),
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'user' => 'User')
                        ),
                        'defaultValue' => 'session',
                        'name' => 'Scope type',
                        'description' => 'Sets when to run elements in sequence.',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement', '\Convo\Core\Workflow\IElementGenerator'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed in order',
                        'valueType' => 'class'
                    ),
                    'done' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Done',
                        'description' => 'Elements to be executed if main flow has been executed already.',
                        'valueType' => 'class'
                    ),
                    'should_reset' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Should Reset',
                        'description' => 'If this expression evaluates to true, the queue will reset and start from the beginning.',
                        'valueType' => 'string'
                    ),
                    'wraparound' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Wraparound',
                        'description' => 'Whether to read the "Done" flow once elements have been read in sequence, or to start over. You can also toggle to raw to add an expression that evaluates to a boolean.',
                        'valueType' => 'boolean'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'element-queue.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\CardElement',
                'x!Card',
                'Display the properties of an object in an visual layout. (Works with devices that have the screen output capability.)',
                array(
                    'data_item' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Item',
                        'description' => 'Single item (object) from which you want to display properties to an visual card element.',
                        'valueType' => 'string'
                    ),
                    'back_button' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('VISIBLE' => 'VISIBLE', 'HIDDEN'  => 'HIDDEN'),
                        ),
                        'defaultValue' => 'VISIBLE',
                        'name' => 'Back Button',
                        'description' => 'Choose between hidden or visible back button. (works only on Alexa)',
                        'valueType' => 'string'
                    ),
                    'data_item_title' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item title',
                        'description' => 'Title of the item which will be displayed on the card.',
                        'valueType' => 'string'
                    ),
                    'data_item_subtitle' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item subtitle',
                        'description' => 'Subtitle of the item which will be displayed on the card.',
                        'valueType' => 'string'
                    ),
                    'data_item_description_1' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item description 1',
                        'description' => 'Primary description of the item which will be displayed on the card.',
                        'valueType' => 'string'
                    ),
                    'data_item_description_2' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item description 2',
                        'description' => 'Secondary description of the item which will be displayed on the card. (works with Alexa only)',
                        'valueType' => 'string'
                    ),
                    'data_item_description_3' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item description 3',
                        'description' => 'Tertiary description of the item which will be displayed on the card. (works with Alexa only)',
                        'valueType' => 'string'
                    ),
                    'data_item_image_url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item image url',
                        'description' => 'Link to the image of an item in the card.',
                        'valueType' => 'string'
                    ),
                    'data_item_image_text' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Data item image text',
                        'description' => 'Accessibility text of the image of an item in the card. (Required if you want to display the image.)',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<ul class="list-unstyled">' .
                            ' <li>Data item: {{component.properties.data_item}}</li>' .
                            ' <li>Back button: {{component.properties.back_button}}</li>' .
                            ' <li>Data item title: {{component.properties.data_item_title}}</li>' .
                            ' <li>Data item subtitle: {{component.properties.data_item_subtitle}}</li>' .
                            ' <li>Data item description 1: {{component.properties.data_item_description_1}}</li>' .
                            ' <li>Data item description 2: {{component.properties.data_item_description_2}}</li>' .
                            ' <li>Data item description 3: {{component.properties.data_item_description_3}}</li>' .
                            ' <li>Data item image URL: {{component.properties.data_item_image_url}}</li>' .
                            ' <li>Data item image text: {{component.properties.data_item_image_text}}</li>' .
                            '</ul>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'card-element.html'
                    ),
                    '_workflow' => 'read',
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('ALEXA_PRESENTATION_APL')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ListElement',
                'x!List',
                'Iterates over a collection and renders a visual representation for each item in the list. (Works with devices that have the screen output capability.)',
                array(
                    'list_title' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List title',
                        'description' => 'Title of the content that is in the list.',
                        'valueType' => 'string'
                    ),
                    'list_template' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('LIST' => 'List', 'CAROUSEL'  => 'Carousel'),
                        ),
                        'defaultValue' => 'LIST',
                        'name' => 'List template',
                        'description' => 'Choose between Vertical or Horizontal list layout.',
                        'valueType' => 'string'
                    ),
                    'data_collection' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Items',
                        'description' => 'Collection of items which will be displayed in the list as a visual representation of each list item.',
                        'valueType' => 'string'
                    ),
                    'offset' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Offset',
                        'description' => 'Display this many items from the beginning of the collection.',
                        'valueType' => 'string'
                    ],
                    'limit' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Limit',
                        'description' => 'Display to this many items of the collection.',
                        'valueType' => 'string'
                    ],
                    'list_item_title' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List item title',
                        'description' => 'Title of the item which is in the list.',
                        'valueType' => 'string'
                    ),
                    'list_item_description_1' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List item description 1',
                        'description' => 'Description of the item which is in the list. (works with Google Assistant and Alexa)',
                        'valueType' => 'string'
                    ),
                    'list_item_description_2' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List item description 2',
                        'description' => 'Secondary description of the item which is in the list. (works with Alexa only)',
                        'valueType' => 'string'
                    ),
                    'list_item_image_url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List item image url',
                        'description' => 'Link to the image of an item in the list.',
                        'valueType' => 'string'
                    ),
                    'list_item_image_text' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'List item image text',
                        'description' => 'Accessibility text of the image of an item in the list. (Required if you want to display the image.)',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<ul class="list-unstyled">' .
                            ' <li>List name: {{component.properties.list_title}}</li>' .
                            ' <li>List template: {{component.properties.list_template}}</li>' .
                            ' <li>List items: {{component.properties.data_collection}}</li>' .
                            ' <li>List item title: {{component.properties.list_item_title}}</li>' .
                            ' <li>List item description 1: {{component.properties.list_item_description_1}}</li>' .
                            ' <li>List item description 2: {{component.properties.list_item_description_2}}</li>' .
                            ' <li>List item image URL: {{component.properties.list_item_image_url}}</li>' .
                            ' <li>List item image text: {{component.properties.list_item_image_text}}</li>' .
                            '</ul>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'list-element.html'
                    ),
                    '_workflow' => 'read',
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('ALEXA_PRESENTATION_APL')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\HttpQueryElement',
                'HTTP Query',
                'Perform an HTTP request to a specified endpoint',
                array(
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'request' => 'Request', 'user' => 'User'),
                        ),
                        'defaultValue' => 'session',
                        'name' => 'Scope type',
                        'description' => 'Scope under which to store parameters',
                        'valueType' => 'string'
                    ),
                    'parameters' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('block' => 'Block Params', 'service' => 'Service Params'),
                        ),
                        'defaultValue' => 'block',
                        'name' => 'Parameters',
                        'description' => 'Store in Block Params or in Service Params',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'response',
                        'name' => 'Result name',
                        'description' => 'Name under which to save the result in parameters',
                        'valueType' => 'string'
                    ),
                    'url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Endpoint URL',
                        'description' => 'URL to send request',
                        'valueType' => 'string'
                    ),
                    'content_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'multiple' => false,
                            'options' => array('AUTO' => 'Auto', 'JSON' => 'JSON', 'TEXT' => 'Plain Text')
                        ),
                        'defaultValue' => 'AUTO',
                        'name' => 'Content Type',
                        'description' => 'Http content type to check for in headers',
                        'valueType' => 'string'
                    ),
                    'method' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'multiple' => false,
                            'options' => array('GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT')
                        ),
                        'defaultValue' => 'GET',
                        'name' => 'HTTP method',
                        'description' => 'Method by which to perform the request',
                        'valueType' => 'string'
                    ),
                    'cache_timeout' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'dependency' => 'component.properties.method === "GET"'
                        ),
                        'defaultValue' => 0,
                        'name' => 'Cache timeout',
                        'description' => 'Cache GET requests. Expiration value is in seconds',
                        'valueType' => 'int'
                    ),
                    'timeout' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 3,
                        'name' => 'Timeout',
                        'description' => 'Maximum timeout in seconds',
                        'valueType' => 'int'
                    ),
                    'headers' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => 'true'
                        ),
                        'defaultValue' => array(),
                        'name' => 'Headers',
                        'description' => 'HTTP headers to send with the request',
                        'valueType' => 'array'
                    ),
                    'params' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => 'true'
                        ),
                        'defaultValue' => array(),
                        'name' => 'Parameters',
                        'description' => 'Parameters to send with the request.',
                        'valueType' => 'array'
                    ),
                    'body' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(
                            'dependency' => 'component.properties.method === "POST" || component.properties.method === "PUT"'
                        ),
                        'defaultValue' => null,
                        'name' => 'Request body',
                        'description' => 'JSON body to send with POST or PUT request',
                        'valueType' => 'string'
                    ),
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'OK',
                        'description' => 'Flow to be executed if an HTTP request was successful',
                        'valueType' => 'class'
                    ],
                    'nok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'NOK',
                        'description' => 'Flow to be executed if an HTTP request was unsuccessful',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">{{ component.properties.method }}</span> {{ component.properties.method === \'GET\' ? \'from\' : \'to\' }} <b>{{ component.properties.url }}</b>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'http-query-element.html'
                    ),
                    '_workflow' => 'read',
                    '_factory' => new class($this->_httpFactory, $this->_cache) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_httpFactory;
                        private $_cache;
                        public function __construct($httpFactory, $cache)
                        {
                            $this->_httpFactory    =    $httpFactory;
                            $this->_cache        =    $cache;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Core\Elements\HttpQueryElement($properties, $this->_httpFactory, $this->_cache);
                        }
                    },
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\MysqliQueryElement',
                'x!MySQLI query',
                'Perform an SQL query via a connection',
                array(
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Result name',
                        'description' => 'Name under which to save the result in parameters',
                        'valueType' => 'string'
                    ),
                    'conn' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Connection',
                        'description' => 'Connection to use for executing queries',
                        'valueType' => 'string'
                    ),
                    'query' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Query',
                        'description' => 'SQL query to execute',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">PERFORM</span> {{ component.properties.query }}' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'mysqli-query-element.html'
                    ),
                    '_workflow' => 'read'
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\JsonReader',
                'x!JSON Reader',
                'URL',
                array(
                    'url' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'URL',
                        'valueType' => 'string'
                    ),
                    'var' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'VAR',
                        'valueType' => 'string'
                    ),
                    'decode' => array(
                        'editor_type' => 'boolean',
                        'editor_properties' => array(),
                        'defaultValue' => false,
                        'name' => 'Decode',
                        'description' => 'Decode special html characters',
                        'valueType' => 'boolean'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="we-say"><b>Reading: {{component.properties.url}}</b></div>'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Init\MysqlConnectionComponent',
                'x!MySQL connection context',
                'Setup connection params for MySQL',
                array(
                    'id' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Context ID',
                        'description' => 'Unique ID by which this context is referenced',
                        'valueType' => 'string'
                    ),
                    'host' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Host',
                        'description' => 'Host to connect to',
                        'valueType' => 'string'
                    ),
                    'port' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Port',
                        'description' => 'Port to which to connect to on the host',
                        'valueType' => 'string'
                    ),
                    'user' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Username',
                        'description' => 'Username to authenticate with',
                        'valueType' => 'string'
                    ),
                    'pass' => array(
                        'editor_type' => 'password',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Password',
                        'description' => 'Password to use when connecting',
                        'valueType' => 'string'
                    ),
                    'dbName' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Database name',
                        'description' => 'Name of database to use',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">CONNECT TO</span> <b>{{ contextElement.properties.host }}{{ contextElement.properties.port ? \':\'+contextElement.properties.port : \'\' }}</b> <span class="statement">AS</span> {{ contextElement.properties.user }}' .
                            '<br/>' .
                            '<span class="statement">USE DB</span> <b>{{ contextElement.properties.dbName }}</b>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'mysql-connection-component.html'
                    ),
                    '_workflow' => 'datasource'
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ConversationBlock',
                'Conversation Block',
                'A step in the conversation flow. It has initial read phase serves for informing user about thing he can do.
Process phase tries to execute user command, if matched. If no match is found the default phase is executed.
In default phase you can inform users about problem you have interpreting command.',
                array(
                    'role' => array(
                        'defaultValue' => IRunnableBlock::ROLE_CONVERSATION_BLOCK
                    ),
                    'block_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'new-block-id',
                        'name' => 'Block ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New block',
                        'name' => 'Block name',
                        'description' => 'A user friendly name for the block',
                        'valueType' => 'string'
                    ),
                    'pre_dispatch' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'roles' => [
                                IRunnableBlock::ROLE_SESSION_START
                            ]
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Pre-dispatch flow',
                        'description' => 'Elements to run before each read and process phase. They will not be re-run if the block is read again.',
                        'valueType' => 'class',
                        '_separate' => true
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'roles' => [
                                IRunnableBlock::ROLE_CONVERSATION_BLOCK,
                                IRunnableBlock::ROLE_SESSION_START,
                                IRunnableBlock::ROLE_SESSION_ENDED,
                                IRunnableBlock::ROLE_DEFAULT_FALLBACK,
                                IRunnableBlock::ROLE_ERROR_HANDLER
                            ]
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Read phase',
                        'description' => 'Elements to be executed in read phase',
                        'valueType' => 'class',
                        '_separate' => true
                    ),
                    'processors' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationProcessor'),
                            'multiple' => true,
                            'roles' => [IRunnableBlock::ROLE_CONVERSATION_BLOCK, IRunnableBlock::ROLE_SESSION_START, IRunnableBlock::ROLE_SERVICE_PROCESSORS]
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Process phase',
                        'description' => 'Processors to be executed in process phase',
                        'valueType' => 'class'
                    ),
                    'fallback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'roles' => [IRunnableBlock::ROLE_CONVERSATION_BLOCK, IRunnableBlock::ROLE_SESSION_START]
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Fallback',
                        'description' => 'Elements to be read if none of the processors match',
                        'valueType' => 'class'
                    ),
                    '_workflow' => 'read',
                    '_system' => true,
                    '_help' => [
                        'type' => 'file'
                    ]
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\DefaultSpecialRoleBlock',
                'Special Role Block',
                'This block will be activate for special role requests.',
                array(
                    'block_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'new-block-id',
                        'name' => 'Block ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New block',
                        'name' => 'Block name',
                        'description' => 'A user friendly name for the block',
                        'valueType' => 'string'
                    ),
                    'role' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Role',
                        'description' => 'A role to be activated on',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Read phase',
                        'description' => 'Elements to be executed in read phase',
                        'valueType' => 'class',
                        '_separate' => true
                    ),
                    '_workflow' => 'read',
                    '_system' => true,
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\SpecialRoleProcessorBlock',
                'Special Role Processor Block',
                'This block will be activate for special role requests.',
                array(
                    'block_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'new-block-id',
                        'name' => 'Block ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New block',
                        'name' => 'Block name',
                        'description' => 'A user friendly name for the block',
                        'valueType' => 'string'
                    ),
                    'role' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Role',
                        'description' => 'A role to be activated on',
                        'valueType' => 'string'
                    ),
                    'processors' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationProcessor'),
                            'multiple' => true,
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Process phase',
                        'description' => 'Processors to be executed in process phase',
                        'valueType' => 'class'
                    ),
                    'failback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                        ),
                        'defaultValue' => array(),
                        'name' => 'Failback phase',
                        'description' => 'Elements to be executed if none of the processors was activated',
                        'valueType' => 'class'
                    ),
                    '_workflow' => 'read',
                    '_system' => true,
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\MediaBlock',
                'Media Block',
                'A special role "media_player" block, that handles audio player requests (not in standard service session).',
                array(
                    'role' => array(
                        'defaultValue' => IRunnableBlock::ROLE_MEDIA_PLAYER
                    ),
                    'block_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'new-block-id',
                        'name' => 'Block ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'context_id' => array(
                        'editor_type' => 'context_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'Source Media Context ID',
                        'valueType' => 'string'
                    ),
                    'media_info_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'media_info',
                        'name' => 'Media info',
                        'description' => 'Variable name for the media info array',
                        'valueType' => 'string'
                    ),
                    'last_media_info_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'last_media_info',
                        'name' => 'Last Media info',
                        'description' => 'Variable name for the last successful media info array since the playback has started.',
                        'valueType' => 'string'
                    ),
                    'no_next' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Next not avilable',
                        'description' => 'Elements to be read if next song is requested but not available',
                        'valueType' => 'class'
                    ),
                    'no_previous' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Previous not avilable',
                        'description' => 'Elements to be read if previous song is requested but not available',
                        'valueType' => 'class'
                    ),
                    'fallback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Fallback',
                        'description' => 'Elements to be read if none of the processors match',
                        'valueType' => 'class'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'media-block.html'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_system' => true,
                    '_factory' => new class($this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct(\Convo\Core\Factory\PackageProviderFactory $packageProviderFactory)
                        {
                            $this->_packageProviderFactory    =    $packageProviderFactory;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Core\Elements\MediaBlock($properties, $service, $this->_packageProviderFactory);
                        }
                    },
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('AUDIO_PLAYER')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\MediaInfoElement',
                'Media Info',
                'Provides info about current songs in the connected Media Context component',
                array(
                    'context_id' => array(
                        'editor_type' => 'context_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'A media source context id',
                        'valueType' => 'string'
                    ),
                    'media_info_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'media_info',
                        'name' => 'Media info',
                        'description' => 'Variable name for the media info array',
                        'valueType' => 'string'
                    ),
                    'has_results' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'Has results',
                        'description' => 'Executed if there are results',
                        'valueType' => 'class'
                    ],
                    'no_results' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'No results',
                        'description' => 'Executed if there are no results',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">LET</span> <b>{{ component.properties.media_info_var }}</b> = ' .
                            'media info <span class="statement">FROM</span> <b>{{ component.properties.context_id }}</b>' .
                            '</div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'media-info-element.html'
                    ),
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\StartAudioPlayback',
                'Start Audio Playback',
                'Initiates audio playback and automatically stops the current session.',
                array(
                    'context_id' => array(
                        'editor_type' => 'context_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'A media source context id',
                        'valueType' => 'string'
                    ),
                    'play_index' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Song to play',
                        'description' => 'Expression which evaluates to integer index of the desired song to play',
                        'valueType' => 'string'
                    ),
                    'media_info_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'media_info',
                        'name' => 'Media info',
                        'description' => 'Variable name for the media info array',
                        'valueType' => 'string'
                    ),
                    'failback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Failback phase',
                        'description' => 'Elements to be executed if element fails to play desired song',
                        'valueType' => 'class'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">START PLAYBACK</span> on <b>{{component.properties.context_id}}</b>' .
                            '<span class="statement">{{ component.properties.play_index ? \' FROM \' : \'\'}}</span>' .
                            '<b> {{ component.properties.play_index ? component.properties.play_index : \'\'}}</b>' .
                            '</div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'start-audio-playback.html'
                    ),
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('AUDIO_PLAYER')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\SeekAudioPlaybackBySearch',
                'Seek Audio Playback By Search',
                'Initiates audio playback by search in the current playlist and automatically stops the current session.',
                array(
                    'context_id' => array(
                        'editor_type' => 'context_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'A media source context id',
                        'valueType' => 'string'
                    ),
                    'search_term' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Search Term',
                        'description' => 'Expression which evaluates to string of the desired song title or artist to seek to',
                        'valueType' => 'string'
                    ),
                    'media_info_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'media_info',
                        'name' => 'Media info',
                        'description' => 'Variable name for the media info array',
                        'valueType' => 'string'
                    ),
                    'fallback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Fallback phase',
                        'description' => 'Elements to be executed if element fails to play desired song',
                        'valueType' => 'class'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">SEEK PLAYBACK </span> on <b>{{component.properties.context_id}}</b>' .
                            '<span class="statement">{{ component.properties.search_term ? \' SEARCH TERM \' : \'\'}}</span>' .
                            '<b> {{ component.properties.search_term ? component.properties.search_term : \'\'}}</b>' .
                            '</div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'seek-audio-playback-by-search.html'
                    ),
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('AUDIO_PLAYER')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\FastForwardRewindAudioPlayback',
                'Fast Forward Rewind Audio Playback',
                'Fast Forwards or Rewinds the currently initiated audio playback and automatically stops the current session.',
                array(
                    'context_id' => array(
                        'editor_type' => 'context_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'A media source context id',
                        'valueType' => 'string'
                    ),
                    'mode' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('rewind' => 'Rewind', 'fast_forward' => 'Fast Forward'),
                        ),
                        'defaultValue' => 'rewind',
                        'name' => 'Mode',
                        'description' => '',
                        'valueType' => 'string'
                    ),
                    'rewind_fast_forward_value' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '30',
                        'name' => 'Seconds to Rewind or Fast Forward Playback',
                        'description' => 'Expression which evaluates to integer seconds of the desired seconds of the song to skip.',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">{{ component.properties.mode === \'rewind\' ? \'REWIND\' :  \'FAST FORWARD\' }} </span>' .
                            '<b>{{ component.properties.rewind_fast_forward_value }}</b> seconds' .
                            '</div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'fast-forward-rewind-audio-playback.html'
                    ),
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('AUDIO_PLAYER')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\StartVideoPlayback',
                'Start Video Playback',
                'Initiates video playback and automatically stops the current session.',
                array(
                    'url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Video URL',
                        'description' => 'A URL to video.',
                        'valueType' => 'string'
                    ),
                    'title' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Video Title',
                        'description' => 'Video Title.',
                        'valueType' => 'string'
                    ),
                    'subtitle' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Video Subtitle',
                        'description' => 'Video Subtitle.',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><span class="statement">START VIDEO PLAYBACK</span> from <b>{{component.properties.url}}</b>' .
                            '</div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'start-video-playback.html'
                    ),
                    '_platform_defaults' => array(
                        'amazon' => array(
                            'interfaces' => array('VIDEO_APP')
                        )
                    )
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\LoopBlock',
                'Loop Block',
                'Special conversation block type that will iterate over given array by itself.',
                array(
                    'role' => array(
                        'defaultValue' => IRunnableBlock::ROLE_CONVERSATION_BLOCK
                    ),
                    'block_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'new-block-id',
                        'name' => 'Block ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New block',
                        'name' => 'Block name',
                        'description' => 'A user friendly name for the block',
                        'valueType' => 'string'
                    ),
                    'data_collection' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Items',
                        'description' => 'Collection of items over which to iterate',
                        'valueType' => 'string'
                    ),
                    'item' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'item',
                        'name' => 'Data collection item name',
                        'description' => 'Name under which to provide each item of the collection in parameters.',
                        'valueType' => 'string'
                    ),
                    'offset' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Offset',
                        'description' => 'Skip this many elements from the beginning of the collection.',
                        'valueType' => 'string'
                    ],
                    'limit' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Limit',
                        'description' => 'Limit execution to this many elements of the collection.',
                        'valueType' => 'string'
                    ],
                    'skip_reset' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Skip reset',
                        'description' => 'Remember block param values when outside of trivia block. Enter a value that evaluates to true or false.',
                        'valueType' => 'string'
                    ),
                    'reset_loop' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Reset loop',
                        'description' => 'Resets the loop and starts over. Enter a value that evaluates to true or false.',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Read phase',
                        'description' => 'Elements to be executed in read phase',
                        'valueType' => 'class',
                        '_separate' => true
                    ),
                    'main_processors' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationProcessor'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Main processors',
                        'description' => 'Main processors to be executed in process phase. After main procesor is triggered, loop advances to next item',
                        'valueType' => 'class'
                    ),
                    'processors' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationProcessor'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Process phase',
                        'description' => 'Other processors to be executed in process phase. E.g. help, repeat ... This procoessors will not trigger loop iteration.',
                        'valueType' => 'class',
                        '_separate' => true
                    ),
                    'fallback' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Fallback',
                        'description' => 'Elements to be read if none of the processors match',
                        'valueType' => 'class'
                    ),
                    'done' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Done',
                        'description' => 'Elements to be read after loop is done. Use it for cleanup and moving the conversation focus to some other block.',
                        'valueType' => 'class'
                    ),
                    '_help' => [
                        'type' => 'file'
                    ],
                    '_workflow' => 'read',
                    '_system' => true
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Filters\NopRequestFilter',
                'No-Op filter',
                'No operation - does nothing',
                array(
                    'empty' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('empty' => 'empty', 'match' => 'match'),
                        ),
                        'defaultValue' => 'empty',
                        'name' => 'Is empty',
                        'description' => 'Use this filter to test workflows',
                        'valueType' => 'string'
                    ),
                    'values' => array(
                        'editor_type' => 'params',
                        'editor_properties' => array(
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Predefined values',
                        'description' => 'Predefined values which should be set in result',
                        'valueType' => 'array'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><b>{{ component.properties.empty === \'empty\' ? \'Will not activate\' :  \'Always activated\' }}</b>' .
                            '<span ng-if="component.properties.empty != \'empty\' && !component.properties[\'_use_var_values\']" ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span>' .
                            '<span ng-if="component.properties.empty != \'empty\' && component.properties[\'_use_var_values\']"><br>Use predefined values expression <b>{{ component.properties.values }}</b></span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'nop-request-filter.html'
                    ),
                    '_workflow' => 'filter',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ElementsFragment',
                'Elements Fragment',
                'Read workflow fragment',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'read-fragment',
                        'name' => 'Fragment ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New elements fragment',
                        'name' => 'Fragment name',
                        'description' => 'Name for easier fragment reference',
                        'valueType' => 'string'
                    ),
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'defaultOpen' => true,
                        'name' => 'Elements',
                        'description' => 'Elements to be executed',
                        'valueType' => 'class'
                    ),
                    '_workflow' => 'read',
                    '_system' => true
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Processors\ProcessorFragment',
                'Processors Fragment',
                'Fragment which contains processors',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'process-fragment',
                        'name' => 'Fragment ID',
                        'description' => 'Unique string identificator',
                        'valueType' => 'string'
                    ),
                    'name' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'New processor fragment',
                        'name' => 'Fragment name',
                        'description' => 'Name for easier fragment reference',
                        'valueType' => 'string'
                    ),
                    'processors' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationProcessor'),
                            'multiple' => true
                        ),
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Processors',
                        'description' => 'One or more processors to be taken in count',
                        'valueType' => 'class'
                    ),
                    '_workflow' => 'process',
                    '_system' => true
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\AudioPlayer',
                'x!Audio Player',
                'URL',
                array(
                    'url' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'URL',
                        'valueType' => 'string'
                    ),
                    'mode' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('play' => 'Play', 'stop' => 'Stop', 'enqueue' => 'Enqueue', 'other' => 'Other', 'clearEnqueue' => 'ClearEnqueue'),
                        ),
                        'defaultValue' => 'play',
                        'name' => 'Mode',
                        'description' => '',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="we-say"><b>Playing: {{component.properties.url}}</b></div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'audio-player.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Processors\ProcessProcessorFragment',
                'Include Processor Fragment',
                'Include a processor fragment to reuse behavior',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'process_fragment',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Process fragment name',
                        'description' => 'Name of the fragment to be processed',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">INCLUDE</span> ' .
                            "<span ng-if=\"!isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id\">{{ getSubroutineName( component.properties.fragment_id)}}</span>" .
                            "<span ng-if=\"isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id linked\"" .
                            " ng-click=\"selectSubroutine( component.properties.fragment_id); \$event.stopPropagation()\">{{ getSubroutineName( component.properties.fragment_id)}}</span>" .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'process-processor-fragment.html'
                    ),
                    '_workflow' => 'process',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\FileReader',
                'x!File Reader',
                'Folders and Files',
                array(
                    'basePath' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'basePath',
                        'valueType' => 'string'
                    ),
                    'mode' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('folders' => 'Folders', 'files' => 'Files'),
                        ),
                        'defaultValue' => 'folders',
                        'name' => 'Mode',
                        'description' => '',
                        'valueType' => 'string'
                    ),
                    'var' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'VAR',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="we-say"><b>Reading {{component.properties.mode}} {{component.properties.basePath}}</b></div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'file-reader.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\ReadBlockAgainElement',
                'Read Block Again',
                'Runs read phase of current conversation block.',
                array(
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">RUN</span> ' .
                            '<span class="block-id">current block again</span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'read-block-again-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\PromptAccountLinkingElement',
                'Prompt Account Linking',
                'Indicates to vendors that they should show an account linking card',
                [
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">PROMPT</span> ' .
                            '<span>Account Linking</span>' .
                            '</div>'
                    ],
                    '_help' => [
                        'type' => 'file'
                    ],
                    '_workflow' => 'read'
                ]
            )
        );
    }
}
