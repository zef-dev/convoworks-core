<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\ComponentDefinition;
use Convo\Core\Factory\IComponentFactory;
use Convo\Pckg\Alexa\Elements\AplCommandElement;
use Convo\Pckg\Alexa\Workflow\IAplCommandElement;

class AmazonPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-alexa';

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

	public function __construct(
	    \Psr\Log\LoggerInterface $logger,
        \Convo\Core\Util\IHttpFactory $httpFactory,
        \Convo\Core\IServiceDataProvider $convoServiceDataProvider
    ) {
        $this->_httpFactory = $httpFactory;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;

		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

	protected function _initDefintions()
    {
        return [
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\GetAmazonUserElement',
                'Init Amazon user',
                'Initialize an Amazon user.',
                [
                    'initialized_user_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'user',
                        'name' => 'Name',
                        'description' => 'Name under which to store the loaded user object in the context',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Load Amazon User and set it as <span class="statement"><b>{{ component.properties.initialized_user_var }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'get-amazon-user-element.html'
                    ),
                    '_factory' => new class ($this->_httpFactory, $this->_convoServiceDataProvider) implements IComponentFactory
                    {
                        private $_httpFactory;
                        private $_convoServiceDataProvider;

                        public function __construct($httpFactory, $convoServiceDataProvider)
                        {
                            $this->_httpFactory = $httpFactory;
                            $this->_convoServiceDataProvider = $convoServiceDataProvider;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\GetAmazonUserElement($properties, $this->_httpFactory, $this->_convoServiceDataProvider);
                        }
                    }
                ]
            ),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\GenericAplElement',
				'Generic APL Element',
				'Prepares an APL response from an APL definition.',
				array(
					'use_hashtag_sign' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Use #{} for evaluation?',
						'description' => 'Since Alexa APL uses ${} as their data-binding syntax, it clashes with our data binding syntax. To avoid that just enable the option and start evaluating via #{}.',
						'valueType' => 'boolean'
					),
					'name' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Template Token',
						'description' => 'Template Token of the APL definition.',
						'valueType' => 'string'
					),
					'apl_definition' => array(
						'editor_type' => 'desc',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'APL Definition',
						'description' => 'Definition on an APL document.',
						'valueType' => 'string'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'generic-epl-element.html'
					),
					'_workflow' => 'read',
					'_platform_defaults' => array(
						'amazon' => array(
							'interfaces' => array('ALEXA_PRESENTATION_APL')
						)
					),
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplExecuteCommandsElement',
				'APL Execute Commands',
				'Prepares an APL Execute Commands Response of APL Set Value Command Element children.',
				array(
					'name' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Template Token',
						'description' => 'Template Token of the APL document that the commands will be executed on..',
						'valueType' => 'string'
					),
					'apl_commands' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => [\Convo\Pckg\Alexa\Workflow\IAplCommandElement::class],
							'multiple' => true
						),
						'defaultValue' => array(),
						'defaultOpen' => true,
						'name' => 'APL Commands',
						'description' => 'APL Commands to be added.',
						'valueType' => 'class'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_workflow' => 'read',
					'_platform_defaults' => array(
						'amazon' => array(
							'interfaces' => array('ALEXA_PRESENTATION_APL')
						)
					),
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Filters\AplUserEventReader',
				'APL User Event',
				'Reads APL User Events. Use for matching specific APL User Event Argument Part or any APL User Event.',
				array(
					'use_apl_user_event_argument_part' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Use APL User Event Argument Part',
						'description' => 'If this value is false, any APL User Event will be caught. Otherwise, only specified APL User Event name will be caught.',
						'valueType' => 'boolean'
					),
					'apl_user_event_argument_part' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.use_apl_user_event_argument_part === true"
						),
						'defaultValue' => '',
						'name' => 'APL User Event Argument Part',
						'description' => 'Name of the APL User Event which activates this filter.',
						'valueType' => 'string'
					),
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Catch <b>{{ !component.properties.use_apl_user_event_argument_part ? "Any " : ""}}</b> APL User Event <b>{{ component.properties.use_apl_user_event_argument_part ? "Argument Part " + component.properties.apl_user_event_argument_part : ""}}</b></div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-user-event-reader.html'
					),
					'_workflow' => 'filter',
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplCommandElement',
				'APL Command Element',
				'Prepares an APL command.',
				array(
					'command_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => [
								IAplCommandElement::APL_COMMAND_TYPE_AUTO_PAGE => IAplCommandElement::APL_COMMAND_TYPE_AUTO_PAGE,
								IAplCommandElement::APL_COMMAND_TYPE_CLEAR_FOCUS => IAplCommandElement::APL_COMMAND_TYPE_CLEAR_FOCUS,
								IAplCommandElement::APL_COMMAND_TYPE_FINISH => IAplCommandElement::APL_COMMAND_TYPE_FINISH,
								IAplCommandElement::APL_COMMAND_TYPE_REINFLATE => IAplCommandElement::APL_COMMAND_TYPE_REINFLATE,
								IAplCommandElement::APL_COMMAND_TYPE_BACKSTACK_CLEAR => IAplCommandElement::APL_COMMAND_TYPE_BACKSTACK_CLEAR,
								IAplCommandElement::APL_COMMAND_TYPE_BACK_GO_BACK => IAplCommandElement::APL_COMMAND_TYPE_BACK_GO_BACK,
								IAplCommandElement::APL_COMMAND_TYPE_IDLE => IAplCommandElement::APL_COMMAND_TYPE_IDLE,
								IAplCommandElement::APL_COMMAND_TYPE_OPEN_URL => IAplCommandElement::APL_COMMAND_TYPE_OPEN_URL,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL => IAplCommandElement::APL_COMMAND_TYPE_SCROLL,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_COMPONENT => IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_COMPONENT,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_INDEX => IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_INDEX,
								IAplCommandElement::APL_COMMAND_TYPE_SEND_EVENT => IAplCommandElement::APL_COMMAND_TYPE_SEND_EVENT,
								IAplCommandElement::APL_COMMAND_TYPE_SET_FOCUS => IAplCommandElement::APL_COMMAND_TYPE_SET_FOCUS,
								IAplCommandElement::APL_COMMAND_TYPE_SET_VALUE => IAplCommandElement::APL_COMMAND_TYPE_SET_VALUE,
								IAplCommandElement::APL_COMMAND_TYPE_SPEAK_ITEM => IAplCommandElement::APL_COMMAND_TYPE_SPEAK_ITEM,
								IAplCommandElement::APL_COMMAND_TYPE_SPEAK_LIST => IAplCommandElement::APL_COMMAND_TYPE_SPEAK_LIST,
							]
						],
						'defaultValue' => IAplCommandElement::APL_COMMAND_TYPE_FINISH,
						'name' => 'APL Command Type',
						'description' => 'Type of the command.',
						'valueType' => 'string'
					),
					'command_auto_page_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the "Pager" to page through.',
						'valueType' => 'string'
					),
					'command_auto_page_count' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Count',
						'description' => 'The number of pages to display.',
						'valueType' => 'int'
					),
					'command_auto_page_duration' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Duration',
						'description' => 'The amount of time (in milliseconds) to wait after advancing to the next page.',
						'valueType' => 'int'
					),
					'command_auto_page_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => 1000,
						'name' => 'Delay',
						'description' => 'Displays page 1 for value in ms while waiting to start.',
						'valueType' => 'int'
					),
					'command_back_go_back_use_back_type' => array(
						'editor_type' => 'boolean',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'Back:GoBack'",
						],
						'defaultValue' => false,
						'name' => 'Use APL Back Type',
						'description' => 'If this value is false, back will go to the previous rendered document, otherwise it will navigate to an specified back type property.',
						'valueType' => 'boolean'
					),
					'command_back_go_back_back_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'Back:GoBack' && component.properties.command_back_go_back_use_back_type === true",
							'options' => ['count' => 'Count', 'index' => 'Index', 'id' => 'ID']
						],
						'defaultValue' => 'count',
						'name' => 'Back Type',
						'description' => 'The type of back navigation to use.',
						'valueType' => 'string'
					),
					'command_back_go_back_back_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Back:GoBack' && component.properties.command_back_go_back_use_back_type === true"
						),
						'defaultValue' => 0,
						'name' => 'APL Back Value',
						'description' => 'The value indicating the document to return to in the backstack.',
						'valueType' => 'string'
					),
					'command_idle_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Idle'"
						),
						'defaultValue' => 3000,
						'name' => 'Delay',
						'description' => 'Numeric value of the delay to set in milliseconds.',
						'valueType' => 'int'
					),
					'command_open_url_source' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'OpenURL'"
						),
						'defaultValue' => '',
						'name' => 'Source',
						'description' => 'The URL to open.',
						'valueType' => 'string'
					),
					'command_scroll_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Scroll'"
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component to read.',
						'valueType' => 'string'
					),
					'command_scroll_distance' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Scroll'"
						),
						'defaultValue' => '',
						'name' => 'Distance',
						'description' => 'The number of pages to scroll. Defaults to 1.',
						'valueType' => 'int'
					),
					'command_scroll_to_component_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToComponent'"
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_scroll_to_component_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'ScrollToComponent'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_index' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
						),
						'defaultValue' => 0,
						'name' => 'Index',
						'description' => 'The 0-based index of the child to display.',
						'valueType' => 'int'
					),
					'command_send_event_arguments' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SendEvent'",
						),
						'defaultValue' => [],
						'name' => 'Arguments',
						'description' => 'An array of argument data to send to the skill in the "UserEvent" request.',
						'valueType' => 'array'
					),
					'command_send_event_components' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SendEvent'",
						),
						'defaultValue' => [],
						'name' => 'Components',
						'description' => 'An array of component IDs. The value associated with each identified component is included in the the resulting "UserEvent" request.',
						'valueType' => 'array'
					),
					'command_set_focus_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetFocus'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component which will receive focus.',
						'valueType' => 'string'
					),
					'command_set_value_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'Refers to an component id of current APL Definition.',
						'valueType' => 'string'
					),
					'command_set_value_property' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Property',
						'description' => 'Property to change.',
						'valueType' => 'string'
					),
					'command_set_value_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Value',
						'description' => 'Value to set.',
						'valueType' => 'string'
					),
					'command_speak_item_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakItem'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_speak_item_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakItem'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_speak_item_highlight_mode' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakItem'",
							'options' => ['line' => 'Line', 'block' => 'Block']
						],
						'defaultValue' => 'block',
						'name' => 'Highlight Mode',
						'description' => 'How karaoke is applied: on a line-by-line basis, or to the entire block. Defaults to "block".',
						'valueType' => 'string'
					),
					'command_speak_item_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakItem'",
						),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted.',
						'valueType' => 'int'
					),
					'command_speak_list_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The id of the Sequence or Container (or any other hosting component).',
						'valueType' => 'string'
					),
					'command_speak_list_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakList'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_speak_list_count' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => 1,
						'name' => 'Count',
						'description' => 'The number of children to read.',
						'valueType' => 'int'
					),
					'command_speak_list_start' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => 0,
						'name' => 'Start',
						'description' => 'The index of the item to start reading.',
						'valueType' => 'int'
					),
					'command_speak_list_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted for. Defaults to 0.',
						'valueType' => 'int'
					),
					'command_description' => array(
						'editor_type' => 'desc',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Description',
						'description' => 'Optional documentation for this command.',
						'valueType' => 'string'
					),
					'command_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Delay',
						'description' => 'Delay time in milliseconds before this command runs. Must be non-negative. Defaults to 0.',
						'valueType' => 'int'
					),
					'command_screen_lock' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Screen Lock',
						'description' => 'If true, disable the interaction timer.',
						'valueType' => 'boolean'
					),
					'command_when' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'When',
						'description' => 'Conditional expression. If this evaluates to false, the command is skipped. Defaults to true.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						//'template' => '<div class="code">APL Command <b>{{component.properties.command_type}}</b>.</div>'
						'template' =>'<div class="code">' .
							'<span>APL Command <b>{{component.properties.command_type}}</b></span>' .
							'<hr>' .
							'<ul class="list-unstyled">' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Component ID: <b>{{component.properties.command_auto_page_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Count: <b>{{component.properties.command_auto_page_count}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Duration: <b>{{component.properties.command_auto_page_duration}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Delay: <b>{{component.properties.command_auto_page_delay}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'AutoPage\'">' .
							'<li ng-if="component.properties.command_type === \'Back:GoBack\'">Back Type: <b>{{component.properties.command_back_go_back_back_type}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'Back:GoBack\'">Back Value: <b>{{component.properties.command_back_go_back_back_value}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Back:GoBack\'">' .
							'<li ng-if="component.properties.command_type === \'Idle\'">Delay: <b>{{component.properties.command_idle_delay}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Idle\'">' .
							'<li ng-if="component.properties.command_type === \'OpenURL\'">Source: <b>{{component.properties.command_open_url_source}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'OpenURL\'">' .
							'<li ng-if="component.properties.command_type === \'Scroll\'">Component ID: <b>{{component.properties.command_scroll_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'Scroll\'">Distance: <b>{{component.properties.command_scroll_distance}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Scroll\'">' .
							'<li ng-if="component.properties.command_type === \'ScrollToComponent\'">Component ID: <b>{{component.properties.command_scroll_to_component_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToComponent\'">Align: <b>{{component.properties.command_scroll_to_component_align}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'ScrollToComponent\'">' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Component ID: <b>{{component.properties.command_scroll_to_index_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Align: <b>{{component.properties.command_scroll_to_index_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Index: <b>{{component.properties.command_scroll_to_index_index}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'ScrollToIndex\'">' .
							'<li ng-if="component.properties.command_type === \'SendEvent\'">Arguments: <b>{{component.properties.command_send_event_arguments}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SendEvent\'">Components: <b>{{component.properties.command_send_event_components}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SendEvent\'">' .
							'<li ng-if="component.properties.command_type === \'SetFocus\'">Component ID: <b>{{component.properties.command_set_focus_component_id}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SetFocus\'">' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Component ID: <b>{{component.properties.command_set_value_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Property: <b>{{component.properties.command_set_value_property}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Value: <b>{{component.properties.command_set_value_value}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SetValue\'">' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Component ID: <b>{{component.properties.command_speak_item_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Align: <b>{{component.properties.command_speak_item_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Highlight Mode: <b>{{component.properties.command_speak_item_highlight_mode}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Minimum Dwell Time: <b>{{component.properties.command_speak_item_minimum_dwell_time}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SpeakItem\'">' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Component ID: <b>{{component.properties.command_speak_list_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Align: <b>{{component.properties.command_speak_list_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Start: <b>{{component.properties.command_speak_list_start}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Count: <b>{{component.properties.command_speak_list_count}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Minimum Dwell Time: <b>{{component.properties.command_speak_list_minimum_dwell_time}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SpeakList\'">' .
							' <li>Command Description: <b>{{component.properties.command_description}}</b></li>' .
							' <li>Command Delay: <b>{{component.properties.command_delay}}</b></li>' .
							' <li>Command Screen Lock: <b>{{component.properties.command_screen_lock}}</b></li>' .
							' <li>Command When: <b>{{component.properties.command_when}}</b></li>' .
							'</ul>' .
							'</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-commands-element.html'
					),
					'_descend' => true,
				)
			),
        ];
    }

}
