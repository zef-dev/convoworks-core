<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Processors;

class YesNoProcessor extends \Convo\Pckg\Core\Processors\AbstractServiceProcessor
{

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_yes =    [];

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_no  =    [];

	public function __construct($properties, $packageProviderFactory, $service)
	{
	    parent::__construct( $properties);
	    $this->setService($service);

	    $this->_packageProviderFactory  =   $packageProviderFactory;

	    if ( $properties['yes'] && is_array( $properties['yes'])) {
		    $this->_yes =   $properties['yes'];
		    foreach ( $this->_yes as $yes) {
		        $this->addChild( $yes);
		    }
		}

		if ( $properties['no'] && is_array( $properties['no'])) {
		    $this->_no  =   $properties['no'];
		    foreach ( $this->_no as $no) {
		        $this->addChild( $no);
		    }
		}

		$this->_requestFilters = $this->_initFilters();
	}

	private function _initFilters()
	{
	    $this->_logger->debug( 'Generating filters');

	    $yes_reader    =   new \Convo\Pckg\Core\Filters\ConvoIntentReader( [ 'intent' => 'convo-core.YesIntent'], $this->_packageProviderFactory);
	    $yes_reader->setLogger( $this->_logger);
	    $yes_reader->setService( $this->getService());

	    $no_reader     =   new \Convo\Pckg\Core\Filters\ConvoIntentReader( [ 'intent' => 'convo-core.NoIntent'], $this->_packageProviderFactory);
	    $no_reader->setLogger( $this->_logger);
	    $no_reader->setService( $this->getService());

	    $config        =    [
	        'readers' => [ $yes_reader, $no_reader]
	    ];

	    $intent_filter =   new \Convo\Pckg\Core\Filters\IntentRequestFilter( $config);
	    $intent_filter->setLogger( $this->_logger);
	    $intent_filter->setService( $this->getService());

	    $this->addChild( $intent_filter);

	    return [$intent_filter];
	}

	public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result)
	{
	    if ( !is_a( $request, \Convo\Core\Workflow\IIntentAwareRequest::class)) {
	        throw new \Exception( 'This processor requires IIntentAwareRequest environment');
	    }

	    /* @var \Convo\Core\Workflow\IIntentAwareRequest $request */
        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($this->getService()->getPackageIds());
	    $sys_intent = $provider->findPlatformIntent( $request->getIntentName(), $request->getIntentPlatformId());

	    $this->_logger->debug( 'Got sys intent ['.$sys_intent->getName().']['.$sys_intent.']');

	    if ( $sys_intent->getName() === 'YesIntent') {
			$this->_logger->debug( 'Reading yes');
			foreach ( $this->_yes as $yes) {
			    $yes->read( $request, $response);
			}
			return ;
		}

		if ( $sys_intent->getName() === 'NoIntent') {
			$this->_logger->debug( 'Reading no');
			foreach ( $this->_no as $no) {
			    $no->read( $request, $response);
			}
			return ;
		}

		throw new \Exception( 'Got convo intent ['.$sys_intent.'] for ['.$request->getPlatformId().']['.$request->getIntentName().']'.
		    ' but expected YesIntent or NoIntent');
	}
}
