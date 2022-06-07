<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\ConvoServiceInstance;
use Convo\Core\StateChangedException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class ConversationBlock extends \Convo\Pckg\Core\Elements\ElementCollection implements \Convo\Core\Workflow\IRunnableBlock
{

	private $_blockId;

	/**
	 * An optional collection of pre-dispatch elements to read
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_preDispatch = [];

	private $_preDispatchRun = false;

	/**
	 * @var \Convo\Core\Workflow\IConversationProcessor[]
	 */
	private $_processors	=	array();

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_fallback = [];

	/**
	 * @var string
	 */
	private $_role;

	private $_blockName;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_blockId		=	$properties['block_id'];

		if (isset($properties['pre_dispatch'])) {
			foreach ($properties['pre_dispatch'] as $preDispatch) {
				$this->_preDispatch[] = $preDispatch;
				$this->addChild($preDispatch);
			}
		}

		foreach ( $properties['processors'] as $processor) {
			/* @var $processor \Convo\Core\Workflow\IConversationProcessor */
			$this->addProcessor( $processor);
		}

		if ( isset( $properties['fallback'])) {
			foreach ( $properties['fallback'] as $fallback) {
				$this->addFallback( $fallback);
			}
		}

		if ( !isset( $properties['role'])) {
		    // BACK COMPATIBILITY
		    if ( $this->_blockId === ConvoServiceInstance::BLOCK_TYPE_SESSION_START) {
		        $this->_role  =   IRunnableBlock::ROLE_SESSION_START;
		    } else if ( $this->_blockId === ConvoServiceInstance::BLOCK_TYPE_SESSION_END) {
		        $this->_role  =   IRunnableBlock::ROLE_SESSION_ENDED;
		    } else if ( $this->_blockId === ConvoServiceInstance::BLOCK_TYPE_SERVICE_PROCESSORS) {
		        $this->_role  =   IRunnableBlock::ROLE_SERVICE_PROCESSORS;
		    } else if ( $this->_blockId === ConvoServiceInstance::BLOCK_TYPE_MEDIA_CONTROLS) {
		        $this->_role  =   IRunnableBlock::ROLE_MEDIA_PLAYER;
		    } else {
		        $this->_role  =   IRunnableBlock::ROLE_CONVERSATION_BLOCK;
		    }
		} else {
		    $this->_role  =   $properties['role'];
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
		$this->_readPreDispatch($request, $response);

		parent::read($request, $response);
	}

    /**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRunnableBlock::run()
	 */
	public function run( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$this->_readPreDispatch($request, $response);
		
		$processors	=	$this->_collectAllAccountableProcessors();
		if ( empty( $processors)) {
			$this->_logger->notice('No processors defined in ['.$this.']');
			$this->_readFallback( $request, $response);
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

		$this->_readFallback($request, $response);
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

	private function _readFallback(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		if (!empty($this->_fallback))
		{
		    $this->_logger->info( 'No valid matches found. Going to run fallback.');
			
			foreach ($this->_fallback as $fallback)
			{
				/** @var \Convo\Core\Workflow\IConversationElement $fallback */
				$fallback->read($request, $response);
			}
		}
		else
		{
			$this->_logger->info( 'No valid matches found, with no fallback exit. Looking for service level fallback');

			try {
				$default_fallback = $this->getService()->getBlockByRole(IRunnableBlock::ROLE_DEFAULT_FALLBACK);
				$default_fallback->read($request, $response);
			} catch (\Convo\Core\DataItemNotFoundException $e) {
				$this->_logger->info('No valid matches found, with no block level fallback nor service level fallback');
			}
		}
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

	public function addFallback(\Convo\Core\Workflow\IConversationElement $element)
	{
		$this->_fallback[] = $element;
		$this->addChild($element);
	}

	/**
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	public function getFallback() {
		return $this->_fallback;
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

        // What the bot says first
        $section = new PreviewSection('Read', $this->_logger);
		$section->collect($this->getElements(), '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

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

        // Fallback text
        $section = new PreviewSection('Fallback', $this->_logger);
		$section->collect($this->getFallback(), '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

        return $pblock;
    }

	private function _readPreDispatch(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		if (!$this->_preDispatchRun)
		{
			$this->_logger->info('Will check if there are any pre dispatch elements to read.');

			if (!empty($this->_preDispatch)) {
				$this->_logger->info('Found ['.count($this->_preDispatch).'] pre-dispatch elements to read.');
				
				foreach ($this->_preDispatch as $preDispatch) {
					$preDispatch->read($request, $response);
				}
				
				$this->_preDispatchRun = true;
			}
		}
		else 
		{
			$this->_logger->info('Pre dispatch already read in ['.$this.']');
			return;
		}
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_blockId.']';
	}
}
