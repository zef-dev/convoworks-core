<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRunnableBlock;

class SalesBlock extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRunnableBlock
{
	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onBuy        =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onUpsell    =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onRefundCancel    =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_fallback      =   [];

	private $_blockId;

	private $_blockName;

	private $_salesOperationStatus;

	public function __construct($properties, \Convo\Core\ConvoServiceInstance $service)
	{
		parent::__construct($properties);
		$this->setService($service);

		$this->_blockId		    		=	$properties['block_id'];
		$this->_blockName       		=   $properties['name'] ?? 'Nameless sales block';
		$this->_salesOperationStatus	=	$properties['sales_status_var'] ?? 'sales_status';

		if (isset($properties['no_buy'])) {
			foreach ($properties['no_buy'] as $element) {
				$this->_onBuy[]        =   $element;
				$this->addChild( $element);
			}
		}

		if (isset($properties['no_upsell'])) {
			foreach ( $properties['no_upsell'] as $element) {
				$this->_onUpsell[]        =   $element;
				$this->addChild( $element);
			}
		}

		if ( isset( $properties['no_refund_cancel'])) {
			foreach ( $properties['no_refund_cancel'] as $element) {
				$this->_onRefundCancel[]    =   $element;
				$this->addChild( $element);
			}
		}

		if ( isset( $properties['fallback'])) {
			foreach ( $properties['fallback'] as $fallback) {
				$this->addFallback( $fallback);
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
		$salesOperationStatus = $this->evaluateString( $this->_salesOperationStatus);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			$amazonRequestData = $request->getPlatformData();

			switch ($request->getIntentName()) {
				case 'Buy':
					$req_params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
					$req_params->setServiceParam( $salesOperationStatus, $amazonRequestData['request']);
					foreach ( $this->_onBuy as $element) {
						$element->read( $request, $response);
					}
					break;
				case 'Upsell':
					$req_params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
					$req_params->setServiceParam( $salesOperationStatus, $amazonRequestData['request']);
					foreach ( $this->_onUpsell as $element) {
						$element->read( $request, $response);
					}
					break;
				case 'Cancel':
					$req_params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
					$req_params->setServiceParam( $salesOperationStatus, $amazonRequestData['request']);
					foreach ( $this->_onRefundCancel as $element) {
						$element->read( $request, $response);
					}
					break;
				default:
					foreach ( $this->_fallback as $element) {
						$element->read( $request, $response);
					}
					break;
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
		return IRunnableBlock::ROLE_SALES_BLOCK;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_blockName;
	}

	/**
	 * @return PreviewBlock
	 */
	public function getPreview()
	{
		$pblock = new PreviewBlock($this->getName(), $this->getComponentId());
		$pblock->setLogger($this->_logger);

		$section = new PreviewSection('On Buy', $this->_logger);
		$section->collect( $this->_onBuy, '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

		$section = new PreviewSection('On Upsell', $this->_logger);
		$section->collect( $this->_onUpsell, '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

		$section = new PreviewSection('On Refund or Cancel', $this->_logger);
		$section->collect( $this->_onRefundCancel, '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

		// Fallback text
		$section = new PreviewSection('Fallback', $this->_logger);
		$section->collect($this->_fallback, '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);


		return $pblock;
	}

	public function addFallback(\Convo\Core\Workflow\IConversationElement $element)
	{
		$this->_fallback[] = $element;
		$this->addChild($element);
	}
}