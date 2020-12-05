<?php


namespace Convo\Core\Adapters\Dialogflow;


use Convo\Core\IConvoServiceLanguageMapper;

class DialogflowLanguageMapper implements IConvoServiceLanguageMapper
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
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
                return IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH;
            case IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN:
                return $locale;
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
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN:
            case IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN:
                return [$locale];
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
        return self::getDefaultLocale($locale);
    }

    /**
     * @param $locale
     * @return array
     * @throws \Exception
     */
    public static function getSupportedLocalesFromExternalLocale($locale)
    {
        return self::getSupportedLocalesByLocale($locale);
    }

    /**
     * @param $locales
     * @return string
     * @throws \Exception
     */
    public static function getDefaultLocaleFromExternalSupportedLocales($locales)
    {
        return $locales[0];
    }
}
