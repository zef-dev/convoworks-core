<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;

/**
 * Class AudioPlayer
 * @package Convo\Pckg\Core\Elements
 * @deprecated
 */

class AudioPlayer extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_url;
	private $_mode;

	public function __construct( $properties)
	{
            parent::__construct( $properties);

            $this->_url    =   $properties['url'];
            $this->_mode     =   $properties['mode'];
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
            $this->_logger->debug( 'Raw url ['.$this->_url.']');

            $service	=	$this->getService();
            $params     =	$service->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION);


            if ($params->getServiceParam('current_song_token')) {
                $params->setServiceParam('last_song_token', $params->getServiceParam('current_song_token'));
            }



            $url    =   $this->evaluateString( $this->_url);
            $mode	=   $this->evaluateString( $this->_mode);

            $params->setServiceParam('current_url', $url);
            $params->setServiceParam('current_song_token', md5($url));

            $this->_logger->debug( 'Mode before enqueue '.$mode.']');
           // if ($mode != 'enqueue') {
           //     $params->setServiceParam('last_song_token', md5($url));
           // }

            $this->_logger->debug( 'Adding url ['.$url.']');
            $this->_logger->debug( 'Adding mode ['.$mode.']');

            $response->addText( 'Playing song from ['.$url.']');

            if (is_a( $response, 'Convo\Core\Adapters\Google\Gactions\ActionsCommandResponse'))
            {
                $this->_logger->debug('Google action invoked ['.$response->getText().']');
                /* @var \Convo\Core\Adapters\Google\Gactions\ActionsCommandResponse  $response */
                $response->prepareResponse(IResponseType::MEDIA_RESPONSE, $url);
            }

            if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
            {
                $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
                /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
                $response->prepareResponse(IResponseType::MEDIA_RESPONSE, $url);
            }

            if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
            {
                $response->setUrl( $url);
                $response->setMode( $mode);

                $response->setCurrentSongToken($params->getServiceParam('current_song_token'));

                if ($mode == 'enqueue') {
                    $response->setPreviousSongToken($params->getServiceParam('last_song_token'));
                }

                if ($request->getIntentType() == 'AudioPlayer.PlaybackStopped') {
                    $params->setServiceParam('offset_milliseconds', $request->getOffsetMilliseconds());
                } else if ($request->getIntentType() === 'PlaybackController.PlayCommandIssued' || $request->getIntentName() === 'AMAZON.ResumeIntent') {
                    $response->setOffsetMilliseconds($params->getServiceParam('offset_milliseconds'));
                }

                $this->_logger->debug('Amazon command invoked ['.$response->getText().']');
                /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response*/
                $response->prepareResponse(IResponseType::MEDIA_RESPONSE);
            }
	}


	// UTIL
	public function __toString()
	{
        return parent::__toString().'['.$this->_url.']';
	}
}
