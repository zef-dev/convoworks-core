<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

class GoogleNlpRequestFilter implements \Convo\Core\Workflow\IRequestFilter, \Psr\Log\LoggerAwareInterface
{
	/**
	 * @var \Convo\Pckg\Gnlp\Filters\ITextFilter
	 */
	protected $_filter;

	/**
	 * @var \Convo\Pckg\Gnlp\Api\IGoogleNlpFactory
	 */
	protected $_googleNlpFactory;

	/**
	 * @var string
	 */
	private $_apiKey;

	/**
	 * @var \Convo\Core\ConvoServiceInstance
	 */
	private $_service;
	
	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxParser
	 */
	protected $_googleNlpSyntaxParser;

	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	private $_id;
	
	public function __construct( $config, $service, \Convo\Pckg\Gnlp\Api\IGoogleNlpFactory $nlpFactory, \Convo\Pckg\Gnlp\GoogleNlSyntaxParser $syntaxParser)
    {
    	$this->_id			=	isset( $config['_component_id']) ?? $config['_component_id'];
    	
    	$this->_filter		=	isset( $config['filter']) ? $config['filter'] : new \Convo\Pckg\Gnlp\Filters\NopFilter();

    	if ( !isset( $config['api_key'])) {
			throw new \Exception('API key not set, but is required.');
	    }

	    $this->_apiKey                  =   $config['api_key'];
    	
	    $this->_googleNlpFactory		=	$nlpFactory;
	    $this->_googleNlpSyntaxParser	=	$syntaxParser;
		
		$this->_logger					=	new \Psr\Log\NullLogger();

    	if ( $service !== null) {
    		$this->setService( $service);
	    }
    }
    
    public function getId()
    {
    	return $this->_id;
    }
    
    public function setLogger( \Psr\Log\LoggerInterface $logger)
    {
    	$this->_logger	=	$logger;
    }
    
    public function accepts( \Convo\Core\Workflow\IConvoRequest $request) {
    	return !empty( $request->getText());
    }

    /**
     * @param \Convo\Core\Workflow\IConvoRequest $request
     * @return \Convo\Core\Workflow\IRequestFilterResult
     */
    public function filter( \Convo\Core\Workflow\IConvoRequest $request)
    {
    	$api_key        =   $this->getService()->evaluateString( $this->_apiKey);
    	$googleNlpApi   =   $this->_googleNlpFactory->getApi( $api_key);

    	$syntax_results =   $googleNlpApi->analyzeTextSyntax( $request->getText());
    	$roots			=	$this->_googleNlpSyntaxParser->parseGoogleResponse( $syntax_results);

    	foreach ($roots as $root) {
			$root->collectInto( $this->_filter);
		}
    	
    	return $this->_filter->getFilterResult();
    }

    public function getService()
    {
    	if ( !isset( $this->_service)) {
    		throw new \Convo\Core\ComponentNotFoundException( 'Service not set yet in ['.$this.']');
    	}
    	
    	return $this->_service;
    }

    public function setService( \Convo\Core\ConvoServiceInstance $service)
    {
	    $this->_service     =   $service;
    }

	// UTIL
    public function __toString()
    {
    	return get_class( $this);
    }
}