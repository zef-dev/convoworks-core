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

    public function __construct($logger, $serviceDataProvider, $adminUserDataProvider)
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;
        $this->_adminUserDataProvider = $adminUserDataProvider;
    }

    /**
     * @param \Convo\Core\IAdminUser $user
     * @param $serviceId
     * @return \Convo\Core\Adapters\Dialogflow\DialogflowApi
     */
    public function getApi(\Convo\Core\IAdminUser $user, $serviceId)
    {
        return new DialogflowApi(
            $this->_logger,
            $this->_convoServiceDataProvider,
            $user,
            $serviceId,
            $this->_adminUserDataProvider
        );
    }
}
