<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\StateChangedException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class SpecialRoleProcessorBlock extends \Convo\Pckg\Core\Elements\ElementCollection implements IRunnableBlock
{

	private $_blockId;

	/**
	 * @var \Convo\Core\Workflow\IConversationProcessor[]
	 */
	private $_processors	=	array();

	/**
	 * @var string
	 */
	private $_role;

	private $_blockName;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_blockId   =   $properties['block_id'];
		$this->_role      =   $properties['role'];
		
		foreach ( $properties['processors'] as $processor) {
			/* @var $processor \Convo\Core\Workflow\IConversationProcessor */
			$this->addProcessor( $processor);
		}

		$this->_blockName = $properties['name'] ?? 'Nameless block';
	}


	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IIdentifiableComponent::getComponentId()
	 */
	public function getComponentId()
	{
		return $this->_blockId;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRunnableBlock::getRole()
	 */
	public function getRole()
	{
	    return $this->_role;
	}

	public function getName()
    {
        return $this->_blockName;
    }

	/**
	 * {@inheritDoc}
	 * @see \Convo\Pckg\Core\Elements\ElementCollection::read()
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{

	}

    /**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRunnableBlock::run()
	 */
	public function run( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$processors	=	$this->_collectAllAccountableProcessors();
		if ( empty( $processors)) {
			return;
		}

		$this->_logger->info('Processing request in ['.$this.']');

		$session_params		=	$this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION, $this);

		$session_params->setServiceParam( 'failure_count', intval( $session_params->getServiceParam( 'failure_count')));

		// $default_processor	=	null;
		// $default_result		=	null;
		foreach ( $processors as $processor)
		{
		    try {
		        if ( $this->_processProcessor( $request, $response, $processor)) {
		            $session_params->setServiceParam( 'failure_count', 0);
		            return ;
		        }
		    } catch ( StateChangedException $e) {
		        $session_params->setServiceParam( 'failure_count', 0);
		        throw $e;
		    }
		}

		$session_params->setServiceParam( 'failure_count', intval( $session_params->getServiceParam( 'failure_count')) + 1);
	}

	protected function _processProcessor(
	    \Convo\Core\Workflow\IConvoRequest $request,
	    \Convo\Core\Workflow\IConvoResponse $response,
	    \Convo\Core\Workflow\IConversationProcessor $processor)
	{
	    $processor->setParent( $this);
	    $result	=	$processor->filter( $request);

	    // if ( is_null( $default_processor)) {
	    // 	$default_processor	=	$processor;
	    // 	$default_result		=	$result;
	    // }

	    if ( $result->isEmpty()) {
	        $this->_logger->info( 'Processor ['.$processor.'] not appliable for ['.$request.']. Skipping ...');
	        return false;
	    }

	    $params				=	$this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
	    $params->setServiceParam( 'result', $result->getData());

	    $this->_logger->info('Processing with ['.$processor.']');

	    $processor->process( $request, $response, $result);

	    return true;
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


	public function addProcessor( \Convo\Core\Workflow\IConversationProcessor $processor)
	{
		$this->_processors[]	=	$processor;
		$this->addChild( $processor);
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRunnableBlock::getProcessors()
	 */
	public function getProcessors()
	{
		return $this->_processors;
	}

	protected function _collectAllAccountableProcessors()
	{
		$processors	=	array_merge( $this->_processors);

		if ( strpos( $this->getComponentId(), '__')  === 0) {
		    $this->_logger->debug( 'Do not use system processors in system block');
		    return $processors;
		}

		try {
			$block	=	$this->getService()->getBlockByRole( IRunnableBlock::ROLE_SERVICE_PROCESSORS);
			$processors	=	array_merge( $processors, $block->getProcessors());
		} catch ( \Convo\Core\ComponentNotFoundException $e) {
		}

		return $processors;
	}

	// PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        // User <-> Bot back and forth
        foreach ($this->getProcessors() as $processor)
        {
			/** @var \Convo\Pckg\Core\Processors\AbstractServiceProcessor $processor */
			$name = $processor->getName() !== '' ? $processor->getName() : 'Process - '.(new \ReflectionClass($processor))->getShortName().' ['.($processor->getId()).']';
            $section = new PreviewSection($name, $this->_logger);
			$section->collectOne($processor, '\Convo\Core\Preview\IUserSpeechResource');
			$section->collectOne($processor, '\Convo\Core\Preview\IBotSpeechResource');
			
			$pblock->addSection($section);
        }

        return $pblock;
    }
    
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_blockId.']';
	}
}
