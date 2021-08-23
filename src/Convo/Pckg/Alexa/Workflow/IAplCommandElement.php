<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Workflow;

/**
 * Elements are used to be executed (read) in read phase.
 * 
 * @author Tole
 *
 */
interface IAplCommandElement extends \Convo\Core\Workflow\IServiceWorkflowComponent
{
	public const APL_COMMAND_TYPE_AUTO_PAGE = 'AutoPage';
	public const APL_COMMAND_TYPE_CLEAR_FOCUS = 'ClearFocus';
	public const APL_COMMAND_TYPE_FINISH = 'Finish';
	public const APL_COMMAND_TYPE_REINFLATE = 'Reinflate';
	public const APL_COMMAND_TYPE_BACKSTACK_CLEAR = 'Backstack:Clear';
	public const APL_COMMAND_TYPE_BACK_GO_BACK = 'Back:GoBack';
	public const APL_COMMAND_TYPE_IDLE = 'Idle';
	public const APL_COMMAND_TYPE_OPEN_URL = 'OpenURL';
	public const APL_COMMAND_TYPE_SCROLL = 'Scroll';
	public const APL_COMMAND_TYPE_SCROLL_TO_COMPONENT = 'ScrollToComponent';
	public const APL_COMMAND_TYPE_SCROLL_TO_INDEX = 'ScrollToIndex';
	public const APL_COMMAND_TYPE_SEND_EVENT = 'SendEvent';
	public const APL_COMMAND_TYPE_SET_FOCUS = 'SetFocus';
	public const APL_COMMAND_TYPE_SET_VALUE = 'SetValue';
	public const APL_COMMAND_TYPE_SPEAK_ITEM = 'SpeakItem';
	public const APL_COMMAND_TYPE_SPEAK_LIST = 'SpeakList';


	/**
	 * Executes internal logic (could be just delegating to child elements) in read phase.
	 * @param \Convo\Core\Workflow\IConvoRequest $request
	 * @param \Convo\Core\Workflow\IConvoResponse $response
	 * @throws \Convo\Core\StateChangedException
	 */
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response);
	
}