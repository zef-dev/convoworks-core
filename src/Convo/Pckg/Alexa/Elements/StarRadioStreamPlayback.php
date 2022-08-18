<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Media\AudioItemToken;
use Convo\Core\Media\RadioStream;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRadioStreamResponse;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Workflow\IMediaType;
use Convo\Core\Workflow\IRunnableBlock;

class StarRadioStreamPlayback extends AbstractWorkflowContainerComponent implements IConversationElement
{

    private $_streamUrl;
    private $_radioStationName;
    private $_slogan;
    private $_radioStationLogoURL;

    /**
     * @var string
     */
    private $_radioStreamInfoVar;

    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_streamUrl           =   $properties['stream_url'] ?? '';
        $this->_radioStationName	=	$properties['radio_station_name'] ?? '';
        $this->_slogan	            =	$properties['slogan'] ?? '';
        $this->_radioStationLogoURL	=	$properties['radio_station_logo_url'] ?? '';
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        if ( !( $response instanceof IConvoRadioStreamResponse)) {
            $this->_logger->info( 'Not an IConvoRadioStreamResponse. Exiting ...');
            return ;
        }

        $streamUrl = $this->evaluateString($this->_streamUrl);
        $radioStationName = $this->evaluateString($this->_radioStationName);
        $slogan = $this->evaluateString($this->_slogan);
        $radioStationLogoURL = $this->evaluateString($this->_radioStationLogoURL);

        $radioStream = new RadioStream($streamUrl, $radioStationName, $slogan, $radioStationLogoURL);
        $response->startRadioStream($radioStream);
    }
}
