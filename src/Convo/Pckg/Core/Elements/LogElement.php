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
    private $_logLevel;
    private $_disableTestView;

    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_logMessage =   $properties['log_message'];
        $this->_logLevel =   $properties['log_level'] ?? \Psr\Log\LogLevel::INFO;
        $this->_disableTestView =   $properties['disable_test_view'] ?? '';
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $this->_logger->debug( 'Running Log Element...');
        
        $message = $this->evaluateString( $this->_logMessage);
        $level = $this->evaluateString( $this->_logLevel);
        $disable = boolval( $this->evaluateString( $this->_disableTestView));
        // Just Do the Logging
        if ( $level) {
            $this->_logger->log( $level, $message);
        } else {
            $this->_logger->info( $message);
        }
        
        if ( !$disable && $request->getPlatformId() === TestServiceRestHandler::DEFAULT_PLATFORM_ID) {
            $response->addText( "```
$message
```");
        }
    }
}
