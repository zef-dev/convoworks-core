<?php

namespace Convo\Pckg\Visuals;

use Convo\Core\Factory\AbstractPackageDefinition;

class VisualsPackageDefinition extends AbstractPackageDefinition
{
    const NAMESPACE = 'convo-visuals';

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct($logger, self::NAMESPACE, __DIR__);
    }

    protected function _initDefintions()
    {
        return [
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Visuals\Elements\ListElement',
                'List',
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
                '\Convo\Pckg\Visuals\Elements\CardElement',
                'Card',
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
            )
        ];
    }
}