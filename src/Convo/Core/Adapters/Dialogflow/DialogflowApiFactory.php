<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Dialogflow;

class DialogflowApiFactory
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\IAdminUserDataProvider
	 */
    private $_adminUserDataProvider;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    public function __construct($logger, $serviceDataProvider, $adminUserDataProvider, $httpFactory)
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;
        $this->_adminUserDataProvider = $adminUserDataProvider;
        $this->_httpFactory = $httpFactory;
    }

    /**
     * @param \Convo\Core\IAdminUser $user
     * @param $serviceId
     * @return \Convo\Core\Adapters\Dialogflow\DialogflowApi
     */
    public function getApi(\Convo\Core\IAdminUser $user, $serviceId, $platformId = 'dialogflow')
    {
        return new DialogflowApi(
            $this->_logger,
            $this->_convoServiceDataProvider,
            $user,
            $serviceId,
            $this->_adminUserDataProvider,
            $platformId,
            $this->_httpFactory
        );
    }
}
