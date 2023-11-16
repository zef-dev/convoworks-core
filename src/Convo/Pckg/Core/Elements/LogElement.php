<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Admin\TestServiceRestHandler;

class LogElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{

    /**
     * @var string
     */
    private $_logMessage;

    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_logMessage =   $properties['log_message'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $this->_logger->info('Running Log Element...');
        $logMessage = $this->evaluateString($this->_logMessage);
        // Just Do the Logging
        $this->_logger->info($logMessage);
        //return;
        
        if ( $request->getPlatformId() === TestServiceRestHandler::DEFAULT_PLATFORM_ID) {
            $response->addText( "```
$logMessage
```");
        }
        
    }
}
