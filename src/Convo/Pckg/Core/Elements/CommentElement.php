<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

class CommentElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    /**
     * @var string
     */
    private $_comment;

    
    public function __construct( $properties)
    {
    	parent::__construct( $properties);
        $this->_comment =   $properties['comment'];
    }
    
    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IConversationElement::read()
     */
    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        // Do nothing
//         $this->_logger->debug( 'Doing nothing ['.$this.']');
        return;
    }

    public function __toString() {
        return parent::__toString().'['.$this->_comment.']';
    }
}