<?php

declare(strict_types=1);

use Convo\Core\Expression\EvaluationContext;
use Convo\Core\Params\SimpleParams;
use Convo\Core\Util\ArrayUtil;
use PHPUnit\Framework\TestCase;
use Convo\Core\Util\EchoLogger;
use Psr\Log\LoggerInterface;
use Convo\Core\Expression\ExpressionFunction;
use Convo\Core\Expression\ExpressionFunctionProviderInterface;
use Zef\Zel\ArrayResolver;
use Zef\Zel\ObjectResolver;

class EvaluationContextTest extends TestCase
{
    /**
     * @var EvaluationContext
     */
    private $_evalContext;


    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function setUp(): void
    {
        $this->_logger      =   new EchoLogger();
        $this->_evalContext = new EvaluationContext($this->_logger, new class implements ExpressionFunctionProviderInterface {
            public function getFunctions()
            {
                $functions = [];
                $functions[] = ExpressionFunction::fromPhp('count');
                $functions[] = ExpressionFunction::fromPhp('rand');
                $functions[] = ExpressionFunction::fromPhp('strtolower');
                $functions[] = ExpressionFunction::fromPhp('date');
                $functions[] = ExpressionFunction::fromPhp('is_numeric');
                $functions[] = ExpressionFunction::fromPhp('is_int');
                $functions[] = ExpressionFunction::fromPhp('str_replace');

                $unwrap_cw_resolvers = function ($args, $data) use (&$unwrap_cw_resolvers) {
                    if (is_array($data)) {
                        $clean = [];

                        foreach ($data as $key => $val) {
                            $clean[$key] = $unwrap_cw_resolvers($args, $val);
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
                    'unwrap_cw_resolvers',
                    function ($data) {
                        return sprintf('unwrap_cw_resolvers(%1$data)', $data);
                    },
                    $unwrap_cw_resolvers
                );

                $functions[] = new ExpressionFunction(
                    'parse_mana',
                    function ($mana) {
                        return sprintf('(is_string(%1$s) ? parse_mana(%1$s) : %1$s', $mana);
                    },
                    function ($args, $mana) {
                        $map = [
                            'W' => 'White',
                            'B' => 'Black',
                            'G' => 'Green',
                            'R' => 'Red',
                            'U' => 'Blue'
                        ];

                        $pattern = '/{(.)}/';
                        $matches = [];

                        preg_match_all($pattern, $mana, $matches);
                        $count = array_count_values($matches[1]);

                        $ret = [];

                        foreach ($count as $symbol => $n) {
                            $ret[] = isset($map[$symbol]) ?
                                $n . ' ' . $map[$symbol] . ' mana' :
                                $symbol . ' Generic mana';
                        }

                        return implode(", ", $ret);
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

                return $functions;
            }
        });
    }

    /**
     * @dataProvider keepTypeProvider
     * @param string $key
     * @param mixed $context
     */
    public function testKeepType($key, $context)
    {
        $string    = '${' . $key . '}';

        $context = new ArrayResolver($context);

        $actual = $this->_evalContext->evalString($string, $context->getValues());

        $this->assertSame($context[$key], $actual);
    }

    public function keepTypeProvider()
    {
        return [
            ['val', ['val' => 1]],
            ['val', ['val' => '+1']]
        ];
    }


    /**
     * @dataProvider stringParsingProvider
     * @param string $expected
     * @param string $string
     * @param mixed $context
     */
    public function testStringParsingWorks($expected, $string, $context)
    {
        $context = new ArrayResolver($context);
        $actual = $this->_evalContext->evalString($string, $context->getValues());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider stringParsingProviderObjects
     * @param string $expected
     * @param string $string
     * @param mixed $context
     */
    public function testStringParsingFromObjectsWorks($expected, $string, $context)
    {
        $ctx = new ArrayResolver([
            'request' => new ObjectResolver($context)
        ]);

        $actual = $this->_evalContext->evalString($string, $ctx->get());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider arrayParsingProvider
     * @param array $expected
     * @param array $array
     * @param mixed $context
     */
    public function testArrayParsingWorks($expected, $array, $context)
    {
        $this->_logger->debug('-------------');
        $context = new ArrayResolver($context);

        $actual = $this->_evalContext->evalArray($array, $context->getValues());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider paramParsingProvider
     * @param array $expected
     * @param string $array
     * @param string $context
     */
    public function testParamSet($data, $string, $expected)
    {
        $params = new SimpleParams();

        foreach ($data as $key => $val) {
            $key    =    $this->_evalContext->evalString($key);
            $parsed =   $this->_evalContext->evalString($val);


            if (!ArrayUtil::isComplexKey($key)) {
                $params->setServiceParam($key, $parsed);
            } else {
                $root = ArrayUtil::getRootOfKey($key);
                $final = ArrayUtil::setDeepObject($key, $parsed, $params->getServiceParam($root) ?? []);
                $params->setServiceParam($root, $final);
            }
        }

        $context = new ArrayResolver($params->getData());
        $actual = $this->_evalContext->evalString($string, $context->getValues());

        $this->assertEquals($expected, $actual);
    }

    // Providers
    public function arrayParsingProvider()
    {
        return [
            [
                [
                    'message' => 'Hello world'
                ],
                [
                    'message' => '${greeting}'
                ],
                [
                    'greeting' => 'Hello world'
                ]
            ],
            [
                [
                    'message' => true
                ],
                [
                    'message' => '${bolVal}'
                ],
                [
                    'bolVal' => true
                ]
            ],
            [
                [
                    'message' => null
                ],
                [
                    'message' => '${noVal}'
                ],
                [
                    'bolVal' => true
                ]
            ],
            [
                [
                    'message' => true
                ],
                [
                    'message' => '${!noVal}'
                ],
                [
                    'bolVal' => false
                ]
            ],
            [
                [
                    'message' => 3
                ],
                [
                    'message' => '${result.value}'
                ],
                [
                    'result' => ['value' => 3]
                ]
            ],
            [
                [
                    'message' => null
                ],
                [
                    'message' => '${result.novalue}'
                ],
                [
                    'result' => ['value' => 3]
                ]
            ],
            [
                [
                    'message' => null
                ],
                [
                    'message' => '${result.novalue.novalue2}'
                ],
                [
                    'result' => ['value' => 3]
                ]
            ],
            [
                [
                    'message' => 3
                ],
                [
                    'message' => '${result.value + noResult}'
                ],
                [
                    'result' => ['value' => 3]
                ]
            ],
            [
                [
                    'message' => 3
                ],
                [
                    'message' => '${result.value + nullValue}'
                ],
                [
                    'result' => ['value' => 3],
                    'nullValue' => null
                ]
            ],
            [
                [
                    'message' => 'A'
                ],
                [
                    'message' => '${questions[item.index]["answers"][0]["letter"]}'
                ],
                [
                    "questions" => [
                        [
                            "answers" => [
                                [
                                    'letter' => 'A'
                                ]
                            ]
                        ]

                    ],
                    'item' => [
                        'index' => 0
                    ]
                ]
            ],
            [
                [
                    'message' => 'DOOM'
                ],
                [
                    'message' => '${games[items.position.indexed.value]["fist_person_shooters"][0]["name"]}'
                ],
                [
                    "games" => [
                        [
                            "fist_person_shooters" => [
                                [
                                    'name' => 'DOOM'
                                ]
                            ]
                        ]
                    ],
                    'items' => [
                        "position" => [
                            'indexed' =>  [
                                "value" => 0
                            ]
                        ]
                    ]
                ]
            ],
            [
                [
                    'message' => ['DOOM ETERNAL', 'CALL OF DUTY: VANGUARD']
                ],
                [
                    'message' => '${games}'
                ],
                [
                    "games" => ['DOOM ETERNAL', 'CALL OF DUTY: VANGUARD']
                ]
            ],
            [
                [
                    'message' => [
                        [
                            "fist_person_shooters" => [
                                [
                                    'name' => 'DOOM'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'message' => '${unwrap_cw_resolvers(games)}'
                ],
                [
                    "games" => [
                        [
                            "fist_person_shooters" => [
                                [
                                    'name' => 'DOOM'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                [
                    'message' => [
                        "fist_person_shooters" => [
                            [
                                'name' => 'DOOM'
                            ]
                        ]
                    ]
                ],
                [
                    'message' => '${game.get()}'
                ],
                [
                    "game" => [
                        "fist_person_shooters" => [
                            [
                                'name' => 'DOOM'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function stringParsingProvider()
    {
        return [
            'Hello world' => [
                'Hello world',
                '${message}',
                ['message' => 'Hello world']
            ],
            'User name' => [
                'Marko',
                '${user.name}',
                [
                    'user' => ['name' => 'Marko']
                ]
            ],
            'MtG' => [
                'Always bolt the bird',
                'Always ${action} the ${creatures[1].name}',
                [
                    'action' => 'bolt',
                    'creatures' => [
                        ['name' => 'goblin'],
                        ['name' => 'bird'],
                        ['name' => 'Pathrazer of Ulamog']
                    ]
                ]
            ],
            'str_replace' => [
                'Rimrock Knight - Boulder Dash',
                '${str_replace("//", "-", cardName)}',
                [
                    'cardName' => 'Rimrock Knight // Boulder Dash'
                ]
            ],
            'MtG Mana' => [
                'Gods Willing costs 1 White mana',
                'Gods Willing costs ${parse_mana(card.mana)}',
                [
                    'card' => [
                        'mana' => '{W}'
                    ]
                ]
            ],
            'Nested arrays' => [
                'My name is Marko, and I\'m a developer who uses PHP.',
                'My name is ${users[0].name}, and I\'m a ${users[0].professions[0].name} who uses ${users[0].professions[0].tool}.',
                [
                    'users' => [
                        [
                            'name' => 'Marko',
                            'professions' => [
                                [
                                    'name' => 'developer',
                                    'tool' => 'PHP'
                                ],
                                [
                                    'name' => 'carpenter',
                                    'tool' => 'rock'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Quoted expressions' => [
                'His name is "Test", and he\'s a "user".',
                'His name is "${name}", and he\'s a "${thing}".',
                [
                    "name" => "Test",
                    "thing" => "user"
                ]
            ],
            'Non-zero starting arrays (object)' => [
                'Your name is Milorad',
                'Your name is ${players[1].name}',
                [
                    'players' => [
                        1 => (object) ['name' => 'Milorad']
                    ]
                ]
            ],
            'Non-zero starting arrays (array)' => [
                'Your name is Milorad',
                'Your name is ${players[1]["name"]}',
                [
                    'players' => [
                        1 => ['name' => 'Milorad']
                    ]
                ]
            ],
            'URL string sample 1' => [
                'https://opentdb.com/api.php?amount=6&category=11&type=multiple',
                'https://opentdb.com/api.php?amount=${numberOfQuestions}&category=${categoryID}&type=multiple',
                [
                    'numberOfQuestions' => 6,
                    'categoryID' => 11
                ]
            ],
            'URL string sample 2' => [
                'https://www.example1.com/search?q=open+gz+string&sclient=gws-wiz',
                'https://www.example1.com/search?q=${search}&sclient=${sclient}',
                [
                    'search' => 'open+gz+string',
                    'sclient' => 'gws-wiz'
                ]
            ],
            'URL string sample 3' => [
                'https://www.example2.com/search?items=1,2,3,4,5',
                'https://www.example2.com/search?items=${items}',
                [
                    'items' => '1,2,3,4,5'
                ]
            ],
            'Alexa Dirty Weekend Date' => [
                '2015-W48 +5 days',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2015-W48-WE'
                    ]
                ]
            ],
            'Alexa Dirty Decade Date' => [
                '2020',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '202X'
                    ]
                ]
            ],
            'Alexa Dirty Winter Date' => [
                '2017-12',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2017-WI'
                    ]
                ]
            ],
            'Alexa Dirty Spring Date' => [
                '2017-03',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2017-SP'
                    ]
                ]
            ],
            'Alexa Dirty Summer Date' => [
                '2017-06',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2017-SU'
                    ]
                ]
            ],
            'Alexa Dirty Fall Date' => [
                '2017-09',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2017-FA'
                    ]
                ]
            ],
            'Alexa Dirty Night Date' => [
                '2022-02-11 23:00',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2022-02-11 NI'
                    ]
                ]
            ],
            'Alexa Dirty Morning Date' => [
                '2022-02-11 05:00',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2022-02-11 MO'
                    ]
                ]
            ],
            'Alexa Dirty Afternoon Date' => [
                '2022-02-11 13:00',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2022-02-11 AF'
                    ]
                ]
            ],
            'Alexa Dirty Evening Date' => [
                '2022-02-11 19:00',
                '${parse_date_time(result.date)}',
                [
                    'result' => [
                        'date' => '2022-02-11 EV'
                    ]
                ]
            ],
            'Alexa Duration of 10 minutes' => [
                600,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'PT10M'
                    ]
                ]
            ],
            'Alexa Duration of 5 hours' => [
                18000,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'PT5H'
                    ]
                ]
            ],
            'Alexa Duration of 3 days' => [
                259200,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'P3D'
                    ]
                ]
            ],
            'Alexa Duration of 30 seconds' => [
                30,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'PT30S'
                    ]
                ]
            ],
            'Alexa Duration of 8 weeks' => [
                4838400,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'P8W'
                    ]
                ]
            ],
            'Alexa Duration of 5 hours and 10 minutes' => [
                18600,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => 'PT5H10M'
                    ]
                ]
            ],
            'Alexa Duration slot resolved as null default is 30 seconds' => [
                30,
                '${parse_duration(result.duration)}',
                [
                    'result' => [
                        'duration' => null
                    ]
                ]
            ]
        ];
    }

    public function stringParsingProviderObjects()
    {
        return [
            'Japanese UTF-8' => [
                'ć—Ąćś¬čŞžă�Żă‚Źă�‹ă‚Šă�ľă�™',
                'ć—Ąćś¬čŞžă�Ż${request.dictionary.know.formal}',
                self::arrayToObj([
                    'dictionary' => [
                        'know' => [
                            'formal' => 'ă‚Źă�‹ă‚Šă�ľă�™',
                            'informal' => 'ă‚Źă�‹ă‚‹'
                        ],
                        'not know' => [
                            'formal' => 'ă‚Źă�‹ă‚Šă�ľă�›ă‚“',
                            'informal' => 'ă‚Źă�‹ă‚‰ă�Şă�„'
                        ]
                    ]
                ])
            ]
        ];
    }

    public function paramParsingProvider()
    {
        $obj = new stdClass();
        $obj->messages = [];
        return [
            [
                [
                    'obj' => $obj,
                    'obj.messages[0]["role"]' => 1,
                ],
                '${obj.messages[0]["role"]}',
                1
            ],
            [
                [
                    'messages[0]["role"]' => 1,
                ],
                '${messages[0]["role"]}',
                1
            ],
            [
                [
                    'simple' => 1,
                ],
                '${simple}',
                1
            ],
            [
                [
                    'number' => 22,
                ],
                'The number is ${number}',
                'The number is 22'
            ],
            [
                [
                    'first' => true,
                    'second' => false
                ],
                'The next statement is ${first ? "true" : "false"}. The previous statement is ${second ? "true" : "false"}.',
                'The next statement is true. The previous statement is false.'
            ],
            [
                [
                    'array' => '${[1, 2, 3]}'
                ],
                '${array[0]}',
                1
            ],
            [
                [
                    'data["name"]' => "John"
                ],
                'My name is ${data["name"]}',
                'My name is John'
            ],
            [
                [
                    'user' => '${{"name": "Test"}}'
                ],
                'User name: ${user.name}',
                'User name: Test'
            ],
            [
                [
                    'card' => '${{"name": "Pack Rat", "mana_cost": "{1}{B}", "cmc": 2}}'
                ],
                '${card.name} (${card.mana_cost}) ${card.cmc}',
                'Pack Rat ({1}{B}) 2'
            ],
            [
                [
                    'number' => '${0}'
                ],
                'The number is ${number === 0 ? "zero" : "not zero"}',
                'The number is zero'
            ],
            [
                [
                    'boolean' => '${false}'
                ],
                'The boolean is ${boolean === false ? "boolean false" : "not boolean false"}',
                'The boolean is boolean false'
            ],
            [
                [
                    'boolean' => '${true}'
                ],
                'The boolean is ${boolean === true ? "boolean true" : "not boolean true"}',
                'The boolean is boolean true'
            ]
        ];
    }

    public static function arrayToObj($arr)
    {
        return json_decode(json_encode($arr));
    }
}
