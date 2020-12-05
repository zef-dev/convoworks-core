<?php

declare(strict_types=1);

namespace Convo\Pckg\Text;

use Convo\Core\Factory\AbstractPackageDefinition;

class TextPackageDefinition extends AbstractPackageDefinition
{
    const NAMESPACE = 'convo-text';

    public function __construct($logger)
    {
        parent::__construct($logger, self::NAMESPACE, __DIR__);
    }

    protected function _initDefintions()
    {
        return [
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Text\Filters\Filt\OrFilter',
                'OR Filter',
                'Activates if any of its children are also activated.',
                [
                    'filters' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allowed_interfaces' => ['\Convo\Pckg\Text\Filters\Filt\IPlainTextFilter'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Child filters',
                        'description' => 'Child filters to be applied on user query',
                        'valueType' => 'class'
                    ],
                    'collect_all' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => true,
                        'name' => 'Collect all values',
                        'description' => 'If false, the result will contain only the slot values of the child filter that was activated first. Otherwise, the result will gather the values of ALL child filters. ',
                        'valueType' => 'boolean'
                    ],
                    '_workflow' => 'filter',
                    '_descend' => true
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Text\Filters\Filt\AndFilter',
                'AND filter',
                'Activates if all of its children are activated',
                [
                    'filters' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Pckg\Text\Filters\Filt\IPlainTextFilter'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Child filters',
                        'description' => 'Child filters to be applied on user query',
                        'valueType' => 'class'
                    ],
                    '_workflow' => 'filter',
                    '_descend' => true
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Text\Filters\PlainTextRequestFilter',
                'Plain text request filter',
                'Reacts to any non-empty request',
                [
                    'filters' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => [\Convo\Pckg\Text\Filters\Filt\IPlainTextFilter::class],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => true,
                        'name' => 'Plain Text request filter',
                        'description' => 'Matches any non-empty request',
                        'valueType' => 'class'
                    ],
                    '_workflow' => 'filter'
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Text\Filters\Filt\StriposFilter',
                'Simple string filter',
                'Find a substring within text',
                [
                    'search' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => '',
                        'name' => 'Search string',
                        'description' => 'Substring to look for within user input. This will be added to the result variable, unless you also specify the predetermined slot value.',
                        'valueType' => 'string'
                    ],
                    'slot_name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => '',
                        'name' => 'Slot name',
                        'description' => 'Name under which to store the match',
                        'valueType' => 'string'
                    ],
                    'slot_value' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => null,
                        'name' => 'Slot value',
                        'description' => 'Predefined value to set into the slot',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code">' .
                            'Find <b>"{{ component.properties.search }}"</b>' .
                            '<span ng-if="component.properties.slot_name">, save match as <b>{{ component.properties.slot_name }}</b></span>' .
                            '<span ng-if="component.properties.slot_value">, set value to <b>{{ component.properties.slot_value }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'filter',
                    '_descend' => true
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Text\Filters\Filt\RegexFilter',
                'Regex filter',
                'Filters based on a regex',
                [
                    'regex' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => '',
                        'name' => 'Regex',
                        'description' => 'Regular expression to match with. You do not need to input the enclosing forward slashes (//), they are automatically added.',
                        'valueType' => 'string'
                    ],
                    'slot_name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => null,
                        'name' => 'Slot name',
                        'description' => 'Name under which to store the main match',
                        'valueType' => 'string'
                    ],
                    'slot_value' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => null,
                        'name' => 'Slot value',
                        'description' => 'Predefined value to set into the slot',
                        'valueType' => 'string'
                    ],
                    'slot_name_raw' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'multiple' => false
                        ],
                        'defaultValue' => null,
                        'name' => 'Slot name',
                        'description' => 'Name under which to store the raw match',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                        '<div class="code">' .
                            '<div class="statement">MATCH</div> <b>{{ component.properties.regex }}</b>' .
                            '<span ng-if="component.properties.slot_name">, save match as <b>{{ component.properties.slot_name }}</b></span>' .
                            '<span ng-if="component.properties.slot_value">, set value to <b>{{ component.properties.slot_value }}</b></span>' .
                            '<span ng-if="component.properties.slot_name_raw">, save raw value as <b>{{ component.properties.slot_name_raw }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'filter',
                    '_descend' => true
                ]
            ),
        ];
    }
}
