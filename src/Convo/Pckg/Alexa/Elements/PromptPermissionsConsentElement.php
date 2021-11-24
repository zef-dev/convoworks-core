<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class PromptPermissionsConsentElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

    public function __construct($properties, $convoServiceDataProvider)
    {
        parent::__construct($properties);
		$this->_convoServiceDataProvider = $convoServiceDataProvider;
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest'))
        {
			$amazon_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
					new RestSystemUser(),
					$this->getService()->getId(),
					IPlatformPublisher::MAPPING_TYPE_DEVELOP
				)['amazon'] ?? [];
			$permissionsToAskFor = $amazon_config['permissions'] ?? [];

            /** @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
			if (!empty($permissionsToAskFor)) {
				$response->setPermissionsToAskFor($permissionsToAskFor);
				$response->promptPermissionsConsent();
				$response->setShouldEndSession(true);

				throw new \Convo\Core\SessionEndedException();
			}
        }
    }
}
