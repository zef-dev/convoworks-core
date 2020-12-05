<?php declare(strict_types=1);

use Convo\Core\EvaluationContext;
use PHPUnit\Framework\TestCase;
use Convo\Core\Util\EchoLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
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
        $this->_logger  	=   new EchoLogger();
        $this->_evalContext = new EvaluationContext( $this->_logger, new class implements ExpressionFunctionProviderInterface {
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
								$n.' '.$map[$symbol].' mana' :
								$symbol.' Generic mana';
						}

						return implode(", ", $ret);
					}
				);

				return $functions;
			}
		});
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

        $actual = $this->_evalContext->evalString($string, $ctx->getValues());

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
        $this->_logger->debug( '-------------');
        $context = new ArrayResolver($context);

        $actual = $this->_evalContext->evalArray($array, $context->getValues());

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
                    'result' => [ 'value' => 3]
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
                    'result' => [ 'value' => 3]
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
                    'result' => [ 'value' => 3]
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
                    'result' => [ 'value' => 3]
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
                    'result' => [ 'value' => 3],
                    'nullValue' => null
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
                        ['name' => 'goblin'], ['name' => 'bird'], ['name' => 'Pathrazer of Ulamog']
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
			'Non-zero starting arrays (object)' => [
				'Your name is Milorad',
				'Your name is ${players[1].name}',
				[
					'players' => [
						1 => (object) [ 'name' => 'Milorad' ]
					]
				]
			],
			'Non-zero starting arrays (array)' => [
				'Your name is Milorad',
				'Your name is ${players[1]["name"]}',
				[
					'players' => [
						1 => [ 'name' => 'Milorad' ]
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

    public static function arrayToObj( $arr)
    {
    	return json_decode( json_encode( $arr));
    }
}
