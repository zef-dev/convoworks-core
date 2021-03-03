<?php


namespace Convo\Core\Admin;


use Convo\Core\Rest\RequestInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AmazonAlexaSkillInfo implements \Psr\Http\Server\RequestHandlerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\Adapters\Alexa\AmazonPublishingService
     */
    private $_amazonPublishingService;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    public function __construct($logger, $httpFactory, $adminUserDataProvider, $amazonPublishingService)
    {
        $this->_logger                  = $logger;
        $this->_httpFactory             = $httpFactory;
        $this->_adminUserDataProvider   = $adminUserDataProvider;
        $this->_amazonPublishingService = $amazonPublishingService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $json = $request->getParsedBody();
        $info = new RequestInfo($request);

        $user = $this->_adminUserDataProvider->findUser($json['owner']);

        if ($info->post() && $route = $info->route('get-existing-alexa-skill/{skillId}/manifest'))
        {
            return $this->_httpFactory->buildResponse($this->_getAlexaSkill($user, $route->get('skillId')));
        }

        if ($info->post() && $route = $info->route('get-existing-alexa-skill/{skillId}/account-linking-information'))
        {
            return $this->_httpFactory->buildResponse($this->_getAlexaSkillAccountLinkingInformation($user, $route->get('skillId')));
        }

        throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
    }

    private function _getAlexaSkill($user, $skillId) {
        return $this->_amazonPublishingService->getSkill($user, $skillId, 'development');
    }
    private function _getAlexaSkillAccountLinkingInformation($user, $skillId) {
        return $this->_amazonPublishingService->getAccountLinkingInformation($user, $skillId, 'development');
    }
}
