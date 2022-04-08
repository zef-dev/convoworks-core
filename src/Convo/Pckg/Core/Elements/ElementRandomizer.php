<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IElementGeneratorItem;

class ElementRandomizer extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    const RANDOM_MODE_WILD  =   'wild';
    const RANDOM_MODE_SMART =   'smart';

    private $_mode;

	private $_elements;

	private $_loop;

	private $_isRepeat;

	private $_scopeType;
	
	public function __construct($properties)
	{
	    parent::__construct($properties);

        $this->_mode = $properties['mode'] ?? self::RANDOM_MODE_WILD;

		$this->_loop = $properties['loop'] ?? false;

		$this->_isRepeat = $properties['is_repeat'] ?? false;

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
		$loop = $this->evaluateString($this->_loop);
		$isRepeat = $this->evaluateString($this->_isRepeat);
		$componentId = $this->getId();
		
		$elements = $this->getService()->spreadElements( $this->_elements);
		
		
	    if ($mode === self::RANDOM_MODE_SMART) {
	    	$params = $service->getComponentParams($scope_type, $this)->getServiceParam($componentId);

            if (!empty($params) && isset($params['elements']) && isset($params['current_index']))
			{
                $randomized =   $params['elements'];
                $next =   $params['current_index'];

				if (!$isRepeat) {
					$next++;
				}
            }
			else
			{
                $this->_logger->info( 'Shuffling and storing new');

                $shuffled = $this->_shuffleArray( range( 0, count( $elements) - 1));

                $service->getComponentParams($scope_type, $this)->setServiceParam($componentId, [
						'elements' => $shuffled,
						'current_index' => 0
					]
				);

                $randomized = $shuffled;
				$next = 0;
            }

			if ($next >= count($elements)) {
				$next = 0;
				if (!$loop) {
					$randomized = $this->_shuffleArray( range( 0, count( $elements) - 1));
				}
			}

            $service->getComponentParams($scope_type, $this)->setServiceParam($componentId, [
					'elements' => array_values( $randomized),
					'current_index' => $next
				]
			);

            $random_element = $elements[$randomized[$next]];
        } else {
	        $random_idx     = rand( 0, count( $elements) - 1);
	        $random_element = $elements[$random_idx];
        }
        
        if ( $random_element instanceof IElementGeneratorItem) {
            /** @var IElementGeneratorItem $random_el */
            $random_element =   $random_element->getElement();
            /** @var \Convo\Core\Workflow\IConversationElement $random_el */
        }
        
        $random_element->read( $request, $response);
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