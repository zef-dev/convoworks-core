<?php

namespace Convo\Core\Adapters\Alexa\Validators;

use Convo\Core\Util\CurrentTimeService;
use Convo\Core\Util\ICurrentTimeService;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class AlexaRequestValidator
{
    /**
     * @var IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var CurrentTimeService
     */
    private $_currentTimeService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    private $_validCertificateUrl = false;

    public function __construct(\Convo\Core\Util\IHttpFactory $httpFactory, ICurrentTimeService $currentTimeService, LoggerInterface $logger)
    {
        $this->_httpFactory         = $httpFactory;
        $this->_currentTimeService  = $currentTimeService;
        $this->_logger              = $logger;
    }

    /**
     * @param $requestBody
     * @param $signatureHeader
     * @param $signatureCertChainUrlHeader
     * @param $servicePlatformConfig
     */
    public function verifyRequest(\Psr\Http\Message\ServerRequestInterface $request, $servicePlatformConfig) {
        $requestBody = $request->getBody()->getContents();
        $signatureHeader = $request->getHeader("Signature");
        $signatureCertChainUrlHeader = $request->getHeader("SignatureCertChainUrl");

        $verifiedSillId = $this->_verifySkillId($requestBody, $servicePlatformConfig);
        $verifiedRequestTimestamp = $this->_verifyRequestTimestamp($requestBody);
        $verifiedCertificate = $this->_verifyCertificate($requestBody, $signatureHeader, $signatureCertChainUrlHeader);

        $request->getBody()->rewind();

        return [
            "verifiedSkillId" => $verifiedSillId,
            "verifiedRequestTimestamp" => $verifiedRequestTimestamp,
            "validCertificateUrl" => $this->_validCertificateUrl,
            "verifiedCertificate" => $verifiedCertificate
        ];
    }

    /**
     * @var  $request
     */
    private function _verifyRequestTimestamp($requestBody) {
        $req = json_decode($requestBody);
        $timezone = $this->_currentTimeService->getTimezone();
        $date = new \DateTime($req->request->timestamp, $timezone);
        $currentTimeStamp = $this->_currentTimeService->getTime();
        $requestTimeStamp = $date->getTimestamp();

        $timeDeltaInSeconds =   $currentTimeStamp - $requestTimeStamp;
        if ($timeDeltaInSeconds > 150) {
            $errorMsg = 'The request is too old!';
            $this->_logger->warning($errorMsg);
            return false;
        }

        return true;
    }

    /**
     * @param $requestBody
     * @param $signatureHeader
     * @param $signatureCertChainUrlHeader
     */
    private function _verifyCertificate($requestBody, $signatureHeader, $signatureCertChainUrlHeader) {
        $wasVerified = false;
        if (is_array($signatureHeader) && is_array($signatureCertChainUrlHeader) && count($signatureHeader) > 0 && count($signatureCertChainUrlHeader) > 0) {
            // generate local cert path
            if (!empty($signatureCertChainUrlHeader[0]) && $this->_validateCertUrl($signatureCertChainUrlHeader[0]) === true) {
                $localCertPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.md5($signatureCertChainUrlHeader[0]).'.pem';
                $certData = $this->_fetchCertData($signatureCertChainUrlHeader[0], $localCertPath);
                $verificationStatus = $this->_verifyCert($requestBody, $signatureHeader[0], $certData);

                if ($verificationStatus === true) {
                    $wasVerified = true;
                }
            } else {
                $errorMsg = 'Invalid certificate url!';
                $this->_logger->warning($errorMsg);
                $wasVerified = false;
            }
        } else {
            $errorMsg = 'Missing required headers!';
            $this->_logger->warning($errorMsg);
            $wasVerified = false;
        }

        return $wasVerified;
    }

    private function _validateCertUrl($signatureCertChainUrl)
    {
        $uriParts = parse_url($signatureCertChainUrl);
        $isValid = true;

        if (!empty($signatureCertChainUrl)) {
            if (array_key_exists('host', $uriParts) && strcasecmp($uriParts['host'], 's3.amazonaws.com') != 0) {
                $isValid = false;
            }

            if (array_key_exists('path', $uriParts) && strpos($uriParts['path'], '/echo.api/') !== 0) {
                $isValid = false;
            }

            if (array_key_exists('scheme', $uriParts) && strcasecmp($uriParts['scheme'], 'https') != 0) {
                $isValid = false;
            }

            if (array_key_exists('port', $uriParts) && $uriParts['port'] != '443') {
                $isValid = false;
            }
        } else {
            $isValid = false;
        }

        $this->_validCertificateUrl = $isValid;

        return $isValid;
    }

    private function _fetchCertData($signatureCertChainUrl, $localCertPath): string
    {
        if (!file_exists($localCertPath)) {
            $request = $this->_httpFactory->buildRequest(
                \Convo\Core\Util\IHttpFactory::METHOD_GET,
                $signatureCertChainUrl);
            /**
             * @var \GuzzleHttp\Client
             */
            $client = $this->_httpFactory->getHttpClient();

            try {
                $response = $client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                $this->_logger->warning($e->getMessage());
                return false;
            }

            $certData = $response->getBody()->getContents();
            @file_put_contents($localCertPath, $certData);
        } else {
            $certData = @file_get_contents($localCertPath);
        }

        return $certData;
    }

    /**
     * @param string $amazonRequestBody
     * @param string $signature
     * @param string $certData
     */
    private function _verifyCert($amazonRequestBody, $signature, $certData)
    {
        $verificationStatus = true;
        if (1 !== @openssl_verify($amazonRequestBody, base64_decode($signature, true), $certData, OPENSSL_ALGO_SHA1)) {
            $errorMsg = 'Could not verify certificate!';
            $this->_logger->warning($errorMsg);
            $verificationStatus = false;
        }

        return $verificationStatus;
    }

    private function _verifySkillId($requestBody, $servicePlatformConfig) {
        $req = json_decode($requestBody);
        // $this->_data['context']['System']['application']['applicationId'];
        $providedSkillID = $req->context->System->application->applicationId;
        $configuredSkillID = '';

        if (is_array($servicePlatformConfig)) {
            $configuredSkillID = $servicePlatformConfig['amazon']['app_id'];
        }

        if ($providedSkillID !== $configuredSkillID) {
            $errorMsg = 'The configured skill id [' . $configuredSkillID .'] does not match with the provided skill id ['. $providedSkillID.']';
            $this->_logger->warning($errorMsg);
            return false;
        }

        return true;
    }

    public function getCurrentTimeService() {
        return $this->_currentTimeService;
    }

}
