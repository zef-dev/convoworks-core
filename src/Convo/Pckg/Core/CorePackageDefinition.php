<?php declare(strict_types=1);

namespace Convo\Pckg\Core;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Intent\SystemEntity;
use Convo\Core\Intent\EntityModel;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Psr\SimpleCache\CacheInterface;
use Convo\Core\Workflow\IRunnableBlock;

class CorePackageDefinition extends AbstractPackageDefinition
{
    const NAMESPACE	=	'convo-core';
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
        $this->_httpFactory				=	$httpFactory;
        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_cache                   =   $cache;

        parent::__construct( $logger, self::NAMESPACE, __DIR__);

        $this->addTemplate( $this->_loadFile( __DIR__ .'/blank.template.json'));
        $this->addTemplate( $this->_loadFile( __DIR__ .'/convo-daily-quotes.template.json'));
    }

    protected function _initIntents()
    {
        return $this->_loadIntents( __DIR__ .'/system-intents.json');
    }

    protected function _initEntities()
    {
        $entities  =    [];
        $entities['number'] =   new SystemEntity( 'number');
        $entities['number']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.NUMBER', true));
        $entities['number']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.number-integer', true));

        $entities['ordinal'] =   new SystemEntity( 'ordinal');
        $entities['ordinal']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Ordinal', true));
        $entities['ordinal']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.ordinal', true));

        $entities['city'] = new SystemEntity('city');
        $entities['city']->setPlatformModel('amazon', new EntityModel('AMAZON.City', true));
        $entities['city']->setPlatformModel('dialogflow', new EntityModel('@sys.geo-city', true));

        $entities['country'] = new SystemEntity('country');
        $entities['country']->setPlatformModel('amazon', new EntityModel('AMAZON.Country', true));
        $entities['country']->setPlatformModel('dialogflow', new EntityModel('@sys.geo-country', true));

        $entities['any'] = new SystemEntity( 'any');
        $entities['any']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.SearchQuery', true));
        $entities['any']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.any', true));

        $entities['person'] = new SystemEntity( 'person');
        $entities['person']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.FirstName', true));
        $entities['person']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.person', true));

        $entities['person_first_and_lastname'] = new SystemEntity( 'person_first_and_lastname');
        $entities['person_first_and_lastname']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Person', true));
        $entities['person_first_and_lastname']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.person', true));

        $entities['artist'] = new SystemEntity( 'artist');
        $entities['artist']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Artist', true));
        $entities['artist']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.music-artist', true));

        $entities['song'] = new SystemEntity( 'song');
        $entities['song']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.MusicRecording', true));
        $entities['song']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.any', true));

        $entities['genre'] = new SystemEntity( 'genre');
        $entities['genre']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Genre', true));
        $entities['genre']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.music-genre', true));

        $entities['music_playlist'] = new SystemEntity( 'music_playlist');
        $entities['music_playlist']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.SearchQuery', true));
        $entities['music_playlist']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.any', true));

        $entities['date'] = new SystemEntity( 'date');
        $entities['date']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.DATE', true));
        $entities['date']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.date', true));

        $entities['time'] = new SystemEntity( 'time');
        $entities['time']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.TIME', true));
        $entities['time']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.time', true));

        $entities['color'] = new SystemEntity( 'color');
        $entities['color']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Color', true));
        $entities['color']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.color', true));

        $entities['language'] = new SystemEntity( 'language');
        $entities['language']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Language', true));
        $entities['language']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.language', true));

        $entities['airport'] = new SystemEntity( 'airport');
        $entities['airport']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.Airport', true));
        $entities['airport']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.airport', true));

        $entities['duration'] = new SystemEntity( 'duration');
        $entities['duration']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.DURATION', true));
        $entities['duration']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.duration', true));

        $entities['phone_number'] = new SystemEntity( 'phone_number');
        $entities['phone_number']->setPlatformModel( 'amazon', new EntityModel( 'AMAZON.PhoneNumber', true));
        $entities['phone_number']->setPlatformModel( 'dialogflow', new EntityModel( '@sys.phone-number', true));
        return $entities;
    }

    public function getFunctions()
    {
        $functions = [];

        $functions[] = ExpressionFunction::fromPhp('count');
        $functions[] = ExpressionFunction::fromPhp('rand');
        $functions[] = ExpressionFunction::fromPhp('strtolower');
        $functions[] = ExpressionFunction::fromPhp('strtoupper');
        $functions[] = ExpressionFunction::fromPhp('date');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('is_int');
        $functions[] = ExpressionFunction::fromPhp('intval');
        $functions[] = ExpressionFunction::fromPhp('strlen');
        $functions[] = ExpressionFunction::fromPhp('array_rand');
        $functions[] = ExpressionFunction::fromPhp('urlencode');
        $functions[] = ExpressionFunction::fromPhp('str_replace');
        $functions[] = ExpressionFunction::fromPhp('array_splice');
        $functions[] = ExpressionFunction::fromPhp('is_array');
        $functions[] = ExpressionFunction::fromPhp('in_array');
        $functions[] = ExpressionFunction::fromPhp('array_search');
        $functions[] = ExpressionFunction::fromPhp('array_merge');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('substr');
        $functions[] = ExpressionFunction::fromPhp('stripos');
        $functions[] = ExpressionFunction::fromPhp('strtotime');
        $functions[] = ExpressionFunction::fromPhp('time');

        // CUSTOM
        $functions[] = new ExpressionFunction(
            'array_shuffle',
            function ($array) {
                return sprintf('(is_array(%1$a) ? array_shuffle(%1$a) : %1$a', $array);
            },
            function($args, $array) {
                if (!is_array($array)) {
                    return $array;
                }

                $copy = $array;
                shuffle($copy);
                return $copy;
            }
        );

        $functions[] = new ExpressionFunction(
            'decode_special_chars',
            function ($string) {
                return sprintf('(is_string(%1$a) ? decode_special_chars(%1$a) : %1$a', $string);
            },
            function($args, $string) {
                if (!is_string($string)) {
                    return $string;
                }

                $string = html_entity_decode( $string, ENT_QUOTES);
                $string = htmlspecialchars_decode( $string);
                $string = urldecode( $string);
                return $string;
            }
        );

        $functions[] = new ExpressionFunction(
            'array_sort_by_field',
            function ($array, $field) {
                return sprintf('(is_array(%1$a) ? array_sort_by_field(%1$a, %2$a) : %1$a', $array);
            },
            function($args, $array, $field) {
                if (!is_array($array)) {
                    return $array;
                }
//                 $this->_logger->debug( 'helou'.print_r( $args, true));
                usort($array, function ($a, $b) use ($field) {return strcmp( $a[$field], $b[$field]);});
                return $array;
            }
        );

        $functions[] = new ExpressionFunction(
            'ordinal',
            function ($string) {
                return sprintf('(is_numeric(%1$a) ? ordinal(%1$a) : %1$a', $string);
            },
            function($args, $string) {
                if (!is_numeric($string)) {
                    return $string;
                }

                $integer = intval( $string);
                $ends = array('th','st','nd','rd','th','th','th','th','th','th');

                if( (($integer % 100) >= 11) && (($integer%100) <= 13)) {
                    return $integer. 'th';
                } else {
                    return $integer. $ends[$integer % 10];
                }
            }
        );

        $functions[] = new ExpressionFunction(
            'human_concat',
            function ($array,  $conjunction) {
                return sprintf('(is_array(%1$a) ? human_concat(%1$a, %2$a) : %1$a', $array);
            },

            function($args, $array, $conjunction = null) {
                if (!is_array($array)) {
                    return $array;
                }

                $last  = array_slice( $array, -1);
                $first = join(', ', array_slice($array, 0, -1));
                $both  = array_filter( array_merge( array( $first), $last), 'strlen');

                if( $conjunction) {
                    return join(' '.$conjunction.' ', $both);
                }

                return join(', ', $both);
            }
        );

        return $functions;
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
                'Text response',
                'Text which will be responded to the user. Response can be default response or reprompt. You can use SSML to tune up response.',
                array(
                    'type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('default' => 'Default', 'reprompt' => 'Reprompt'),
                        ),
                        'defaultValue' => 'default',
                        'name' => 'Type',
                        'description' => 'Type of the response definition',
                        'valueType' => 'string'
                    ),
                    'text' => array(
                        'editor_type' => 'ssml',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Text',
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
                            'options' => array('normal' => 'Normal', 'long-form' => 'Long Form', 'music' => 'Music', 'news' => 'News'),
                        ),
                        'defaultValue' => 'normal',
                        'name' => 'Alexa Domain',
                        'description' => 'Domain of spoken text by Alexa',
                        'valueType' => 'string'
                    ),
                    'alexa_emotion' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('neutral' => 'Neutral', 'excited' => 'Excited', 'disappointed' => 'Disappointed'),
                        ),
                        'defaultValue' => 'neutral',
                        'name' => 'Alexa Emotion',
                        'description' => 'Emotion of spoken text by Alexa',
                        'valueType' => 'string'
                    ),
                    'alexa_emotion_intensity' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('low' => 'Low', 'medium' => 'Medium', 'high' => 'High'),
                        ),
                        'defaultValue' => 'medium',
                        'name' => 'Alexa Emotion Intensity',
                        'description' => 'Emotion intensity of spoken text by Alexa',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="we-say">' .
                            '{{ component.properties.type == \'default\' ? \'Say:\' : \'Repeat:\' }} <span class="we-say-text">{{component.properties.text}}</span>' .
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
                'Editor comment',
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
                '\Convo\Pckg\Core\Elements\GoToElement',
                'GOTO',
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
                            "<span class=\"statement\">{{ component.properties.value == 'next' ? 'NEXT' : 'GOTO' }}</span> ".
                            "<span ng-if=\"!isBlockLinkable( component.properties.value)\" class=\"block-id\">{{ getBlockName( component.properties.value)}}</span>" .
                            "<span ng-if=\"isBlockLinkable( component.properties.value)\" class=\"block-id linked\"".
                            " ng-click=\"selectBlock( component.properties.value); \$event.stopPropagation()\">{{ getBlockName( component.properties.value)}}</span>" .
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
                'INCLUDE',
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
                        "<span ng-if=\"isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id linked\"".
                        " ng-click=\"selectSubroutine( component.properties.fragment_id); \$event.stopPropagation()\">{{ getSubroutineName( component.properties.fragment_id)}}</span>" .
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
                            'options' => ['session' => 'Session', 'installation' => 'Installation']
                        ],
                        'defaultValue' => 'session',
                        'name' => 'Run once per',
                        'description' => 'Run the child once per either session or installation',
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
                        'description' => 'Children to be run',
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
                        'description' => 'Else flow',
                        'valueType' => 'class'
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
                'Set parameter element',
                'Sets parameters',
                array(
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'request' => 'Request'),
                        ),
                        'defaultValue' => 'request',
                        'name' => 'Scope type',
                        'description' => 'Id under which parameters are stored',
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
                        'template' => '<div class="code">Set parameters in <span class="statement">{{ component.properties.scope_type.toUpperCase() }}</span><br>' .
                            ' <span ng-repeat="(key, val) in component.properties.properties track by key"><span class="statement">LET</span> <b>{{ key}}</b> = <b>{{ val}};</b><br></span>' .
                            '</div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'set-parameter-element.html'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\IfElement',
                'IF',
                'Test against an expression and if it evaluates to true executes the THEN flow. If it is false, this element searches for ELSE IF sub-component which will evaluate to true and if it fails, will execute ELSE flow',
                [
                    'test' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Test',
                        'description' => 'String variable to evaluate',
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
                        'description' => 'Flow to be executed if test is evaluated as TRUE',
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
                        'description' => 'Flow to be executed if test is evaluated as FALSE',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">IF</span> <b>{{ component.properties.test }}</b>' .
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
                'ELSE IF',
                'Test against an expression and execute own THEN flow if true',
                [
                    'test' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Test',
                        'description' => 'Test to be checked',
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
                        'description' => 'Flow to be executed if test is evaluated as TRUE',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">ELSE IF</span> <b>{{ component.properties.test }}</b>' .
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
                'LOOP',
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
                        'defaultValue' => '',
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
                'FOR',
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
                        'description' => 'How many times should be executed',
                        'valueType' => 'string'
                    ),
                    'status_var' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Status variable',
                        'description' => 'Result variable name',
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
                'END session',
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
                '\Convo\Pckg\Core\Processors\SimpleProcessor',
                'Simple processor',
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
                        'description' => 'Flow to be executed if processor is matched',
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
                'Reads platform intents. Use here specific platform intent names',
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
                        'template' => '<div class="code">Catch platform intent <b>{{ component.properties.intent}}</b>'.
                            '<span ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span>'.
                            '<span ng-repeat="(key,val) in component.properties.rename track by key">, rename slot <b>{{ key }} => result.{{ val }}</b></span></div>'
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
                    'required_slots' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(
                            'multiple' => false
                        ),
                        'defaultValue' => '',
                        'name' => 'Required slots',
                        'description' => 'List of required slot names (comma separated). If required slot is empty, filter will not activate even if intent name is correct.',
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
                        'description' => 'Use values but with different name',
                        'valueType' => 'array'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">Catch Convoworks intent '.
                            '<span ng-if="component.properties.intent && !isSystemIntent(component.properties.intent)" class="block-id linked" ng-click="gotoIntent(component.properties.intent); $event.stopPropagation();">{{ component.properties.intent}}</span>'.
                            '<b ng-if="component.properties.intent && isSystemIntent(component.properties.intent)">{{ component.properties.intent}}</b>'.
                            '<span ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span>'.
                            '<span ng-repeat="(key,val) in component.properties.rename track by key">, rename slot <b>{{ key }} => result.{{ val }}</b></span></div>'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'convo-intent-reader.html'
                    ),
                    '_workflow' => 'filter',
                    '_descend' => true,
                    '_factory' => new class ($this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct( $packageProviderFactory)
                        {
                            $this->_packageProviderFactory	= $packageProviderFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new \Convo\Pckg\Core\Filters\ConvoIntentReader( $properties, $this->_packageProviderFactory);
                        }
                    }
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Processors\YesNoProcessor',
                'Yes/No processor',
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
                            '</div>' .
                            ''
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'yes-no-processor.html'
                    ),
                    '_workflow' => 'process',
                    '_factory' => new class ( $this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct( $packageProviderFactory)
                        {
                            $this->_packageProviderFactory	=	$packageProviderFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new \Convo\Pckg\Core\Processors\YesNoProcessor( $properties, $this->_packageProviderFactory, $service);
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
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
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
                'Element randomizer',
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
                        'description' => 'Selects wild or smart mode',
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
                        'description' => 'Containing text to be randomized',
                        'valueType' => 'class'
                    ),
                    'namespace' => array(
                        'editor_type' => 'string',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Namespace',
                        'description' => 'Stored text variations for smart mode.',
                        'valueType' => 'string'
                    ),
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'request' => 'Request'),
                        ),
                        'defaultValue' => 'installation',
                        'name' => 'Scope type',
                        'description' => 'Id under which parameters are stored',
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
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\HttpQueryElement',
                'HTTP client',
                'Perform an HTTP request to a specified endpoint',
                array(
                    'scope_type' => array(
                        'editor_type' => 'select',
                        'editor_properties' => array(
                            'options' => array('session' => 'Session', 'installation' => 'Installation', 'request' => 'Request'),
                        ),
                        'defaultValue' => 'session',
                        'name' => 'Scope type',
                        'description' => 'Id under which parameters are stored',
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
                        'defaultValue' => '',
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
                            'options' => array( 'AUTO'=> 'AUTO', 'JSON' => 'JSON', 'TEXT' => 'TEXT')
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
                            'options' => array( 'GET'=> 'GET', 'POST' => 'POST')
                        ),
                        'defaultValue' => 'GET',
                        'name' => 'HTTP method',
                        'description' => 'Method by which to perform the request',
                        'valueType' => 'string'
                    ),
                    'cache_timeout' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
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
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Request body',
                        'description' => 'JSON body to send with POST request',
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
                        'description' => 'Flow to be executed if an http request was successful. [example usage in ok flow: ${row.body} -> to get data from the retrieved response]',
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
                        'description' => 'Flow to be executed if an http request was unsuccessful. [example usage in nok flow: ${row.code} -> to get error code ${row.error} -> to get error message]',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<span class="statement">{{ component.properties.method }}</span> {{ component.properties.method === \'GET\' ? \'from\' : \'to\' }} <b>{{ component.properties.url }}</b>' .
                            '</div>'
                    ),
                    '_help' => array(
                        'type' => 'file',
                        'filename' => 'http-client-element.html'
                    ),
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'http-query-element.html'
                    ),
                    '_workflow' => 'read',
                    '_factory' => new class ( $this->_httpFactory, $this->_cache) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_httpFactory;
                        private $_cache;
                        public function __construct( $httpFactory, $cache)
                        {
                            $this->_httpFactory	=	$httpFactory;
                            $this->_cache    	=	$cache;
                        }
                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Core\Elements\HttpQueryElement($properties, $this->_httpFactory, $this->_cache);
                        }
                },
                )
            ), new \Convo\Core\Factory\ComponentDefinition(
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
                'Conversation block',
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
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true,
                            'roles' => [IRunnableBlock::ROLE_CONVERSATION_BLOCK, IRunnableBlock::ROLE_SESSION_START, IRunnableBlock::ROLE_SESSION_ENDED]
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
                    '_system' => true
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\MediaBlock',
                'Media block',
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
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => 'search_media',
                        'name' => 'Source',
                        'description' => 'Source Media Context id',
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
                    '_factory' => new class ($this->_packageProviderFactory) implements \Convo\Core\Factory\IComponentFactory
                    {
                        private $_packageProviderFactory;
                        public function __construct( \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory)
                        {
                            $this->_packageProviderFactory	=	$packageProviderFactory;
                        }
                        public function createComponent( $properties, $service)
                        {
                            return new \Convo\Pckg\Core\Elements\MediaBlock( $properties, $service, $this->_packageProviderFactory);
                        }
                    }
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\MediaInfoElement',
                'Media info element',
                'Provides info about current songs in the connected Media Context component',
                array(
                    'context_id' => array(
                        'editor_type' => 'text',
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
                'Start Audio playback',
                'Initiates audio playback and automatically stops the current session.',
                array(
                    'context_id' => array(
                        'editor_type' => 'text',
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
                        'template' => '<div class="code"><span class="statement">START PLAYBACK</span> on <b>{{component.properties.context_id}}</b></div>'
                    ),
                    '_interface' => '\Convo\Core\Workflow\IConversationElement',
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'start-audio-playback.html'
                    ),
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Elements\LoopBlock',
                'Loop block',
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
                        'defaultValue' => '',
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
                    'elements' => array(
                        'editor_type' => 'service_components',
                        'editor_properties' => array(
                            'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
                            'multiple' => true
                        ),
                        'defaultValue' => array(),
                        'name' => 'Read phase',
                        'description' => 'Elements to be executed in read phase',
                        'valueType' => 'class'
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
                    '_workflow' => 'read',
                    '_system' => true
                )
                ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Core\Filters\NopRequestFilter',
                'NOP filter',
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
                        'template' => '<div>' .
                            '<b>{{ component.properties.empty === \'empty\' ? \'Will not activate\' :  \'Always activated\' }}</b>' .
                            '</div>'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code"><b>{{ component.properties.empty === \'empty\' ? \'Will not activate\' :  \'Always activated\' }}</b>'.
                        '<span ng-if="component.properties.empty != \'empty\'" ng-repeat="(key,val) in component.properties.values track by key">, use predefined value <b>result.{{ key }} = \'{{ val }}\'</b></span>'
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
                'Elements fragment',
                'Read workflow fragment',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'read-fragment',
                        'name' => 'Fragment name',
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
                'Processors fragment',
                'Fragment which contains processors',
                array(
                    'fragment_id' => array(
                        'editor_type' => 'block_id',
                        'editor_properties' => array(),
                        'defaultValue' => 'process-fragment',
                        'name' => 'Fragment name',
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
                'INCLUDE',
                'Include processor fragment',
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
                        "<span ng-if=\"isSubroutineLinkable( component.properties.fragment_id)\" class=\"block-id linked\"".
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
