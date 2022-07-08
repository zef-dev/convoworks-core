<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Elements;


class AlexaDialogProcessor extends \Convo\Pckg\Core\Processors\AbstractServiceProcessor
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_started =    [];

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_inProgress  =    [];

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_completed  =    [];

    public function __construct($properties, $packageProviderFactory, $convoServiceDataProvider, $service)
    {
        parent::__construct($properties);
        $this->setService($service);

        $this->_packageProviderFactory    =   $packageProviderFactory;
        $this->_convoServiceDataProvider  =   $convoServiceDataProvider;

        if ( $properties['started'] && is_array( $properties['started'])) {
            $this->_started = $properties['started'];
            foreach ( $this->_started as $started) {
                $this->addChild($started);
            }
        }

        if ( $properties['in_progress'] && is_array( $properties['in_progress'])) {
            $this->_inProgress = $properties['in_progress'];
            foreach ( $this->_inProgress as $inProgress) {
                $this->addChild($inProgress);
            }
        }

        if ( $properties['completed'] && is_array( $properties['completed'])) {
            $this->_completed  =   $properties['completed'];
            foreach ( $this->_completed as $completed) {
                $this->addChild($completed);
            }
        }
    }

    public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result)
    {
        if ( !is_a( $request, \Convo\Core\Workflow\IIntentAwareRequest::class)) {
            throw new \Exception( 'This processor requires IIntentAwareRequest environment');
        }
        $dialogState = $request->getPlatformData()['request']['dialogState'] ?? '';

        $this->_logger->debug('Got dialog state ['.$dialogState.']');

        switch ($dialogState) {
            case 'COMPLETED':
                $this->_logger->debug( 'Reading COMPLETED');

                foreach ( $this->_completed as $completed) {
                    $completed->read( $request, $response);
                }
                return ;
            case 'STARTED':
                $this->_logger->debug( 'Reading STARTED');

                foreach ( $this->_started as $started) {
                    $started->read( $request, $response);
                }

                return ;
            case 'IN_PROGRESS':
                $this->_logger->debug( 'Reading IN_PROGRESS');

                foreach ( $this->_inProgress as $inProgress) {
                    $inProgress->read( $request, $response);
                }
                return ;
            default:
                throw new \Exception( 'Got dialog state ['.$dialogState.'] for ['.$request->getIntentName().']'.
                    ' but expected STARTED, IN_PROGRESS or COMPLETED');
        }
    }
}
