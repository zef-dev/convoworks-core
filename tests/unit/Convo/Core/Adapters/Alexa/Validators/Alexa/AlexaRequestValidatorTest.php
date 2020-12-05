<?php declare(strict_types=1);

use Convo\Core\Util\MockTimeService;
use Convo\Core\Adapters\Alexa\Validators\AlexaRequestValidator;
use Convo\Guzzle\GuzzleHttpFactory;
use Convo\Core\Util\Test\ConvoTestCase;

class AlexaRequestValidatorTest extends ConvoTestCase
{
    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var MockTimeService
     */
    private $_mockTimeService;

    public function setUp(): void
    {
        parent::setUp();
        $this->_httpFactory = new GuzzleHttpFactory();
        $this->_mockTimeService = new MockTimeService();
        $this->_mockTimeService->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    public function testRejectAmazonCommandRequestByTimestampValidation() {
        $validator = new AlexaRequestValidator($this->_httpFactory, $this->_mockTimeService, $this->_logger);
        $requestBody = json_encode($this->_getMaliciousRequest());
        $serverRequest = new \GuzzleHttp\Psr7\ServerRequest('POST', '', [], $requestBody);
        $validationResult = $validator->verifyRequest($serverRequest, $this->_getAmazonConfig());
        $this->assertEquals(false, $validationResult["verifiedRequestTimestamp"]);
    }

    public function testRejectAmazonCommandRequestByCertValidationWithoutHeaders() {
        $validator = new AlexaRequestValidator($this->_httpFactory, $this->_mockTimeService, $this->_logger);

        $requestBody = json_encode($this->_getMaliciousRequest());
        $this->_updateTimestamp($requestBody);
        $serverRequest = new \GuzzleHttp\Psr7\ServerRequest('POST', '', [], $requestBody);
        $validationResult = $validator->verifyRequest($serverRequest, $this->_getAmazonConfig());
        $this->assertEquals(false, $validationResult["validCertificateUrl"]);
    }

    /**
     * @dataProvider sampleBadCertificateUrlProvider
     * @param $badUrl
     */
    public function testRejectAmazonCommandRequestByCertValidationWithInvalidInvalidCertificateUrlHeader($badUrl) {
        $validator = new AlexaRequestValidator($this->_httpFactory, $this->_mockTimeService, $this->_logger);

        $requestBody = json_encode($this->_getMaliciousRequest());
        $this->_updateTimestamp($requestBody);

        $this->assertEquals(0,0);
        $headers = [
            'Signature' => '',
            'SignatureCertChainUrl' => $badUrl,
        ];
        $serverRequest = new \GuzzleHttp\Psr7\ServerRequest('POST', '', $headers, $requestBody);
        $validationResult = $validator->verifyRequest($serverRequest, $this->_getAmazonConfig());
        $this->assertEquals(false, $validationResult["validCertificateUrl"]);
    }

    public function testRejectAmazonCommandRequestBySkillIdValidation() {
        $validator = new AlexaRequestValidator($this->_httpFactory, $this->_mockTimeService, $this->_logger);

        $requestBody = json_encode($this->_getMaliciousRequest());
        $this->_updateTimestamp($requestBody);

        $headers = [
            'Signature' => $this->_getSignatureHeader(),
            'SignatureCertChainUrl' => $this->_getSignatureCertChainUrlHeader(),
        ];
        $serverRequest = new \GuzzleHttp\Psr7\ServerRequest('POST', '', $headers, $requestBody);
        $validationResult = $validator->verifyRequest($serverRequest, $this->_getAnotherAmazonConfig());
        $this->assertEquals(false, $validationResult["verifiedSkillId"]);
    }

    private function _updateTimestamp($requestBody) {
        $validator = new AlexaRequestValidator($this->_httpFactory, $this->_mockTimeService, $this->_logger);
        $req = json_decode($requestBody);
        $timezone =  $validator->getCurrentTimeService()->getTimezone();
        $date = new \DateTime($req->request->timestamp, $timezone);
        $validator->getCurrentTimeService()->setTime($date->getTimestamp());
    }

    public function sampleBadCertificateUrlProvider()
    {
        return [
            [['']],
            [['http://s3.amazonaws.com/echo.api/echo-api-cert.pem']],
            [['https://notamazon.com/echo.api/echo-api-cert.pem']],
            [['https://s3.amazonaws.com/EcHo.aPi/echo-api-cert.pem']],
            [['https://s3.amazonaws.com/invalid.path/echo-api-cert.pem']],
            [['https://s3.amazonaws.com:563/echo.api/echo-api-cert.pem']]
        ];
    }

    private function _getAmazonConfig() {
        return [
            "amazon" => [
                "enabled" => 1,
                "mode" => "manual",
                "invocation" => "burger master",
                "app_id" => "amzn1.ask.skill.05566b87-785f-42c0-a825-9e7e1537fb6a",
                "account_id" => ""
            ]
        ];
    }

    private function _getAnotherAmazonConfig() {
        return [
            "amazon" => [
                "enabled" => 1,
                "mode" => "manual",
                "invocation" => "burger master",
                "app_id" => "amzn1.ask.skill.05566b87-785f-42c0-a825-9e7e1537fb6c",
                "account_id" => ""
            ]
        ];
    }

    private function _getSignatureHeader() {
        return ["WRsVy9obbPWvDHFVVeCDsxfWWmKeWkGoq+vFYT3dpmizRnZerNI1tHhYUTfVtmSy6bWBI5UTYMWSYJu2SJUrt4Zfwa3kV1dRf+5tnkgzZrW/nY6AKkLe3V3SzM3cPb6XhK7U68CScestYhPw40oioSFo9ELv2Cb2BOmaMOw3NxDxYpyLt05Ugun+tNZad1HwCfMFhDkvWLcYQSD6UxPNeoN72zVpyYCJTJdgleZvgkNopuUY5LSxy1gbIOqtHB130E8KneyrdT+ZubbcgwnN2FB3rxIuQCjrKEFXRTpsNgt6/NYEcbjEWrpBfiNb8LooSsBjBkUrrHZRy+zZl2feoQ=="];
    }

    private function _getSignatureCertChainUrlHeader() {
        return ["https://s3.amazonaws.com/echo.api/echo-api-cert-7.pem"];
    }

    private function _getMaliciousRequest() {
        return [
            "version" => "1.0",
            "session" => [
                "new" => false,
                "sessionId" => "amzn1.echo-api.session.780a8b1a-00c3-496b-9c1c-7c0361705425",
                "application" => [
                    "applicationId" => "amzn1.ask.skill.05566b87-785f-42c0-a825-9e7e1537fb6a"
                ],
                "user" => [
                    "userId" => "amzn1.ask.account.AGBVJCXJT5DXYYOIWG6O6QOCZQQX4B4QB7WM7TRSTWY4KJ4NGEOM5B2NLTVL4EQ7BMHSJDS6OOY7IJKJQEJRDQAKAENVJFFQUP7STYOFVJAFHEVBPECWYVMTPR4O6ICBMRAFRHK7JYGRQ7FF3ADNVN6Y4NCMYY24LVYQ7WALEQQTYJHVOFXBCUAS6PHIONYG67JMDE6NY54YMOI"
                ]
            ],
            "context" => [
                "Display" => [
                    "token" => ""
                ],
                "System" => [
                    "application" => [
                        "applicationId" => "amzn1.ask.skill.05566b87-785f-42c0-a825-9e7e1537fb6a"
                    ],
                    "user" => [
                        "userId" => "amzn1.ask.account.AGBVJCXJT5DXYYOIWG6O6QOCZQQX4B4QB7WM7TRSTWY4KJ4NGEOM5B2NLTVL4EQ7BMHSJDS6OOY7IJKJQEJRDQAKAENVJFFQUP7STYOFVJAFHEVBPECWYVMTPR4O6ICBMRAFRHK7JYGRQ7FF3ADNVN6Y4NCMYY24LVYQ7WALEQQTYJHVOFXBCUAS6PHIONYG67JMDE6NY54YMOI"
                    ],
                    "device" => [
                        "deviceId" => "amzn1.ask.device.AEEDUFD5RCKJEN3S2HA5YRTNWTUY7NLSLWAFWLQDUUZ4VWOP3MUH35JAFHF2OVWSKBOYWQB7ZF45ND3UCSIXGXFGIE2QRE32AMCNEWNRQBYNAVPVOF2UZWS7LYWHQZNVHCNO3RIQDQDUQT4KFUDEDL6W2ASNZHJZMRAT24Q7VXVRJFWHNF6TO",
                        "supportedInterfaces" => [
                                "Display" => [
                                    "templateVersion" => "1.0",
                                    "markupVersion" => "1.0"
                            ]
                        ]
                    ],
                    "apiEndpoint" => "https://api.amazonalexa.com",
                    "apiAccessToken" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjEifQ.eyJhdWQiOiJodHRwczovL2FwaS5hbWF6b25hbGV4YS5jb20iLCJpc3MiOiJBbGV4YVNraWxsS2l0Iiwic3ViIjoiYW16bjEuYXNrLnNraWxsLjA1NTY2Yjg3LTc4NWYtNDJjMC1hODI1LTllN2UxNTM3ZmI2YSIsImV4cCI6MTU4NTkxMjQ1OSwiaWF0IjoxNTg1OTEyMTU5LCJuYmYiOjE1ODU5MTIxNTksInByaXZhdGVDbGFpbXMiOnsiY29udGV4dCI6IkFBQUFBQUFBQUFDcDdGMzJHN210aFRzeUV6LzJWWWJiS1FFQUFBQUFBQUI0bndVUVdBOTVacWFKQ1NLTFRWOFBLeHhnSWFFckRlc0xYc013VG5IdEFmdW1sVk51TGcyUVg2L2RDZE1wYXAzdFhHK0JKeVNQeU93cU44Mmg1RHlKV2xwSUZFRlprV0dNczRBKzhJcTErZkxrT0xxYmtyYW5LdjkyYmNRMVNJRWdMVCtkclNqcDFoY011cjVZYzA5ek1IbmFPU3NSdmlMczYxYWRuQXFKSlFhZVMrUkdla1lMRFBqeEZkWFJQTjZ2dldOTFBZUFByMHIydC8vZ01BSXU3cDl5YUxJY3V2cUoxY1dMUkZmdy9XZHUzNG8vbWFkaEI2bTJUNllnZzRHSU5DMEplUW05eWxTK2ZodEFnYitBeDlLUis2ZHZvaUdSQ3o4N0I0SytoRlpXdDhTQlRIM2V1Zk11KzRVQ1ZHVEtYOUhaRmVnK09nbHcrVVBWb2JXM3Z5REsxYUVFajJVMXlUY1NkSlF2ejFOaW1QZ0E0bk5HVjhDQ2FybjBQUGMxWDVBcmZJRmxvaGc9IiwiY29uc2VudFRva2VuIjpudWxsLCJkZXZpY2VJZCI6ImFtem4xLmFzay5kZXZpY2UuQUVFRFVGRDVSQ0tKRU4zUzJIQTVZUlROV1RVWTdOTFNMV0FGV0xRRFVVWjRWV09QM01VSDM1SkFGSEYyT1ZXU0tCT1lXUUI3WkY0NU5EM1VDU0lYR1hGR0lFMlFSRTMyQU1DTkVXTlJRQllOQVZQVk9GMlVaV1M3TFlXSFFaTlZIQ05PM1JJUURRRFVRVDRLRlVERURMNlcyQVNOWkhKWk1SQVQyNFE3VlhWUkpGV0hORjZUTyIsInVzZXJJZCI6ImFtem4xLmFzay5hY2NvdW50LkFHQlZKQ1hKVDVEWFlZT0lXRzZPNlFPQ1pRUVg0QjRRQjdXTTdUUlNUV1k0S0o0TkdFT001QjJOTFRWTDRFUTdCTUhTSkRTNk9PWTdJSktKUUVKUkRRQUtBRU5WSkZGUVVQN1NUWU9GVkpBRkhFVkJQRUNXWVZNVFBSNE82SUNCTVJBRlJISzdKWUdSUTdGRjNBRE5WTjZZNE5DTVlZMjRMVllRN1dBTEVRUVRZSkhWT0ZYQkNVQVM2UEhJT05ZRzY3Sk1ERTZOWTU0WU1PSSJ9fQ.glY8jLAi3zFd-g2hmzyRvCOArgEOZiDMcK4Lpc9edEaOrlLy0RmLAuNgCq3NYx7V1JdGe-ZKPcB0X05CMBuE_1TF3aUUJUpJ2rmGm78pAJ2NJAD1GJBympJt9JpLsqveND_POq64-vYZtujs8NpioNfbT2tW22LrNcXEsNelCljkfPlT-EgJd42kS_a30O8pfxhUN3p_qpPPXbher7pVwEdfD-nUtdNLZ3nL72OipSF5q59k5TU0-rzb4OLYU7Pm6zPe0-1bRsvWdRVRwcIdVWkja_5056HUWbXpmT84CuEexwyBb3sp2BONrcg4eSNRIa6eR_EYoaBL-DqWN3MDeQ"
                ],
                "Viewport" => [
                    "experiences" => [
                        [
                            "arcMinuteWidth" => 246,
                            "arcMinuteHeight" => 144,
                            "canRotate" => false,
                            "canResize" => false
                        ]
                    ],
                    "shape" => "RECTANGLE",
                    "pixelWidth" => 1024,
                    "pixelHeight" => 600,
                    "dpi" => 160,
                    "currentPixelWidth" => 1024,
                    "currentPixelHeight" => 600,
                    "touch" => [
                        "SINGLE"
                    ],
                    "video" => [
                        "codecs" => [
                            "H_264_42",
                            "H_264_41"
                        ]
                    ]
                ],
                "Viewports"=> [
                    [
                        "type" => "APL",
                        "id"=> "main",
                        "shape"=> "RECTANGLE",
                        "dpi"=> 160,
                        "presentationType"=> "STANDARD",
                        "canRotate"=> false,
                        "configuration"=> [
                            "current"=> [
                                "video"=> [
                                    "codecs"=> [
                                        "H_264_42",
                                        "H_264_41"
                                    ]
                                ],
                                "size"=> [
                                    "type"=> "DISCRETE",
                                    "pixelWidth"=> 1024,
                                    "pixelHeight"=> 600
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "request"=> [
                "type"=> "IntentRequest",
                "requestId"=> "amzn1.echo-api.request.c7b4e90a-1b84-4a8a-8fd7-9de89c4de824",
                "timestamp"=> "2020-04-03T11:09:19Z",
                "locale"=> "en-US",
                "intent"=> [
                    "name"=> "Matches",
                    "confirmationStatus"=> "NONE",
                    "slots"=> [
                        "Favorite"=> [
                            "name"=> "Favorite",
                            "confirmationStatus"=> "NONE"
                        ],
                        "CurrentLeague"=> [
                            "name"=> "CurrentLeague",
                            "confirmationStatus"=> "NONE"
                        ],
                        "TeamName"=> [
                            "name"=> "TeamName",
                            "value"=> "Liverpool",
                            "resolutions"=> [
                                "resolutionsPerAuthority"=> [
                                    [
                                        "authority"=> "amzn1.er-authority.echo-sdk.amzn1.ask.skill.05566b87-785f-42c0-a825-9e7e1537fb6a.TeamName",
                                        "status"=> [
                                            "code"=> "ER_SUCCESS_MATCH"
                                        ],
                                        "values"=> [
                                            [
                                                "value"=> [
                                                "name"=> "40",
                                                    "id"=> "d645920e395fedad7bbbed0eca3fe2e0"
                                                ]
                                            ],
                                            [
                                                "value"=> [
                                                "name"=> "1847",
                                                    "id"=> "82cadb0649a3af4968404c9f6031b233"
                                                ]
                                            ],
                                            [
                                                "value"=> [
                                                "name"=> "8669",
                                                    "id"=> "1fb36c4ccf88f7e67ead155496f02338"
                                                ]
                                            ],
                                            [
                                                "value"=> [
                                                "name"=> "7196",
                                                    "id"=> "fe5e7cb609bdbe6d62449d61849c38b0"
                                                ]
                                            ],
                                            [
                                                "value"=> [
                                                "name"=> "7630",
                                                    "id"=> "fbaafc6ec0f0e70f1472122178b4a1a1"
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "confirmationStatus"=> "NONE",
                            "source"=> "USER"
                        ]
                    ]
                ]
            ]
        ];
    }
}
