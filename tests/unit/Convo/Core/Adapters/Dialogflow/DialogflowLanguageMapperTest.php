<?php

use Convo\Core\Adapters\Dialogflow\DialogflowLanguageMapper;
use Convo\Core\Util\Test\ConvoTestCase;

class DialogflowLanguageMapperTest extends ConvoTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider languageCodesOkProvider
     * @param $convoLanguageCode
     * @param $expectedExternalLanguageCode
     * @throws Exception
     */

    public function testFoundLanguageByProvidedLocale($convoLanguageCode, $expectedExternalLanguageCode) {
        $this->_logger->info("Convert from Convo Language code [" . $convoLanguageCode . "] to Amazon Language Code [" . DialogflowLanguageMapper::getDefaultLocale($convoLanguageCode) . "]" );
        $this->assertEquals($expectedExternalLanguageCode, DialogflowLanguageMapper::getDefaultLocale($convoLanguageCode));
    }

    public function testFoundLanguageByExternalLocale() {
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_GERMAN, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("de"));
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("en-US"));
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("en-IN"));
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("en-GB"));
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("en-CA"));
        $this->assertEquals(DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH, DialogflowLanguageMapper::getDefaultLocaleFromExternalLocale("en-AU"));
    }

    /**
     * @dataProvider unsupportedLanguageCodesProvider
     * @param $languageCode
     * @throws Exception
     */
    public function testNotFoundLanguageByProvidedLocale($languageCode) {
        $this->_logger->info('Unsupported language code [' . $languageCode . "]");
        $this->expectException(Exception::class);
        $this->expectDeprecationMessage("Unsupported locale [". $languageCode ."]");
        DialogflowLanguageMapper::getDefaultLocale($languageCode);
    }

    public function testDefaultLocaleBySupportedLocales() {
        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH_US,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-US'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH_CA,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-CA'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH_IN,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-IN'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH_GB,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-GB'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_ENGLISH_AU,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-AU'])
        );

        $this->assertEquals(
            DialogflowLanguageMapper::CONVO_SERVICE_GERMAN,
            DialogflowLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['de'])
        );
    }

    /**
     * Currently supported languages
     * @return string[][]
     */
    public function languageCodesOkProvider()
    {
        return [
            ['en', 'en'],
            ['en-US', 'en'],
            ['en-AU', 'en'],
            ['en-GB', 'en'],
            ['en-IN', 'en'],
            ['de', 'de'],
        ];
    }

    /**
     * @url See https://cloud.google.com/dialogflow/es/docs/reference/language for reference.
     * @return string[][]
     */
    public function unsupportedLanguageCodesProvider()
    {
        return [
            ['zh-HK'],
            ['zh-CN'],
            ['zh-TW'],
            ['da'],
            ['fr'],
            ['fr-CA'],
            ['fr-FR'],
            ['hi'],
            ['id'],
            ['it'],
            ['ja'],
            ['ko'],
            ['no'],
            ['pl'],
            ['pt'],
            ['pt-BR'],
            ['ru'],
            ['es'],
            ['es-419'],
            ['es-ES'],
            ['sv'],
            ['th'],
            ['tr'],
            ['uk']
        ];
    }
}
