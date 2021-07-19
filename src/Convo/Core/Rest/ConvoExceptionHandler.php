<?php declare(strict_types=1);

namespace Convo\Core\Rest;


use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class ConvoExceptionHandler implements \Psr\Http\Server\MiddlewareInterface
{

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	public function __construct( \Psr\Log\LoggerInterface $logger, \Convo\Core\Util\IHttpFactory $httpFactory)
	{
		$this->_logger		=	$logger;
		$this->_httpFactory	=	$httpFactory;
	}

	public function process( ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle( $request);
		} catch ( \Convo\Core\Rest\NotFoundException $e) {
			$this->_logger->notice( $e);
			return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 404, ['Content-Type'=>'application/json']);
		} catch ( \Convo\Core\Rest\NotAuthenticatedException $e) {
			$this->_logger->notice( $e);
			return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 401, ['Content-Type'=>'application/json']);
		} catch ( \Convo\Core\Rest\InvalidRequestException $e) {
			$this->_logger->warning( $e);
			return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 400, ['Content-Type'=>'application/json']);
		} catch (\Convo\Core\Rest\NotAuthorizedException $e) {
            $this->_logger->notice( $e);
            return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 403, ['Content-Type'=>'application/json']);
        } catch (\Convo\Core\Rest\OwnerNotSpecifiedException $e) {
            $this->_logger->notice( $e);
            return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 403, ['Content-Type'=>'application/json']);
        } catch (\Convo\Core\Rest\ServiceBuildingException $e) {
            $this->_logger->notice( $e);
            return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 405, ['Content-Type'=>'application/json']);
        } catch (\Convo\Core\Rest\ServiceDeletionException $e) {
            $this->_logger->notice( $e);
            return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 405, ['Content-Type'=>'application/json']);
        } catch (\Convo\Core\Rest\ServiceEnablementException $e) {
			$this->_logger->notice( $e);
			return $this->_httpFactory->buildResponse( [ 'message' => $e->getMessage()], 405, ['Content-Type'=>'application/json']);
		}
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
