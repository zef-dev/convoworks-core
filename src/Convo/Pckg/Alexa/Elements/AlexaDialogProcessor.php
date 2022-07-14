<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Elements;


class AlexaDialogProcessor extends \Convo\Pckg\Core\Processors\AbstractServiceProcessor
{

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_ok =    [];

    public function __construct($properties, $service)
    {
        parent::__construct($properties);
        $this->setService($service);

        if ( $properties['ok'] && is_array( $properties['ok'])) {
            $this->_ok = $properties['ok'];
            foreach ( $this->_ok as $ok) {
                $this->addChild($ok);
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
        if (!empty($dialogState)) {
            foreach ( $this->_ok as $ok) {
                $ok->read( $request, $response);
            }
        } else {
            throw new \Exception( 'Got empty dialog state ['.$dialogState.'] for ['.$request->getIntentName().']');
        }
    }
}
