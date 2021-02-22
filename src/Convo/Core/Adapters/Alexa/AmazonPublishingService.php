<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Util\IHttpFactory;

class AmazonPublishingService
{
    const BASE_SMAPI_URL = 'https://api.amazonalexa.com';

    const SESSION_MODE_DEFAULT = 'DEFAULT';
    const SESSION_MODE_FORCE_NEW_SESSION = 'FORCE_NEW_SESSION';

    const MAX_POLL_TRIES = 20;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\Adapters\Alexa\AmazonAuthService
     */
    private $_amazonAuthService;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    private $_httpClient;

    public function __construct($logger, $httpFactory, $amazonAuthService)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
        $this->_amazonAuthService = $amazonAuthService;

        $this->_httpClient = $this->_httpFactory->getHttpClient();
    }

    public function getSkill($owner, $skillId, $stage)
    {
        $this->_logger->debug("Checking [$stage][$skillId]");

        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/stages/$stage/manifest";

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_GET,
            $url
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function listSkills($owner, $vendorId, $skillIds = null, $maxResults = null, $nextToken = null)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/?vendorId=$vendorId";

        if ((!$skillIds || empty($skillIds)) && !$maxResults) {
            throw new \Exception('No skill IDs specified and no max results specified. Please specify either of the two (but not both)');
        }

        if ($skillIds)
        {
            if (count($skillIds) > 10) {
                throw new \Exception('Requested an overly large amount of skill IDs. Maximum is 10, requested ['.count($skillIds).']');
            }

            foreach ($skillIds as $skillId) {
                $url .= "&skillId=$skillId";
            }
        }
        else if ($maxResults)
        {
            $maxResults = max(0, min($maxResults, 50));

            $url .= "&maxResults=$maxResults&nextToken=$nextToken";
        }

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_GET,
            $url
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function createSkill($owner, $vendorId, $manifest)
    {
        $url = self::BASE_SMAPI_URL.'/v1/skills';

		$payload = [
			'vendorId' => $vendorId,
			'manifest' => $manifest
		];

		$this->_logger->debug('Going to try creating skill ['.print_r($payload, true).']');

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_POST,
            $url,
            [],
            $payload
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

	public function pollUntilSkillCreated($owner, $skillId)
	{
		$url = self::BASE_SMAPI_URL.'/v1/skills/'.$skillId.'/status';

		$this->_logger->debug('Going to check skill ['.$skillId.'] status');
		$tries = 1;
		do {
			$this->_logger->debug('Poll number ['.$tries.'/'.self::MAX_POLL_TRIES.']');
			$response = $this->_executeRequest(
				$owner,
				IHttpFactory::METHOD_GET,
				$url
			);
			$body = json_decode($response->getBody()->__toString(), true);
			$status = $body['manifest']['lastUpdateRequest']['status'];

			$this->_logger->debug('Skill status response ['.$response->getStatusCode().']['.print_r($body, true).']');
			usleep(500000); // im shleep half a second
		} while (($status === 'IN_PROGRESS') && (++$tries <= self::MAX_POLL_TRIES));

		$this->_logger->debug('Final polling response ['.$response->getStatusCode().']['.print_r($body, true).']');

		return $body;
	}

    public function updateSkill($owner, $skillId, $stage, $manifest)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/stages/$stage/manifest";

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_PUT,
            $url,
            [],
            $manifest
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function deleteSkill($owner, $skillId)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId";

        $this->_logger->debug("Going to delete skill [$skillId]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_DELETE,
            $url
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function updateInteractionModel($owner, $skillId, $interactionModel, $locale)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/stages/development/interactionModel/locales/$locale";

        $this->_logger->debug("Got interaction model\n[".json_encode($interactionModel, JSON_PRETTY_PRINT)."]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_PUT,
            $url,
            [],
            $interactionModel
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function simulateRequest($owner, $skillId, $text, $locale)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/stages/development/interactionModel/locales/".$locale."/profileNlu";

        $post = [
            "utterance" => $text
        ];

        $this->_logger->debug("Going to post to [$url] with [".print_r($post, true)."]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_POST,
            $url,
            [],
            $post
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function createCatalog($owner, $vendorId, $name, $desc)
	{
		$url = self::BASE_SMAPI_URL."/v1/skills/api/custom/interactionModel/catalogs";

		if (strlen($desc) > 255) {
			throw new \Exception('Description for catalog too long.');
		}

		$post = [
			"vendorId" => $vendorId,
			"catalog" => [
				"name" => $name,
				"description" => $desc
			]
		];

		$this->_logger->debug("Going to post to [$url] with [".print_r($post, true)."]");

		$response = $this->_executeRequest(
			$owner,
			IHttpFactory::METHOD_POST,
			$url,
			[],
			$post
		);

		$body = json_decode($response->getBody()->__toString(), true);
		return $body;
	}

	public function deleteCatalog($owner, $catalogId)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/api/custom/interactionModel/catalogs/$catalogId";

        $this->_logger->debug("Going to delete from url [$url]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_DELETE,
            $url
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

	public function getCatalogVersions($owner, $catalogId)
	{
		$url = self::BASE_SMAPI_URL."/v1/skills/api/custom/interactionModel/catalogs/$catalogId/versions";

		$this->_logger->debug("Going to get from [$url]");

		$response = $this->_executeRequest(
			$owner,
			IHttpFactory::METHOD_GET,
			$url
		);

		$body = json_decode($response->getBody()->__toString(), true);
		return $body;
	}

	public function createCatalogVersion($owner, $catalogId, $sourceUrl, $desc)
	{
		$url = self::BASE_SMAPI_URL."/v1/skills/api/custom/interactionModel/catalogs/$catalogId/versions";

		if (strlen($desc) > 255) {
			throw new \Exception('Description for catalog ['.$catalogId.'] version too long.');
		}

		$post = [
			"source" => [
				"type" => "URL",
				"url" => $sourceUrl
			],
			"description" => $desc
		];

		$this->_logger->debug("Going to post to [$url] with [".print_r($post, true)."]");

		$response = $this->_executeRequest(
			$owner,
			IHttpFactory::METHOD_POST,
			$url,
			[],
			$post
		);

		$version_status_location = self::BASE_SMAPI_URL.$response->getHeader('Location')[0];
		$this->_logger->debug('Version created. Going to poll until status is complete, polling at ['.$version_status_location.']');

		$tries = 1;
		do {
			$this->_logger->debug('Poll number ['.$tries.'/'.self::MAX_POLL_TRIES.']');
			$response = $this->_executeRequest(
				$owner,
				IHttpFactory::METHOD_GET,
				$version_status_location
			);
			$body = json_decode($response->getBody()->__toString(), true);
			$this->_logger->debug('Skill status response ['.$response->getStatusCode().']['.print_r($body, true).']');
			$status = $body['lastUpdateRequest']['status'];

			if ($status === 'FAILED') {
				throw new \Exception($body['lastUpdateRequest']['errors'][0]['message']);
			}

			usleep(500000); // im shleep half a second
		} while (($status === 'IN_PROGRESS') && (++$tries <= self::MAX_POLL_TRIES));

		$body = json_decode($response->getBody()->__toString(), true);
		return $body;
	}

    public function getSelfSignedSslCertificateFromSkill($owner, $skillId)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/sslCertificateSets/~latest";

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_GET,
            $url
        );

        return isset(json_decode($response->getBody()->__toString(), true)['sslCertificate']) ?
            json_decode($response->getBody()->__toString(), true)['sslCertificate'] : null;
    }

    public function uploadSelfSignedSslCertificateToSkill($owner, $skillId, $sslCertificateString)
    {
        $url = self::BASE_SMAPI_URL."/v1/skills/$skillId/sslCertificateSets/~latest";

        $sslCertificateBody = [
            "sslCertificate" => $sslCertificateString
        ];
        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_PUT,
            $url,
            [],
            $sslCertificateBody
        );

        return json_decode($response->getBody()->__toString(), true);
    }

    public function getSkillStatus($owner, $skillId) {
        $url = self::BASE_SMAPI_URL."/v1/skills/".$skillId."/status";

        $this->_logger->debug("Going to delete skill [$skillId]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_GET,
            $url
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function validateSkill($owner, $skillId, $stage, $locales)
    {
        $url = self::BASE_SMAPI_URL.'/v1/skills/'.$skillId.'/stages/'.$stage.'/validations';

        $payload = [
            'locales' => $locales
        ];

        $this->_logger->debug('Going to validate skill ['.print_r($payload, true).']');

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_POST,
            $url,
            [],
            $payload
        );

        $body = json_decode($response->getBody()->__toString(), true);
        return $body;
    }

    public function pollUntilSkillValidated($owner, $skillId, $stage, $validationId)
    {
        $url = self::BASE_SMAPI_URL.'/v1/skills/'.$skillId.'/stages/'.$stage.'/validations/'.$validationId;

        $this->_logger->debug('Going to check skill ['.$skillId.'] status');
        $tries = 1;
        do {
            $this->_logger->debug('Poll number ['.$tries.'/'.self::MAX_POLL_TRIES.']');
            $response = $this->_executeRequest(
                $owner,
                IHttpFactory::METHOD_GET,
                $url
            );
            $body = json_decode($response->getBody()->__toString(), true);
            $status = $body['status'];

            $this->_logger->debug('Skill status response ['.$response->getStatusCode().']['.print_r($body, true).']');
            sleep(30); // im sleep half a minute
        } while (($status === 'IN_PROGRESS') && (++$tries <= self::MAX_POLL_TRIES));

        $this->_logger->debug('Final polling response ['.$response->getStatusCode().']['.print_r($body, true).']');

        return $body;
    }

    public function enableSkillForUse($owner, $skillId, $stage) {
        $url = self::BASE_SMAPI_URL.'/v1/skills/'.$skillId.'/stages/'.$stage.'/enablement';

        $this->_logger->debug("Going to enable skill to use from url [$url]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_PUT,
            $url
        );

        $statusCode = $response->getStatusCode();
        return $statusCode;
    }

    public function checkSkillEnablementStatus($owner, $skillId, $stage) {
        $url = self::BASE_SMAPI_URL.'/v1/skills/'.$skillId.'/stages/'.$stage.'/enablement';

        $this->_logger->debug("Going to enable skill to use from url [$url]");

        $response = $this->_executeRequest(
            $owner,
            IHttpFactory::METHOD_GET,
            $url
        );

        $statusCode = $response->getStatusCode();
        return $statusCode;
    }

    // UTIL
    private function _executeRequest($user, $method, $url, $headers = [], $body = null)
    {
        $this->_amazonAuthService->refreshExpiredToken($user);
        $amz_auth = $this->_amazonAuthService->getAuthCredentials($user);

        $request = $this->_httpFactory->buildRequest(
            $method,
            $url,
            $headers,
            $body
        );
        $request  = $request->withHeader('Authorization', $amz_auth['access_token']);

        return $this->_httpClient->sendRequest($request);
    }

    public function __toString()
    {
        return get_class($this).'[]';
    }
}
