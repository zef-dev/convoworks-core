<?php
declare(strict_types=1);

namespace Convo\Pckg\Alexa\Filters;


use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\Intent\IntentModel;
use Convo\Core\Workflow\IIntentAwareRequest;
use Convo\Pckg\Core\Filters\PlatformIntentReader;

class AplUserEventReader extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Intent\IIntentAdapter
{
	private const INTENT = 'Alexa.Presentation.APL.UserEvent';

	private $_id;
	private $_useAplUserEventArgumentPart;
	private $_aplUserEventArgumentPart;

	public function __construct( $config)
	{
		parent::__construct( $config);
		$this->_id     				= $config['_component_id'] ?? ''; // todo generate default id
		$this->_useAplUserEventArgumentPart  = $config['use_apl_user_event_argument_part'] ?? '';
		$this->_aplUserEventArgumentPart     = $config['apl_user_event_argument_part'] ?? '';
	}

	public function getId()
	{
		return $this->_id;
	}

	public function getPlatformIntentName( $platformId)
	{
		$intent = '';
		if ($platformId === AmazonCommandRequest::PLATFORM_ID) {
			$intent = self::INTENT;
		}
		return $intent;
	}

    public function accepts(IIntentAwareRequest $request)
    {
        return $request->getIntentName() === $this->getPlatformIntentName($request->getIntentPlatformId());
    }

	public function read( \Convo\Core\Workflow\IIntentAwareRequest $request)
	{
		/**
		 * @var $request AmazonCommandRequest
		 */
		$result = new \Convo\Core\Workflow\DefaultFilterResult();
		if ($request->getPlatformId() === AmazonCommandRequest::PLATFORM_ID) {
			if (!$this->_useAplUserEventArgumentPart) {
				if ($request->isAplUserEvent()) {
					$result->setSlotValue('intentName', self::INTENT);
					$result->setSlotValue('aplArguments', $request->getAplArguments());
				}
			} else {
				$aplUserEventKey = $this->evaluateString($this->_aplUserEventArgumentPart);
				if ($request->isAplUserEvent() && $this->_isAplArgumentPresent($request->getAplArguments(), $aplUserEventKey)) {
					$result->setSlotValue('intentName', self::INTENT);
					$result->setSlotValue('aplArguments', $request->getAplArguments());
				}
			}
		}

		return $result;
	}

	private function _isAplArgumentPresent($aplArguments, $aplArgumentName) {
		$isAplKeyPresent = false;
		if (strpos(json_encode($aplArguments), $aplArgumentName) !== false) {
			$isAplKeyPresent = true;
		}
		return $isAplKeyPresent;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_id.']';
	}
}
