<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandResponse;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class StartVideoPlayback extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_url;
    private $_title;
    private $_subtitle;

    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_url       =  $properties['url'];
        $this->_title     =  $properties['title'];
        $this->_subtitle  =  $properties['subtitle'];
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        if ( !( $response instanceof AmazonCommandResponse)) {
            $this->_logger->info( 'Not an AmazonCommandResponse. Exiting ...');
            return ;
        }

        $url = $this->evaluateString($this->_url);
        $title = $this->evaluateString($this->_title);
        $subtitle = $this->evaluateString($this->_subtitle);

        /** @var $response AmazonCommandResponse */
        $response->startVideoPlayback($url,$title, $subtitle);
    }
}
