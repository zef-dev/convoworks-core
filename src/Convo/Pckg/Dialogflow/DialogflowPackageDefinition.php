<?php declare(strict_types=1);

namespace Convo\Pckg\Dialogflow;

use Convo\Core\Factory\AbstractPackageDefinition;

class DialogflowPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-dialogflow';

	public function __construct( \Psr\Log\LoggerInterface $logger)
	{
		parent::__construct( $logger, self::NAMESPACE, __DIR__);
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
                '\Convo\Pckg\Dialogflow\Elements\SetSuggestionsElement',
                'Set suggestions',
                'Sets suggestions which should be displayed by a response. Works with devices that have the screen output capability.',
                array(
                    'value' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => null,
                        'name' => 'Suggestions',
                        'description' => 'Suggestions to be shown. Use ";" as delimiter to display more suggestion. Example usage: "suggestion 1;suggestion 2;suggestion 3" etc.',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            "<span class=\"statement\">{{ component.properties.value }}</span> " .
                            '</div>'
                    ),
                    '_workflow' => 'read',
                )
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Dialogflow\Elements\BrowseCarouselElement',
                'Browse Carousel Element',
                'Browse carousel enables you to open an web page from the conversation. (Works with devices that have the web browser capability.)',
                array(
                    'data_collection' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Items',
                        'description' => 'Iterates over a collection and renders a visual representation for each link in the browse carousel.',
                        'valueType' => 'string'
                    ),
                    'offset' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Offset',
                        'description' => 'Skip this many items from the beginning of the collection.',
                        'valueType' => 'string'
                    ],
                    'limit' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Limit',
                        'description' => 'Limit of how many items of the collection will be displayed.',
                        'valueType' => 'string'
                    ],
                    'browse_carousel_item_title' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item title',
                        'description' => 'Title of the visual representation of the link which is in the browse carousel.',
                        'valueType' => 'string'
                    ),
                    'browse_carousel_item_description' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item description',
                        'description' => 'Description of the visual representation of the link which is in the browse carousel.',
                        'valueType' => 'string'
                    ),
                    'browse_carousel_item_footer' => array(
                        'editor_type' => 'desc',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item footer',
                        'description' => 'Footer of the visual representation of the link which is in the browse carousel.',
                        'valueType' => 'string'
                    ),
                    'browse_carousel_item_image_url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item image url',
                        'description' => 'Link to the image of the visual representation of the link which is in the browse carousel.',
                        'valueType' => 'string'
                    ),
                    'browse_carousel_item_image_text' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item image text',
                        'description' => 'Accessibility text of the image of the visual representation of the link which is in the browse carousel. (Required if you want to display the image.)',
                        'valueType' => 'string'
                    ),
                    'browse_carousel_item_url' => array(
                        'editor_type' => 'text',
                        'editor_properties' => array(),
                        'defaultValue' => '',
                        'name' => 'Browse Carousel item URL',
                        'description' => 'Link to the website from an item of the browse carousel.',
                        'valueType' => 'string'
                    ),
                    '_preview_angular' => array(
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            '<ul class="list-unstyled">' .
                            ' <li>Browse Carousel items: {{component.properties.data_collection}}</li>' .
                            ' <li>Browse Carousel item title: {{component.properties.browse_carousel_item_title}}</li>' .
                            ' <li>Browse Carousel item description: {{component.properties.browse_carousel_item_description}}</li>' .
                            ' <li>Browse Carousel item image URL: {{component.properties.browse_carousel_item_image_url}}</li>' .
                            ' <li>Browse Carousel item image text: {{component.properties.browse_carousel_item_image_text}}</li>' .
                            ' <li>Browse Carousel item URL: {{component.properties.browse_carousel_item_url}}</li>' .
                            '</ul>' .
                            '</div>'
                    ),
                    '_workflow' => 'read',
                )
            ),
        );
	}
}
