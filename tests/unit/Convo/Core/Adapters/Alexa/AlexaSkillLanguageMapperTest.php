<?php


use Convo\Core\Adapters\Alexa\AlexaSkillLanguageMapper;
use Convo\Core\Util\Test\ConvoTestCase;

class AlexaSkillLanguageMapperTest extends ConvoTestCase
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
        $this->_logger->info("Convert from Convo Language code [" . $convoLanguageCode . "] to Amazon Language Code [" . AlexaSkillLanguageMapper::getDefaultLocale($convoLanguageCode) . "]" );
        $this->assertEquals($expectedExternalLanguageCode, AlexaSkillLanguageMapper::getDefaultLocale($convoLanguageCode));
    }

    public function testFoundLanguageByExternalLocale() {
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_GERMAN, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("de-DE"));
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_US, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("en-US"));
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_IN, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("en-IN"));
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_GB, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("en-GB"));
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_CA, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("en-CA"));
        $this->assertEquals(AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_AU, AlexaSkillLanguageMapper::getDefaultLocaleFromExternalLocale("en-AU"));
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
        AlexaSkillLanguageMapper::getDefaultLocale($languageCode);
    }

    public function testDefaultLocaleBySupportedLocales() {
        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-US', 'en-CA', 'en-IN', 'en-GB', 'en-AU'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_US,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-US'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_CA,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-CA'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_IN,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-IN'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_GB,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-GB'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_ENGLISH_AU,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['en-AU'])
        );

        $this->assertEquals(
            AlexaSkillLanguageMapper::CONVO_SERVICE_GERMAN,
            AlexaSkillLanguageMapper::getDefaultLocaleFromExternalSupportedLocales(['de-DE'])
        );
    }

    /**
     * Currently supported languages
     * @return string[][]
     */
    public function languageCodesOkProvider()
    {
        return [
            ['en', 'en-US'],
            ['en-US', 'en-US'],
            ['en-AU', 'en-AU'],
            ['en-GB', 'en-GB'],
            ['en-IN', 'en-IN'],
            ['de', 'de-DE'],
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
