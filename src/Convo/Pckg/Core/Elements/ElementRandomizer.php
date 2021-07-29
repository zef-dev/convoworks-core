<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Params\IServiceParamsScope;

class ElementRandomizer extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    const RANDOM_MODE_WILD  =   'wild';
    const RANDOM_MODE_SMART =   'smart';

    private $_mode;

	private $_elements;

	private $_namespace;

	private $_scopeType;
	
	public function __construct($properties)
	{
	    parent::__construct($properties);

        $this->_mode = $properties['mode'] ?? self::RANDOM_MODE_WILD;

	    if ($this->_mode === self::RANDOM_MODE_SMART && !isset($properties['namespace'])) {
            $this->_logger->warning('No namespace provided. Going to use component\'s ID.');
            $this->_namespace = $this->getId();
        } else if ($this->_mode === self::RANDOM_MODE_SMART && isset($properties['namespace'])) {
            $this->_namespace = $properties['namespace'];
        }

		$elements = $properties['elements'] ?? [];

		/** @var \Convo\Core\Workflow\IConversationElement $element */
		foreach ($elements as $element) 
		{
		    if (!is_a($element, 'Convo\Pckg\Core\Elements\CommentElement')){
		        $this->_elements[] = $element;
		        $this->addChild($element);
		    }
        }
        
		$this->_scopeType = $properties['scope_type'] ?? IServiceParamsScope::SCOPE_TYPE_INSTALLATION;
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$service	=	$this->getService();

		$mode = $this->evaluateString($this->_mode);
		$scope_type = $this->evaluateString($this->_scopeType);
		$namespace = $this->evaluateString($this->_namespace);
		
	    if ($mode === self::RANDOM_MODE_SMART) {
	    	$params = $service->getComponentParams($scope_type, $this)->getServiceParam($namespace);

            if ($params !== null && !empty($params))
			{
                $randomized =   $params;
            }
			else
			{
                $this->_logger->info( 'Empty or missing param ['.$namespace.']. Shuffling and storing new');

                $shuffled = $this->_shuffleArray( range( 0, count( $this->_elements) - 1));

                $service->getComponentParams($scope_type, $this)->setServiceParam($namespace, $shuffled);

                $randomized = $shuffled;
            }

            $next = array_shift($randomized);

            $service->getComponentParams($scope_type, $this)->setServiceParam($namespace, array_values( $randomized));

            $random_element = $this->_elements[$next];
            $random_element->read( $request, $response);
        } else {
	        $random_idx =   rand( 0, count( $this->_elements) - 1);

	        /** @var \Convo\Core\Workflow\IConversationElement $random_el */
	        $random_el = $this->_elements[$random_idx];
	        $random_el->read($request, $response);
        }
	}

	public function addElement( \Convo\Core\Workflow\IConversationElement $element)
	{
		$this->_elements[]		=	$element;
		$this->addChild( $element);
	}
	
	private function _shuffleArray( $array)
    {
        $new_arr    =   $array;

        shuffle( $new_arr);

        return array_values( $new_arr);
    }

	// UTIL
	public function __toString()
	{
		return parent::__toString().'[]';
	}
}