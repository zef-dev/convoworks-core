<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{
	// common APL commands
	private $_aplCommandType;
	private $_aplCommandDescription = '';
	private $_aplCommandDelay = 0;
	private $_aplCommandScreenLock;
	private $_aplCommandWhen;

	// AutoPage APL command
	private $_aplCommandAutoPageComponentId;
	private $_aplCommandAutoPageCount;
	private $_aplCommandAutoPageDelay;
	private $_aplCommandAutoPageDuration;

	// Back:GoBack APL command
	private $_aplCommandBackstackGoBackUseAplBackType = false;
	private $_aplCommandBackstackGoBackBackType;
	private $_aplCommandBackstackGoBackBackValue;

	// Idle APL command
	private $_aplCommandIdleDelay = 3000;

	// OpenURL APL Command
	private $_aplCommandOpenUrlSource = "";

	// Scroll APL Command
	private $_aplCommandScrollComponentId;
	private $_aplCommandScrollDistance;

	// ScrollToComponent APL Command
	private $_aplCommandScrollToComponentComponentId;
	private $_aplCommandScrollToComponentAlign;

	// ScrollToIndex APL Command
	private $_aplCommandScrollToIndexComponentId;
	private $_aplCommandScrollToIndexAlign;
	private $_aplCommandScrollToIndexIndex;

	// SendEvent APL Command
	private $_aplCommandSendEventArguments = [];
	private $_aplCommandSendEventComponents = [];

	// SetFocus APL Command
	private $_aplCommandSetFocusComponentId;

	// SetValue APL Command
	private $_aplCommandSetValueComponentId;
	private $_aplCommandSetValueProperty;
	private $_aplCommandSetValueValue;

	// SpeakItem APL Command
	private $_aplCommandSpeakItemComponentId;
	private $_aplCommandSpeakItemAlign;
	private $_aplCommandSpeakItemHighlightMode;
	private $_aplCommandSpeakItemMinimumDwellTime;

	// SpeakList APL Command
	private $_aplCommandSpeakListComponentId;
	private $_aplCommandSpeakListAlign;
	private $_aplCommandSpeakListCount;
	private $_aplCommandSpeakListStart;
	private $_aplCommandSpeakListMinimumDwellTime;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		// common APL commands
		$this->_aplCommandType = $properties['command_type'];
		$this->_aplCommandDescription = $properties['command_description'];
		$this->_aplCommandDelay = $properties['command_delay'];
		$this->_aplCommandScreenLock = $properties['command_screen_lock'];
		$this->_aplCommandWhen = $properties['command_when'];

		// AutoPage APL command
		$this->_aplCommandAutoPageComponentId = $properties['command_auto_page_component_id'];
		$this->_aplCommandAutoPageCount = $properties['command_auto_page_count'];
		$this->_aplCommandAutoPageDuration = $properties['command_auto_page_duration'];
		$this->_aplCommandAutoPageDelay = $properties['command_auto_page_delay'];

		// Back:GoBack APL command
		$this->_aplCommandBackstackGoBackUseAplBackType = $properties['command_back_go_back_use_back_type'];
		$this->_aplCommandBackstackGoBackBackType = $properties['command_back_go_back_back_type'];
		$this->_aplCommandBackstackGoBackBackValue = $properties['command_back_go_back_back_value'];

		// Idle APL command
		$this->_aplCommandIdleDelay = $properties['command_idle_delay'];

		// OpenURL APL Command
		$this->_aplCommandOpenUrlSource = $properties['command_open_url_source'];

		// Scroll APL Command
		$this->_aplCommandScrollComponentId = $properties['command_scroll_component_id'];
		$this->_aplCommandScrollDistance = $properties['command_scroll_distance'];

		// ScrollToComponent APL Command
		$this->_aplCommandScrollToComponentComponentId = $properties['command_scroll_to_component_component_id'];
		$this->_aplCommandScrollToComponentAlign =$properties['command_scroll_to_component_align'];

		// ScrollToIndex APL Command
		$this->_aplCommandScrollToIndexComponentId = $properties['command_scroll_to_index_component_id'];
		$this->_aplCommandScrollToIndexAlign = $properties['command_scroll_to_index_align'];
		$this->_aplCommandScrollToIndexIndex = $properties['command_scroll_to_index_index'];

		// SendEvent APL Command
		$this->_aplCommandSendEventArguments = $properties['command_send_event_arguments'];
		$this->_aplCommandSendEventComponents = $properties['command_send_event_components'];

		// SetFocus APL Command
		$this->_aplCommandSetFocusComponentId = $properties['command_set_focus_component_id'];

		// SetValue APL Command
		$this->_aplCommandSetValueComponentId = $properties['command_set_value_component_id'];
		$this->_aplCommandSetValueProperty = $properties['command_set_value_property'];
		$this->_aplCommandSetValueValue = $properties['command_set_value_value'];

		// SpeakItem APL Command
		$this->_aplCommandSpeakItemComponentId = $properties['command_speak_item_component_id'];
		$this->_aplCommandSpeakItemAlign = $properties['command_speak_item_align'];
		$this->_aplCommandSpeakItemHighlightMode = $properties['command_speak_item_highlight_mode'];
		$this->_aplCommandSpeakItemMinimumDwellTime = $properties['command_speak_item_minimum_dwell_time'];

		// SpeakList APL Command
		$this->_aplCommandSpeakListComponentId = $properties['command_speak_list_component_id'];
		$this->_aplCommandSpeakListAlign = $properties['command_speak_list_align'];
		$this->_aplCommandSpeakListCount = $properties['command_speak_list_count'];
		$this->_aplCommandSpeakListStart = $properties['command_speak_list_start'];
		$this->_aplCommandSpeakListMinimumDwellTime = $properties['command_speak_list_minimum_dwell_time'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandType = $this->evaluateString($this->_aplCommandType);

		if (empty($aplCommandType)) {
			throw new InvalidComponentDataException('APL Command Type must not be empty.');
		}

		switch ($aplCommandType) {
			// simple apl commands
			case self::APL_COMMAND_TYPE_CLEAR_FOCUS:
			case self::APL_COMMAND_TYPE_FINISH:
			case self::APL_COMMAND_TYPE_REINFLATE:
			case self::APL_COMMAND_TYPE_BACKSTACK_CLEAR:
				$this->_addSimpleCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_BACK_GO_BACK:
				$this->_addBackGoBackCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_AUTO_PAGE:
				$this->_addAutoPageCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_IDLE:
				$this->_addIdleCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_OPEN_URL:
				$this->_addOpenUrlCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SCROLL:
				$this->_addScrollCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SCROLL_TO_COMPONENT:
				$this->_addScrollToComponentCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SCROLL_TO_INDEX:
				$this->_addScrollToIndexCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SEND_EVENT:
				$this->_addSendEventCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SET_FOCUS:
				$this->_addSetFocusCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SET_VALUE:
				$this->_addSetValueCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SPEAK_ITEM:
				$this->_addSpeakItemCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			case self::APL_COMMAND_TYPE_SPEAK_LIST:
				$this->_addSpeakListCommandToAplCommandsDirective($request, $response, $aplCommandType);
				break;
			default:
				throw new InvalidComponentDataException($aplCommandType . ' is not supported.');
		}
	}

	/**
	 * Prepares APL Commands without properties.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addSimpleCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the AutoPage APL Command
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addAutoPageCommandToAplCommandsDirective($request, $response, $aplCommandType)
	{
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandAutoPageComponentId = $this->evaluateString($this->_aplCommandAutoPageComponentId);
		$aplCommandAutoPageCount = $this->evaluateString($this->_aplCommandAutoPageCount);
		$aplCommandAutoPageDuration = $this->evaluateString($this->_aplCommandAutoPageDuration);
		$aplCommandAutoPageDelay = $this->evaluateString($this->_aplCommandAutoPageDelay);

		if (!is_numeric($aplCommandAutoPageDelay)) {
			throw new InvalidComponentDataException('The provided duration is not valid');
		}

		if (empty($this->evaluateString($this->_aplCommandDelay))) {
			$command['delay'] = intval($aplCommandAutoPageDelay);
		}

		if (!empty($aplCommandAutoPageComponentId)) {
			$command['componentId'] = $aplCommandAutoPageComponentId;
		}

		if (is_numeric($aplCommandAutoPageCount)) {
			$command['count'] = intval($aplCommandAutoPageCount);
		}

		if (is_numeric($aplCommandAutoPageDuration)) {
			$command['duration'] = intval($aplCommandAutoPageDuration);
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the Back:GoBack APL Command
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addBackGoBackCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandBackstackGoBackUseAplBackType = $this->evaluateString($this->_aplCommandBackstackGoBackUseAplBackType);
		$aplCommandBackstackGoBackBackType = $this->evaluateString($this->_aplCommandBackstackGoBackBackType);
		$aplCommandBackstackGoBackBackValue = $this->evaluateString($this->_aplCommandBackstackGoBackBackValue);

		if (is_bool($aplCommandBackstackGoBackUseAplBackType) && $aplCommandBackstackGoBackUseAplBackType) {
			$command['backType'] = $aplCommandBackstackGoBackBackType;
			$command['backValue'] = $aplCommandBackstackGoBackBackType === 'count' || $aplCommandBackstackGoBackBackType === 'index'
				? intval($aplCommandBackstackGoBackBackValue) : $aplCommandBackstackGoBackBackValue;
		}

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the Idle APL Command
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addIdleCommandToAplCommandsDirective($request, $response, $aplCommandType)
	{
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandIdleDelay = $this->evaluateString($this->_aplCommandIdleDelay);

		if (!is_numeric($aplCommandIdleDelay)) {
			throw new InvalidComponentDataException('The provided delay is not valid');
		}

		if (empty($this->evaluateString($this->_aplCommandDelay))) {
			$command['delay'] = intval($aplCommandIdleDelay);
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the OpenURL APL Command
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addOpenUrlCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandOpenUrlSource = $this->evaluateString($this->_aplCommandOpenUrlSource);

		if (empty($aplCommandOpenUrlSource)) {
			throw new InvalidComponentDataException("Invalid URL provided");
		}

		$command['source'] = $aplCommandOpenUrlSource;

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the Scroll APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addScrollCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandScrollComponentId = $this->evaluateString($this->_aplCommandScrollComponentId);
		$aplCommandScrollDistance = $this->evaluateString($this->_aplCommandScrollDistance);

		if (!empty($aplCommandScrollComponentId)) {
			$command['componentId'] = $aplCommandScrollComponentId;
		}

		if (is_numeric($aplCommandScrollDistance)) {
			$command['distance'] = intval($aplCommandScrollDistance);
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the ScrollToComponent APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addScrollToComponentCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandScrollToComponentComponentId = $this->evaluateString($this->_aplCommandScrollToComponentComponentId);
		$aplCommandScrollToComponentAlign = $this->evaluateString($this->_aplCommandScrollToComponentAlign);

		if (!empty($aplCommandScrollToComponentComponentId)) {
			$command['componentId'] = $aplCommandScrollToComponentComponentId;
		}

		if (!empty($aplCommandScrollToComponentAlign)) {
			$command['align'] = $aplCommandScrollToComponentAlign;
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the ScrollToIndex APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addScrollToIndexCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandScrollToIndexComponentId = $this->evaluateString($this->_aplCommandScrollToIndexComponentId);
		$aplCommandScrollToIndexAlign = $this->evaluateString($this->_aplCommandScrollToIndexAlign);
		$aplCommandScrollToIndexIndex = $this->evaluateString($this->_aplCommandScrollToIndexIndex);

		if (!is_numeric($aplCommandScrollToIndexIndex)) {
			throw new InvalidComponentDataException('The provided index is not valid.');
		}

		if (!empty($aplCommandScrollToIndexComponentId)) {
			$command['componentId'] = $aplCommandScrollToIndexComponentId;
		}

		if (!empty($aplCommandScrollToComponentAlign)) {
			$command['align'] = $aplCommandScrollToComponentAlign;
		}

		if (is_numeric($aplCommandScrollToIndexIndex)) {
			$command['index'] = intval($aplCommandScrollToIndexIndex);
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the SendEvent APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addSendEventCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandSendEventArguments = $this->evaluateString($this->_aplCommandSendEventArguments);
		$aplCommandSendEventComponents = $this->evaluateString($this->_aplCommandSendEventComponents);

		if (!is_array($aplCommandSendEventArguments) || !is_array($aplCommandSendEventComponents)) {
			throw new InvalidComponentDataException("APL Command Arguments or APL Command Components must be an array.");
		}

		if (!empty($aplCommandSendEventArguments)) {
			$command['arguments'] = $aplCommandSendEventArguments;
		}

		if (!empty($aplCommandSendEventComponents)) {
			$command['components'] = $aplCommandSendEventComponents;
		}

		if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the SetFocus APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addSetFocusCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandSetFocusComponentId = $this->evaluateString($this->_aplCommandSetFocusComponentId);

		if (!empty($aplCommandSetFocusComponentId)) {
			$command['componentId'] = $aplCommandSetFocusComponentId;
		}

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * * Prepares the SetValue APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addSetValueCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandSetValueComponentId = $this->evaluateString($this->_aplCommandSetValueComponentId);
		$aplCommandSetValueProperty = $this->evaluateString($this->_aplCommandSetValueProperty);
		$aplCommandSetValueValue = $this->evaluateString($this->_aplCommandSetValueValue);

		if (empty($aplCommandSetValueProperty) || empty($aplCommandSetValueValue)) {
			throw new InvalidComponentDataException('You have to provide an property and a value to the SetValue Command');
		}

		if (!empty($aplCommandSetValueComponentId)) {
			$command['componentId'] = $aplCommandSetValueComponentId;
		}

		$command['property'] = $aplCommandSetValueProperty;
		$command['value'] = $aplCommandSetValueValue;

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the SpeakItem APL Command.
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 */
	private function _addSpeakItemCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandSpeakItemComponentId = $this->evaluateString($this->_aplCommandSpeakItemComponentId);
		$aplCommandSpeakItemAlign = $this->evaluateString($this->_aplCommandSpeakItemAlign);
		$aplCommandSpeakItemHighlightMode = $this->evaluateString($this->_aplCommandSpeakItemHighlightMode);
		$aplCommandSpeakItemMinimumDwellTime = $this->evaluateString($this->_aplCommandSpeakItemMinimumDwellTime);

		if (!empty($aplCommandSpeakItemComponentId)) {
			$command['componentId'] = $aplCommandSpeakItemComponentId;
		}

		if (!empty($aplCommandSpeakItemAlign)) {
			$command['highlightMode'] = $aplCommandSpeakItemAlign;
		}

		if (!empty($aplCommandSpeakItemHighlightMode)) {
			$command['align'] = $aplCommandSpeakItemHighlightMode;
		}

		if (is_numeric($aplCommandSpeakItemMinimumDwellTime)) {
			$command['minimumDwellTime'] = intval($aplCommandSpeakItemMinimumDwellTime);
		}

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the SpeakList APL Command
	 * @param $request
	 * @param $response
	 * @param $aplCommandType
	 * @throws InvalidComponentDataException
	 */
	private function _addSpeakListCommandToAplCommandsDirective($request, $response, $aplCommandType) {
		$command = $this->_prepareBaseAplCommand($aplCommandType);

		$aplCommandSpeakListComponentId = $this->evaluateString($this->_aplCommandSpeakListComponentId);
		$aplCommandSpeakListAlign = $this->evaluateString($this->_aplCommandSpeakListAlign);
		$aplCommandSpeakListStart = $this->evaluateString($this->_aplCommandSpeakListStart);
		$aplCommandSpeakListCount = $this->evaluateString($this->_aplCommandSpeakListCount);
		$aplCommandSpeakListMinimumDwellTime = $this->evaluateString($this->_aplCommandSpeakListMinimumDwellTime);

		if (!is_numeric($aplCommandSpeakListStart) || !is_numeric($aplCommandSpeakListCount)) {
			throw new InvalidComponentDataException('The provided count or start is not valid.');
		}

		$command['start'] = intval($aplCommandSpeakListStart);
		$command['count'] = intval($aplCommandSpeakListCount);

		if (!empty($aplCommandSpeakListComponentId)) {
			$command['componentId'] = $aplCommandSpeakListComponentId;
		}

		if (!empty($aplCommandSpeakListAlign)) {
			$command['align'] = $aplCommandSpeakListAlign;
		}

		if (is_numeric($aplCommandSpeakListMinimumDwellTime)) {
			$command['minimumDwellTime'] = intval($aplCommandSpeakListMinimumDwellTime);
		}

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}

	/**
	 * Prepares the base of an APL Command.
	 * @param $aplCommandType
	 * @return array
	 */
	private function _prepareBaseAplCommand($aplCommandType) {
		$aplCommandDescription = $this->evaluateString($this->_aplCommandDescription);
		$aplCommandDelay = $this->evaluateString($this->_aplCommandDelay);
		$aplCommandScreenLock = $this->evaluateString($this->_aplCommandScreenLock);
		$aplCommandWhen = $this->evaluateString($this->_aplCommandWhen);

		$command = [
			'type' => $aplCommandType,
		];

		if (!empty($aplCommandDescription)) {
			$command['description'] = $aplCommandDescription;
		}

		if (is_numeric($aplCommandDelay)) {
			$command['delay'] = intval($aplCommandDelay);
		}

		$aplCommandScreenLockValue = !($aplCommandScreenLock !== '') || boolval($aplCommandScreenLock);
		if ($aplCommandScreenLockValue) {
			$command['screenLock'] = $aplCommandScreenLockValue;
		}

		$aplCommandWhenValue = !($aplCommandWhen !== '') || boolval($aplCommandWhen);
		if (!$aplCommandWhenValue) {
			$command['when'] = $aplCommandWhenValue;
		}

		return $command;
	}
}