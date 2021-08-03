<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\ComponentDefinition;
use Convo\Core\Factory\IComponentFactory;

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
				'\Convo\Pckg\Alexa\Elements\AplAutoPageCommandElement',
				'APL Auto Page Command',
				'The AutoPage command automatically progresses through a series of pages displayed in a Pager component. The AutoPage command finishes after the last page has been displayed for the requested time period.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the "Pager" to page through.',
						'valueType' => 'string'
					),
					'command_count' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Count',
						'description' => 'The number of pages to display.',
						'valueType' => 'int'
					),
					'command_duration' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Duration',
						'description' => 'The amount of time (in milliseconds) to wait after advancing to the next page.',
						'valueType' => 'int'
					),
					'command_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 1000,
						'name' => 'Delay',
						'description' => 'Displays page 1 for value in ms while waiting to start.',
						'valueType' => 'int'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-auto-page-commands-element.html'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">The first page of Component with ID <b>{{component.properties.command_component_id}}</b> will be displayed for <b>{{component.properties.command_delay}} milliseconds, changes to other pages will be in {{component.properties.command_duration}} milliseconds</b>.</div>'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSetValueCommandElement',
				'APL Set Value Command',
				'Provides the SetValue APL Command.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'Refers to an component id of current APL Definition.',
						'valueType' => 'string'
					),
					'command_property' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Property',
						'description' => 'Property to change.',
						'valueType' => 'string'
					),
					'command_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Value',
						'description' => 'Value to set.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Set Property <b>{{component.properties.command_property}}</b> to value <b>{{component.properties.command_value}}</b> of APL Document Token <b>{{ component.properties.command_component_id}}</b></div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplIdleCommandElement',
				'APL Idle Command',
				'The Idle command does nothing. Use as a placeholder or to insert a calculated delay in a longer series of commands.',
				array(
					'command_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 3000,
						'name' => 'Delay',
						'description' => 'Numeric value of the delay to set in milliseconds.',
						'valueType' => 'int'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Delay will last for <b>{{component.properties.command_delay}}</b> milliseconds.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSetValueCommandElement',
				'APL Set Value Command',
				'Provides the SetValue APL Command.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Template Component ID',
						'description' => 'Refers to an component id of current APL Definition.',
						'valueType' => 'string'
					),
					'command_property' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Property',
						'description' => 'Property to change.',
						'valueType' => 'string'
					),
					'command_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Value',
						'description' => 'Value to set.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Set Property <b>{{component.properties.command_property}}</b> to value <b>{{component.properties.command_value}}</b> of APL Document Token <b>{{ component.properties.command_component_id}}</b></div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSendEventCommandElement',
				'APL Send Event Command',
				'Provides the SendEvent APL Command.',
				array(
					'command_arguments' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => [],
						'name' => 'Arguments',
						'description' => 'An array of argument data to send to the skill in the "UserEvent" request.',
						'valueType' => 'array'
					),
					'command_components' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => [],
						'name' => 'Components',
						'description' => 'An array of component IDs. The value associated with each identified component is included in the the resulting "UserEvent" request.',
						'valueType' => 'array'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'<ul class="list-unstyled">' .
							' <li>Arguments: <b>{{component.properties.command_arguments ? component.properties.command_arguments : "N/A"}}</b></li>' .
							' <li>Component IDs: <b>{{component.properties.command_components ? component.properties.command_components : "N/A"}}</b></li>' .
							'</ul>' .
							'</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-send-event-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSetFocusCommandElement',
				'APL Set Focus Command',
				'Changes the actionable component that is in focus.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component which will receive focus.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Set Focus to Component ID <b>{{component.properties.command_component_id}}</b>.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplClearFocusCommandElement',
				'APL Clear Focus Command',
				'Removes focus from the actionable component that is currently in focus.',
				array(
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">APL Clear Focus.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplFinishCommandElement',
				'APL Finish Command',
				'Close the current APL document and exit.',
				array(
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">APL Command Finish.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplBackstackGoBackCommandElement',
				'APL Backstack Go Back Command',
				'Navigates trough the Backstack.',
				array(
					'_workflow' => 'read',
					'use_apl_back_type' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Use APL Back Type ID',
						'description' => 'If this value is false, back will go to the previous rendered document, otherwise it will navigate to an specified ID.',
						'valueType' => 'boolean'
					),
					'apl_back_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.use_apl_back_type === true",
							'options' => ['count' => 'Count', 'index' => 'Index', 'id' => 'ID']
						],
						'defaultValue' => 'count',
						'name' => 'Back Type',
						'description' => 'The type of back navigation to use.',
						'valueType' => 'string'
					),
					'apl_back_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.use_apl_back_type === true"
						),
						'defaultValue' => 0,
						'name' => 'Use APL Back ID',
						'description' => 'The value indicating the document to return to in the backstack.',
						'valueType' => 'string'
					),
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">{{component.properties.use_apl_back_type ? "Use backstack type " + component.properties.apl_back_type + " with backstack value " + component.properties.apl_back_value : "APL Command Backstack Go Back."}}</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplBackstackClearCommandElement',
				'APL Backstack Clear Command',
				'Clears the Backstack.',
				array(
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">APL Command Backstack Clear.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplReinflateCommandElement',
				'APL Reinflate Command',
				'Reinflates the current document with updated configuration properties.',
				array(
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">APL Command Reinflate.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplScrollCommandElement',
				'APL Scroll Command',
				'The Scroll command scrolls a ScrollView or Sequence forward or backward by a set number of pages.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component to read.',
						'valueType' => 'string'
					),
					'command_distance' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Distance',
						'description' => 'The number of pages to scroll. Defaults to 1.',
						'valueType' => 'int'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">The Component with ID <b>{{component.properties.command_component_id}}</b> will scroll <b>{{component.properties.command_distance}} pages</b>.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplScrollToComponentCommandElement',
				'APL Scroll To Component Command',
				'Scroll forward or backward through a ScrollView or Sequence to ensure that a particular component is in view.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Align <b>{{component.properties.command_align}}</b> to Component with ID <b>{{component.properties.command_component_id}}</b>.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplScrollToIndexCommandElement',
				'APL Scroll To Index Command',
				'Scroll forward or backward through a ScrollView or Sequence to ensure that a particular child component is in view.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'command_index' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 0,
						'name' => 'Index',
						'description' => 'The 0-based index of the child to display.',
						'valueType' => 'int'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Scroll to <b>{{component.properties.command_component_index}}</b> in Component with ID <b>{{component.properties.command_component_id}} with alignment {{component.properties.command_align}}</b>.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSpeakItemCommandElement',
				'APL Speak Item Command',
				'The SpeakItem command reads the contents of a single component on the screen. The component scrolls or pages into view if it is not already visible.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_highlight_mode' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['line' => 'Line', 'block' => 'Block']
						],
						'defaultValue' => 'block',
						'name' => 'Highlight Mode',
						'description' => 'How karaoke is applied: on a line-by-line basis, or to the entire block. Defaults to "block".',
						'valueType' => 'string'
					),
					'command_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted.',
						'valueType' => 'int'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Speak item will display in highlight mode <b>{{component.properties.command_highlight_mode}}</b>, align to <b>{{component.properties.command_align}}</b> in Component with ID <b>{{component.properties.command_component_id}}</b> and dwell time <b>{{component.properties.command_minimum_dwell_time}}</b>, .</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplSpeakListCommandElement',
				'APL Speak List Command',
				'Read the contents of a range of items inside a common container. Each item will scroll into view before speech. Each item should have a speech property, but it is not required.',
				array(
					'command_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The id of the Sequence or Container (or any other hosting component).',
						'valueType' => 'string'
					),
					'command_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_count' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 1,
						'name' => 'Count',
						'description' => 'The number of children to read.',
						'valueType' => 'int'
					),
					'command_start' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 0,
						'name' => 'Start',
						'description' => 'The index of the item to start reading.',
						'valueType' => 'int'
					),
					'command_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted for. Defaults to 0.',
						'valueType' => 'int'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Speak list will start from <b>{{component.properties.command_start}}</b> and go trough <b>{{component.properties.command_count}}</b> items, alignment will be <b>{{component.properties.command_align}}</b> with minimum dwell time of <b>{{component.properties.command_minimum_dwell_time}}</b>milliseconds.</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-backstack-clear-command.html'
					),
					'_descend' => true,
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
        ];
    }

}
