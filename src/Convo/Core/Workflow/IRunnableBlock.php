<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

use Convo\Core\Preview\PreviewBlock;

/**
 * Special kind if workflow components which can be run.
 *
 * This enables the implementing component to decide what own internal chain will it execute (read or process) like we have in conversation blocks.
 * @author Tole
 *
 */
interface IRunnableBlock extends IConversationElement, IIdentifiableComponent, IWorkflowContainerComponent
{
	const ROLE_CONVERSATION_BLOCK  =   'conversation_block';
	const ROLE_MEDIA_PLAYER        =   'media_player';
	const ROLE_RADIO_STREAM        =   'radio_stream';
	const ROLE_SALES_BLOCK   	   =   'sales_block';
	const ROLE_VOICE_PIN_CONFIRMATION_BLOCK = 'voice_pin_confirmation_block';
	const ROLE_SESSION_START       =   'session_start';
	const ROLE_SESSION_ENDED       =   'session_ended';
	const ROLE_SERVICE_PROCESSORS  =   'service_processors';
    const ROLE_DEFAULT_FALLBACK    =   'default_fallback';
    const ROLE_ERROR_HANDLER       =   'error_handler';

	/**
     * Executes internal flow with given request and response objects.
     * @param \Convo\Core\Workflow\IConvoRequest $request
     * @param \Convo\Core\Workflow\IConvoResponse $response
     * @throws \Convo\Core\StateChangedException
     */
    public function run( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response);

    /**
     * Returns child processors
     * @return \Convo\Core\Workflow\IConversationProcessor[]
     */
    public function getProcessors();

    /**
     * Returns child elements
     * @return \Convo\Core\Workflow\IConversationElement[]
     */
    public function getElements();

    /**
     * Returns one of the roles defined above which is assigned to the block.
     * @return string
     */
    public function getRole();

    /**
     * Get the user specified name for the block
     * @return string
     */
    public function getName();

    /**
     * Get preview object for this block
     * @return PreviewBlock
     */
    public function getPreview();
}
