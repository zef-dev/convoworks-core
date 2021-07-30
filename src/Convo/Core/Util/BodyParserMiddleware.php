<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Seems that the Guzzle has bad RequestHandlerInterface::getParsedBody() which does not parses out the JSON body.
 * In such cases, this middleware willl fix the issue.
 * @author Tole
 *
 */
class BodyParserMiddleware implements \Psr\Http\Server\MiddlewareInterface
{	
	
	public function process( ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// It seems like sometimes the headers received in the request have all been lowercased
		// with dashes - replaced with underscores _
		// which results in Content-Type becoming content_type
		// check for either just to be safe
		$contentType = 
			$request->getHeaderLine('Content-Type') !== "" ? 
			$request->getHeaderLine('Content-Type') :
			$request->getHeaderLine('content-type');
		
		if ( strstr( $contentType, 'application/json')) {
			$contents = json_decode( file_get_contents('php://input'), true);
			if ( json_last_error() === JSON_ERROR_NONE) {
				$request = $request->withParsedBody($contents);
			}
		}
		
		if ( strstr( $contentType, 'application/x-www-form-urlencoded') || strstr( $contentType, 'multipart/form-data')) {
			$request = $request->withParsedBody( $_POST);
		}
		
		return $handler->handle( $request);
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}