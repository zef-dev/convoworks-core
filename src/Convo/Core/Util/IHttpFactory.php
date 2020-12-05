<?php declare(strict_types=1);

namespace Convo\Core\Util;

interface IHttpFactory
{
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';

    const HTTP_STATUS_200 = 200;

    public function getHttpClient(array $config = array()): \Psr\Http\Client\ClientInterface;

    public function buildRequest($method, $uri, array $headers = [], $body = null, $version = '1.1'): \Psr\Http\Message\RequestInterface;

    public function buildResponse($data, $status = 200, $headers = []): \Psr\Http\Message\ResponseInterface;

    public function buildUri($url, $queryParams = []): \Psr\Http\Message\UriInterface;
}