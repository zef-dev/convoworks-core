<?php


namespace Convo\Core\Adapters\Alexa;


use Convo\Core\IConvoServiceLanguageMapper;

class AlexaSkillLanguageMapper implements IConvoServiceLanguageMapper
{

    /**
     * @param $locale
     * @return string
     * @throws \Exception
     */
    public static function getDefaultLocale($locale)
    {
        switch ($locale) {
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
                return IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US;
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
                return $locale;
            case IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN:
                return 'de-DE';
            default:
                throw new \Exception("Unsupported locale [" . $locale . "]");
        }
    }

    /**
     * @param $locale
     * @return array
     * @throws \Exception
     */
    public static function getSupportedLocalesByLocale($locale)
    {
        switch ($locale) {
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH:
                return [
                    IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN,
                    IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB,
                    IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA,
                    IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU,
                    IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US
                ];
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
                return [$locale];
            case IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN:
                return ['de-DE'];
            default:
                throw new \Exception("Unsupported locale [" . $locale . "]");
        }
    }

    /**
     * @param $locale
     * @return string
     * @throws \Exception
     */
    public static function getDefaultLocaleFromExternalLocale($locale)
    {
        switch ($locale) {
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
                return $locale;
            case 'de-DE' :
                return IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN;
            default:
                throw new \Exception("Unsupported locale [" . $locale . "]");
        }
    }

    /**
     * @param $locale
     * @return array
     * @throws \Exception
     */
    public static function getSupportedLocalesFromExternalLocale($locale)
    {
        switch ($locale) {
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
                return [$locale];
            case 'de-DE':
                return [IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN];
            default:
                throw new \Exception("Unsupported locale [" . $locale . "]");
        }
    }

    /**
     * @param $locales
     * @return string
     * @throws \Exception
     */
    public static function getDefaultLocaleFromExternalSupportedLocales($locales)
    {
        $allEnglish = [
            IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US,
            IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA,
            IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB,
            IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU,
            IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN
        ];

        $locale = $locales[0];
        if (count(array_intersect($allEnglish, $locales)) === 5) {
            $locale = IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH;
        } else if ($locale === 'de-DE') {
            $locale = 'de';
        }

        return $locale;
    }
}
