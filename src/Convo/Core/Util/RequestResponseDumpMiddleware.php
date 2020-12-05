<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RequestResponseDumpMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    /**
     * @var array
     */
    private $_convoDumpConfig = [];

    /**
     * @var array
     */
    private $_convoErrorDumpConfig = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    public function __construct($convoDumpConfig, $convoErrorDumpConfig, \Psr\Log\LoggerInterface $logger, \Convo\Core\Util\IHttpFactory $httpFactory)
    {
        $this->_convoDumpConfig	        = $convoDumpConfig;
        $this->_convoErrorDumpConfig    = $convoErrorDumpConfig;
        $this->_logger                  = $logger;
        $this->_httpFactory	            = $httpFactory;
    }

	public function process( ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
        $info	=	new \Convo\Core\Rest\RequestInfo( $request);
        $handlerId = $info->pathGet(1);

	    try {
            $responseInterface = $handler->handle($request);

            if(!empty($this->_convoDumpConfig) && in_array($handlerId, $this->_convoDumpConfig["classIdentifiers"])) {
                if (!empty($this->_convoDumpConfig["dumpPath"])) {
                    $body = $request->getBody();
                    $requestBodyToLog = $body->getContents();
                    $body->rewind();

                    $responseToLog = $responseInterface->getBody()->getContents();
                    $responseInterface->getBody()->rewind();

                    $dataToLog = [
                        "requestBodyToLog" => json_decode($requestBodyToLog),
                        "responseBodyToLog" => json_decode($responseToLog)
                    ];

                    $this->_storeData(
                        $dataToLog,
                        $this->_convoDumpConfig["dumpPath"],
                        $handlerId,
                        $this->_convoDumpConfig["dumpLimitPairs"] * 2
                    );
                }
            }

            return $responseInterface;
        } catch (\Throwable  $t) {
            if (!empty($this->_convoErrorDumpConfig["errorDumpPath"])) {
                $body = $request->getBody();
                $requestBodyToLog = $body->getContents();
                $body->rewind();

                $dataToLog = [
                    "requestBodyToLog" => json_decode($requestBodyToLog, true)
                ];

                $dataToLog['requestBodyToLog']['convoErrorReport'] = array(
                    'errorMessage' =>  $t->getMessage(),
                    'inFileAtLine' =>  $t->getFile() . '(' . $t->getLine() . ')',
                    'trace' =>  $t->getTraceAsString()
                );

                $this->_storeData(
                    $dataToLog,
                    $this->_convoErrorDumpConfig["errorDumpPath"],
                    $handlerId,
                    $this->_convoErrorDumpConfig["errorDumpLimit"]
                );
            }

            $this->_logger->critical( $t);
            return $this->_httpFactory->buildResponse( [ 'message' => $t->getMessage()], 500, ['Content-Type'=>'application/json']);
        }
	}

    private function _storeData($data, $dumpPath, $handlerPathName, $limit)
    {
        $this->_ensureFolder($dumpPath, $handlerPathName);
        $this->_cleanup($dumpPath, $handlerPathName, $limit);

        $time = date('Y-m-d_H-i-s');
        $dumpBasePath = $dumpPath.DIRECTORY_SEPARATOR.$handlerPathName.DIRECTORY_SEPARATOR;
        $requestFileName = $dumpBasePath . $time . '_request' . '.json';
        $responseFileName = $dumpBasePath . $time . '_response' . '.json';

        if (key_exists("requestBodyToLog", $data)) {
            file_put_contents( $requestFileName, json_encode( $data["requestBodyToLog"], JSON_PRETTY_PRINT));
        }
        if (key_exists("responseBodyToLog", $data)) {
            file_put_contents( $responseFileName, json_encode( $data["responseBodyToLog"], JSON_PRETTY_PRINT));
        }
    }

    private function _cleanup($dumpPath, $handlerPathName, $limit) {
        $folder	=	$dumpPath.DIRECTORY_SEPARATOR.$handlerPathName;
        $filesToClean = array_diff(scandir($folder), array('..', '.'));

        if (count($filesToClean) >= $limit) {
            unlink($folder . DIRECTORY_SEPARATOR . $filesToClean[2]);
            unlink($folder . DIRECTORY_SEPARATOR . $filesToClean[3]);
        }
    }

    private function _ensureFolder($dumpPath, $handlerPathName)
    {
        $folder	=	$dumpPath.DIRECTORY_SEPARATOR.$handlerPathName;

        if ( !is_dir( $folder)) {
            mkdir( $folder, 0777, true);
        }

        if ( !is_dir( $folder)) {
            throw new \Exception( 'Could not create folder ['.$folder.']');
        }
        return $folder;
    }

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}