<?php


use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Fbm\FacebookMessengerCommandRequest;
use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest;
use Convo\Core\Adapters\Viber\ViberCommandRequest;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Util\Test\ConvoTestCase;

class RequestParamsScopeTest extends ConvoTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getAmazonRequest
     * @param $getAmazonRequest
     * @throws Exception
     */
    public function testRequestParamsScopeAmazon($getAmazonRequest) {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, 'test', $getAmazonRequest);
        $amazonCommandRequest->init();

        foreach($this->_getGeneratedHashKeysParamsScopeAmazon() as $key => $value) {
            $requestParamsScope = new \Convo\Core\Params\RequestParamsScope($amazonCommandRequest, $key, IServiceParamsScope::LEVEL_TYPE_SERVICE);
            $keyAsFileName = $requestParamsScope->getKey();
            $this->_logger->info("Key as filename by Amazon " . $keyAsFileName);
            $this->assertEquals($value, $keyAsFileName);
        }
    }

    /**
     * @dataProvider getDialogflowRequest
     * @param $getDialogflowRequest
     * @throws Exception
     */
    public function testRequestParamsScopeDialogflow($getDialogflowRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest('test', $getDialogflowRequest);
        $dialogflowCommandRequest->init();

        foreach($this->_getGeneratedHashKeysParamsScopeDialogflow() as $key => $value) {
            $requestParamsScope = new \Convo\Core\Params\RequestParamsScope($dialogflowCommandRequest, $key, IServiceParamsScope::LEVEL_TYPE_SERVICE);
            $keyAsFileName = $requestParamsScope->getKey();
            $this->_logger->info("Key as filename by Dialogflow " . $keyAsFileName);
            $this->assertEquals($value, $keyAsFileName);
        }
    }

    /**
     * @dataProvider getFacebookMessengerRequest
     * @param $getFacebookMessengerRequest
     * @throws Exception
     */
    public function testRequestParamsScopeFacebookMessenger($getFacebookMessengerRequest) {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, 'test', $getFacebookMessengerRequest);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            foreach($this->_getGeneratedHashKeysParamsScopeFacebookMessenger() as $key => $value) {
                $requestParamsScope = new \Convo\Core\Params\RequestParamsScope($facebookMessengerCommandRequest, $key, IServiceParamsScope::LEVEL_TYPE_SERVICE);
                $keyAsFileName = $requestParamsScope->getKey();
                $this->_logger->info("Key as filename by Facebook Messenger " . $keyAsFileName);
                $this->assertEquals($value, $keyAsFileName);
            }
        }
    }

    /**
     * @dataProvider getViberRequest
     * @param $getViberRequest
     * @throws Exception
     */
    public function testRequestParamsScopeViber($getViberRequest) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger,'test', $getViberRequest);
        $viberCommandRequest->init();

        foreach($this->_getGeneratedHashKeysParamsScopeViber() as $key => $value) {
            $requestParamsScope = new \Convo\Core\Params\RequestParamsScope($viberCommandRequest, $key, IServiceParamsScope::LEVEL_TYPE_SERVICE);
            $keyAsFileName = $requestParamsScope->getKey();
            $this->_logger->info("Key as filename by Viber " . $keyAsFileName);
            $this->assertEquals($value, $keyAsFileName);
        }
    }

    public function getAmazonRequest() {
        return $this->_establishTestData(__DIR__ . './data/alexa_launch_request_with_display_interface.json');
    }

    public function getDialogflowRequest() {
        return $this->_establishTestData(__DIR__ . './data/dialogflow_get_matches_request.json');
    }

    public function getFacebookMessengerRequest() {
        return $this->_establishTestData(__DIR__ . './data/facebook_messenger_text_command_request.json');
    }

    public function getViberRequest() {
        return $this->_establishTestData(__DIR__ . './data/viber_text_message_request.json');
    }

    private function _getGeneratedHashKeysParamsScopeAmazon() {
        return [
            IServiceParamsScope::SCOPE_TYPE_REQUEST         => 'amzn1askdeviceagcaf5y553lo4e56h67yzbfxzkvo566byumf6cj6yiaffikdlneapurhoqqegpnonw64dnapifljvewqfu7okmx6fnz4mzhjhyu3jd6qqftwk55ubrhtqbr3u66upynhkrrxsgntfodgk3wwp5chgvndysbeeedzyazdwk2uimuhuazcgjfek_amzn1echo_apirequest89f7f95d_50f3_421d_9e93_6ca1cc76865c',
            IServiceParamsScope::SCOPE_TYPE_SESSION         => 'amzn1askdeviceagcaf5y553lo4e56h67yzbfxzkvo566byumf6cj6yiaffikdlneapurhoqqegpnonw64dnapifljvewqfu7okmx6fnz4mzhjhyu3jd6qqftwk55ubrhtqbr3u66upynhkrrxsgntfodgk3wwp5chgvndysbeeedzyazdwk2uimuhuazcgjfek_amzn1askaccountafhtgzjxa476spsejggxem3mflwkmneozulb74srhkoawkmffxdb46gl5oayevc2skbs47oha344c2p4nassddoxsrxo3xj263pnvpbltmndf2gcbgmv6hyxlnvbd7ipiqm2kym5im2pryq6tqaet5iiijsese4viodbtgjzf5gccsfdeeigkif3rvg22k4z2a3w65dlzujknsi_amzn1echo_apisession47ebf73b_f0ea_4d9f_b258_82202df06b57',
            IServiceParamsScope::SCOPE_TYPE_INSTALLATION    => 'amzn1askdeviceagcaf5y553lo4e56h67yzbfxzkvo566byumf6cj6yiaffikdlneapurhoqqegpnonw64dnapifljvewqfu7okmx6fnz4mzhjhyu3jd6qqftwk55ubrhtqbr3u66upynhkrrxsgntfodgk3wwp5chgvndysbeeedzyazdwk2uimuhuazcgjfek_amzn1askaccountafhtgzjxa476spsejggxem3mflwkmneozulb74srhkoawkmffxdb46gl5oayevc2skbs47oha344c2p4nassddoxsrxo3xj263pnvpbltmndf2gcbgmv6hyxlnvbd7ipiqm2kym5im2pryq6tqaet5iiijsese4viodbtgjzf5gccsfdeeigkif3rvg22k4z2a3w65dlzujknsi'
        ];
    }

    private function _getGeneratedHashKeysParamsScopeDialogflow() {
        return  [
            IServiceParamsScope::SCOPE_TYPE_REQUEST         => 'unknown_d51aa889_0c06_47c8_8be4_9d79ed94f5c1_a14fa99c',
            IServiceParamsScope::SCOPE_TYPE_SESSION         => 'unknown_kn_vucetinec_1565962572259_abwpphgyvz4vdam8cypio5uqcuodyj1nxfinbbgdm_ecvyxjhufgjxbtler3_iov4bnp1pobgpzjiyjkki_c6iewfoky18te5ii',
            IServiceParamsScope::SCOPE_TYPE_INSTALLATION    => 'unknown_kn_vucetinec_1565962572259'
        ];
    }

    private function _getGeneratedHashKeysParamsScopeFacebookMessenger() {
        return [
            IServiceParamsScope::SCOPE_TYPE_SESSION         => 'unknown_3608068992554355_102692124812191_3608068992554355',
            IServiceParamsScope::SCOPE_TYPE_INSTALLATION    => 'unknown_3608068992554355_102692124812191'
        ];
    }

    private function _getGeneratedHashKeysParamsScopeViber() {
        return [
            IServiceParamsScope::SCOPE_TYPE_REQUEST         => 'unknown_5469165883537819000',
            IServiceParamsScope::SCOPE_TYPE_SESSION         => 'unknown_test_9ncr2gsfti7sdtdie23q_9ncr2gsfti7sdtdie23q',
            IServiceParamsScope::SCOPE_TYPE_INSTALLATION    => 'unknown_test_9ncr2gsfti7sdtdie23q'
        ];
    }
}
