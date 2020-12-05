<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Rest\InvalidRequestException;
use Psr\Http\Server\RequestHandlerInterface;

class AmazonAuthRestHandler implements RequestHandlerInterface
{
    private $_baseUrl;

    /**
     * Configuration provider
     *
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    /**
     * Alexa auth service
     *
     * @var \Convo\Core\Adapters\Alexa\AmazonAuthService
     */
    private $_amazonAuthService;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct($baseUrl, $httpFactory, $logger, $adminUserDataProvider, $amazonAuthService)
    {
        $this->_baseUrl = $baseUrl;

        $this->_logger				    = 	$logger;
        $this->_httpFactory			    = 	$httpFactory;
        $this->_adminUserDataProvider   =	$adminUserDataProvider;
        $this->_amazonAuthService	    =	$amazonAuthService;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $info = new \Convo\Core\Rest\RequestInfo( $request);

        if ( $info->get() && $info->route('admin-auth/amazon'))
        {
            $query_params = $request->getQueryParams();

            $this->_logger->debug('Got query params ['.print_r($query_params, true).']');

            $code = $query_params['code'] ?? null;

            if (isset($query_params['username']))
            {
                $this->_logger->debug('Found actual username parameter in request');
                $username = $request->getQueryParams()['username'];
            }
            else if (isset($query_params['state']))
            {
                $this->_logger->debug('Going to try parsing username from state ['.$query_params['state'].']');
                $username = base64_decode($query_params['state']);
            }
            else
            {
                throw new InvalidRequestException('Can not determine username in request ['.$info.']');
            }

            $this->_logger->debug('Got username ['.$username.']');

            $user = $this->_adminUserDataProvider->findUser($username);

            if (!$code)
            {
                return $this->_handleAdminAuthUrlGet($request, $user);
            }
            else
            {
                return $this->_handleAdminAuthPathAmazonGet($request, $user, $code);
            }
        }

        throw new \Convo\Core\Rest\NotFoundException('Could not map ['.$info.']');
    }

    private function _handleAdminAuthUrlGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
    {
        $loginUrl = $this->_amazonAuthService->getAuthUri($user);

        return $this->_httpFactory->buildResponse([
            'authUrl' => $loginUrl->__toString()
        ]);
    }

    private function _handleAdminAuthPathAmazonGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $code)
    {
        $res = $this->_amazonAuthService->redeemCodeForAccessToken($user, $code);

        $credentials = json_decode($res->getBody()->__toString(), true);
        $credentials['created'] = time();

        $this->_amazonAuthService->storeAuthCredentials($user, $credentials);

        return $this->_httpFactory->buildResponse([], 302, ['Location' => $this->_baseUrl]);
    }
}
