<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\Workflow\IConversationProcessor;
use Convo\Core\Workflow\IConversationElement;

class VoicePinConfirmationBlock extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRunnableBlock
{
	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onAchieved        =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onNotAchieved        =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onNotEnabled    =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_fallback    =   [];

	private $_blockId;

	private $_blockName;

	private $_voicePinConfirmationVar;

	public function __construct($properties, \Convo\Core\ConvoServiceInstance $service)
	{
		parent::__construct($properties);
		$this->setService($service);

		$this->_blockId		    		=	$properties['block_id'];
		$this->_blockName       		=   $properties['name'] ?? 'Nameless Voice Pin Confirmation Block';
		$this->_voicePinConfirmationVar	=	$properties['voice_pin_confirmation_var'] ?? 'voice_pin_confirmation_status';

        if (isset($properties['on_achieved'])) {
			foreach ($properties['on_achieved'] as $element) {
				$this->_onAchieved[] = $element;
				$this->addChild($element);
			}
		}

        if (isset($properties['on_not_achieved'])) {
			foreach ($properties['on_not_achieved'] as $element) {
				$this->_onNotAchieved[] = $element;
				$this->addChild($element);
			}
		}

        if (isset($properties['on_not_enabled'])) {
			foreach ($properties['on_not_enabled'] as $element) {
				$this->_onNotEnabled[] = $element;
				$this->addChild($element);
			}
		}

        if (isset($properties['fallback'])) {
			foreach ($properties['fallback'] as $element) {
				$this->_fallback[] = $element;
				$this->addChild($element);
			}
		}
	}

	/**
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 * @return mixed
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
	}

	/**
	 * @return string
	 */
	public function getComponentId()
	{
		return $this->_blockId;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRunnableBlock::run()
	 */
	public function run(IConvoRequest $request, IConvoResponse $response)
	{
		$voicePinConfirmationVar = $this->evaluateString($this->_voicePinConfirmationVar);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            $req_params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $req_params->setServiceParam($voicePinConfirmationVar, [
                'token' => $request->getVoicePinConfirmationToken(),
                'status' => $request->getVoicePinConfirmationStatus(),
                'result' => $request->getVoicePinConfirmationResult(),
                'personId' => $request->getPersonId(),
                'personAuthenticationConfidenceLevel' => $request->getPersonAuthenticationConfidenceLevel()
            ]);

            if ($request->getVoicePinConfirmationStatus()['code'] != 200) {
                foreach ( $this->_fallback as $element) {
                    $element->read( $request, $response);
                }
                return;
            }

            $voicePinConfirmationResult = $request->getVoicePinConfirmationResult()['status'] ?? '';
            $flowToExecute = [];

            switch ($voicePinConfirmationResult) {
                case 'ACHIEVED':
                    $flowToExecute = $this->_onAchieved;
                    break;
                case 'NOT_ACHIEVED':
                    $flowToExecute = $this->_onNotAchieved;
                    break;
                case 'NOT_ENABLED':
                    $flowToExecute = $this->_onNotEnabled;
                    break;
                default:
                    $flowToExecute = $this->_fallback;
                    break;
            }

            foreach ($flowToExecute as $element) {
                $element->read( $request, $response);
            }
		}
	}

	/**
	 * @return IConversationProcessor[]
	 */
	public function getProcessors()
	{
		return [];
	}

	/**
	 * @return IConversationElement[]
	 */
	public function getElements()
	{
		return [];
	}

	/**
	 * @return string
	 */
	public function getRole()
	{
		return IRunnableBlock::ROLE_VOICE_PIN_CONFIRMATION_BLOCK;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_blockName;
	}

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IServiceWorkflowComponent::getBlockParams()
     */
    public function getBlockParams( $scopeType)
    {
        // Is it top level block?
        if ( $this->getParent() === $this->getService()) {
            return $this->getService()->getComponentParams( $scopeType, $this);
        }

        return parent::getBlockParams( $scopeType);
    }

	/**
	 * @return PreviewBlock
	 */
	public function getPreview()
	{
		$previewBlock = new PreviewBlock($this->getName(), $this->getComponentId());
		$previewBlock->setLogger($this->_logger);

        $section = new PreviewSection('On PIN Correct', $this->_logger);
        $section->collect( $this->_onAchieved, '\Convo\Core\Preview\IBotSpeechResource');
        $previewBlock->addSection($section);

        $section = new PreviewSection('On PIN Not Correct', $this->_logger);
        $section->collect( $this->_onNotAchieved, '\Convo\Core\Preview\IBotSpeechResource');
        $previewBlock->addSection($section);

        $section = new PreviewSection('On PIN Not Provided', $this->_logger);
        $section->collect( $this->_onNotEnabled, '\Convo\Core\Preview\IBotSpeechResource');
        $previewBlock->addSection($section);

		$section = new PreviewSection('Fallback', $this->_logger);
		$section->collect( $this->_fallback, '\Convo\Core\Preview\IBotSpeechResource');
		$previewBlock->addSection($section);

		return $previewBlock;
	}
}
