<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Factory\ConvoServiceFactory;

class GeneratorItem extends AbstractWorkflowContainerComponent implements IConversationElement
{
	/**
	 * @var IConversationElement
	 */
	private $_element;
	private $_varName;
	private $_varData;


	public function __construct( $element, $slotName, $data)
	{
	    parent::__construct( ['_component_id' => ConvoServiceFactory::generateId()]);

		$this->_element   =   $element;
		$this->_varName   =   $slotName;
		$this->_varData   =   $data;
	}
	
	public function evaluateString( $string, $context = [])
	{
	    $own_params		=	$this->getService()->getAllComponentParams( $this);
	    return parent::evaluateString( $string, array_merge( $own_params, $context, [ $this->_varName => $this->_varData]));
	}
	
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->addChild( $this->_element);
        $this->_element->read( $request, $response);
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
}