<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class AmazonSkillManifest implements LoggerAwareInterface
{
    const ALLOWED_REGIONS = [
        'NA', 'EU', 'FE'
    ];

    const ALLOWED_LOCALES = [
        'de-DE', 'en-AU', 'en-CA', 'en-GB', 'en-IN', 'en-US', 'es-ES', 'es-MX', 'es-US', 'fr-CA', 'fr-FR', 'hi-IN', 'it-IT', 'ja-JP', 'pt-BR'
    ];

    const DISTRIBUTION_MODE_PRIVATE = 'PRIVATE';
    const DISTRIBUTION_MODE_PUBLIC  = 'PUBLIC';

    const CERTIFICATE_TYPE_SELF_SIGNED = 'SelfSigned';
	const CERTIFICATE_TYPE_WILDCARD = 'Wildcard';
	const CERTIFICATE_TYPE_TRUSTED = 'Trusted';

	const CATEGORY_ALARMS_AND_CLOCKS = "ALARMS_AND_CLOCKS";
	const CATEGORY_ASTROLOGY = "ASTROLOGY";
	const CATEGORY_BUSINESS_AND_FINANCE = "BUSINESS_AND_FINANCE";
	const CATEGORY_CALCULATORS = "CALCULATORS";
	const CATEGORY_CALENDARS_AND_REMINDERS = "CALENDARS_AND_REMINDERS";
	const CATEGORY_CHILDRENS_EDUCATION_AND_REFERENCE = "CHILDRENS_EDUCATION_AND_REFERENCE";
	const CATEGORY_CHILDRENS_GAMES = "CHILDRENS_GAMES";
	const CATEGORY_CHILDRENS_MUSIC_AND_AUDIO = "CHILDRENS_MUSIC_AND_AUDIO";
	const CATEGORY_CHILDRENS_NOVELTY_AND_HUMOR = "CHILDRENS_NOVELTY_AND_HUMOR";
	const CATEGORY_COMMUNICATION = "COMMUNICATION";
	const CATEGORY_CONNECTED_CAR = "CONNECTED_CAR";
	const CATEGORY_COOKING_AND_RECIPE = "COOKING_AND_RECIPE";
	const CATEGORY_CURRENCY_GUIDES_AND_CONVERTERS = "CURRENCY_GUIDES_AND_CONVERTERS";
	const CATEGORY_DATING = "DATING";
	const CATEGORY_DELIVERY_AND_TAKEOUT = "DELIVERY_AND_TAKEOUT";
	const CATEGORY_DEVICE_TRACKING = "DEVICE_TRACKING";
	const CATEGORY_EDUCATION_AND_REFERENCE = "EDUCATION_AND_REFERENCE";
	const CATEGORY_EVENT_FINDERS = "EVENT_FINDERS";
	const CATEGORY_EXERCISE_AND_WORKOUT = "EXERCISE_AND_WORKOUT";
	const CATEGORY_FASHION_AND_STYLE = "FASHION_AND_STYLE";
	const CATEGORY_FLIGHT_FINDERS = "FLIGHT_FINDERS";
	const CATEGORY_FRIENDS_AND_FAMILY = "FRIENDS_AND_FAMILY";
	const CATEGORY_GAME_INFO_AND_ACCESSORY = "GAME_INFO_AND_ACCESSORY";
	const CATEGORY_GAMES = "GAMES";
	const CATEGORY_HEALTH_AND_FITNESS = "HEALTH_AND_FITNESS";
	const CATEGORY_HOTEL_FINDERS = "HOTEL_FINDERS";
	const CATEGORY_KNOWLEDGE_AND_TRIVIA = "KNOWLEDGE_AND_TRIVIA";
	const CATEGORY_MOVIE_AND_TV_KNOWLEDGE_AND_TRIVIA = "MOVIE_AND_TV_KNOWLEDGE_AND_TRIVIA";
	const CATEGORY_MOVIE_INFO_AND_REVIEWS = "MOVIE_INFO_AND_REVIEWS";
	const CATEGORY_MOVIE_SHOWTIMES = "MOVIE_SHOWTIMES";
	const CATEGORY_MUSIC_AND_AUDIO_ACCESSORIES = "MUSIC_AND_AUDIO_ACCESSORIES";
	const CATEGORY_MUSIC_AND_AUDIO_KNOWLEDGE_AND_TRIVIA = "MUSIC_AND_AUDIO_KNOWLEDGE_AND_TRIVIA";
	const CATEGORY_MUSIC_INFO_REVIEWS_AND_RECOGNITION_SERVICE = "MUSIC_INFO_REVIEWS_AND_RECOGNITION_SERVICE";
	const CATEGORY_NAVIGATION_AND_TRIP_PLANNER = "NAVIGATION_AND_TRIP_PLANNER";
	const CATEGORY_NEWS = "NEWS";
	const CATEGORY_NOVELTY = "NOVELTY";
	const CATEGORY_ORGANIZERS_AND_ASSISTANTS = "ORGANIZERS_AND_ASSISTANTS";
	const CATEGORY_PETS_AND_ANIMAL = "PETS_AND_ANIMAL";
	const CATEGORY_PODCAST = "PODCAST";
	const CATEGORY_PUBLIC_TRANSPORTATION = "PUBLIC_TRANSPORTATION";
	const CATEGORY_RELIGION_AND_SPIRITUALITY = "RELIGION_AND_SPIRITUALITY";
	const CATEGORY_RESTAURANT_BOOKING_INFO_AND_REVIEW = "RESTAURANT_BOOKING_INFO_AND_REVIEW";
	const CATEGORY_SCHOOLS = "SCHOOLS";
	const CATEGORY_SCORE_KEEPING = "SCORE_KEEPING";
	const CATEGORY_SELF_IMPROVEMENT = "SELF_IMPROVEMENT";
	const CATEGORY_SHOPPING = "SHOPPING";
	const CATEGORY_SMART_HOME = "SMART_HOME";
	const CATEGORY_SOCIAL_NETWORKING = "SOCIAL_NETWORKING";
	const CATEGORY_SPORTS_GAMES = "SPORTS_GAMES";
	const CATEGORY_SPORTS_NEWS = "SPORTS_NEWS";
	const CATEGORY_STREAMING_SERVICE = "STREAMING_SERVICE";
	const CATEGORY_TAXI_AND_RIDESHARING = "TAXI_AND_RIDESHARING";
	const CATEGORY_TO_DO_LISTS_AND_NOTES = "TO_DO_LISTS_AND_NOTES";
	const CATEGORY_TRANSLATORS = "TRANSLATORS";
	const CATEGORY_TV_GUIDES = "TV_GUIDES";
	const CATEGORY_UNIT_CONVERTERS = "UNIT_CONVERTERS";
	const CATEGORY_WEATHER = "WEATHER";
	const CATEGORY_WINE_AND_BEVERAGE = "WINE_AND_BEVERAGE";
	const CATEGORY_ZIP_CODE_LOOKUP = "ZIP_CODE_LOOKUP";

    /**
     * @var array
     **/
    private $_manifest;

	private $_useEvents;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Crete a new manifest object. You may pass an already existing manifest to overwrite defaults.
     * @param string|array $manifest Existing manifest to use. You may pass either a string which will be treated as JSON, or a key => value map
     * @return void
     * @throws Exception
     */
    public function __construct($manifest = null)
    {
    	$this->_useEvents = false;
        $this->_logger = new NullLogger();

        $default_manifest = $this->_getDefaultManifest();

        if ($manifest) {
            if (!is_array($manifest) && is_string($manifest)) {
                // JSON
                $manifest = json_decode($manifest, true);
            }

            $this->_manifest = array_replace_recursive(
                $default_manifest,
                $manifest
            );
        } else {
            $this->_manifest = $default_manifest;
        }
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Get the current manifest state as an array
     * @param boolean $asJson Should the value be returned as a JSON string instead? Default `false`.
     * @return array|string
     */
    public function getManifest($asJson = false)
    {
    	$ret = $this->_manifest;

		if (isset($ret['publishingInformation']['isAvailableWorldwide']) && $ret['publishingInformation']['isAvailableWorldwide'] === true)
		{
			unset ($ret['publishingInformation']['distributionCountries']);
		}

		if (isset($ret['events']) && !$this->_useEvents) {
			unset ($ret['events']);
		}

        return $asJson ? json_encode($ret) : $ret;
    }

    // LOCALES

    /**
     * Set the skill's summary in a locale(s).
     * @param array|string $locales If this is a string, then the summary will be set for just that locale. If it's a simple array of strings, then the same summary will be set for each locale specified. If it's a key => value pair, then each key is treated as a locale name and the value is the summary for that locale. Keep in mind that if this is the case, then the $summary parameter will be ignored.
     * @param string $summary Summary to set for the skill. This will be ignored if the $locales parameter is a key => value array.
     * @return self
     */
    public function setSummary($locales, $summary)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $locale => $locale_summary) {
            $l = is_numeric($locale) ? $locale_summary : $locale;
            $s = is_numeric($locale) ? $summary : $locale_summary;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['summary'] = $s;
        }

        return $this;
    }

    /**
     * Set the skill's example phrases in the specified locale.
     * @param array|string $locales Set the locale or locales for which to set the example phrases. If this is a string, then the example phrases will be set for only that specific locale. If it is a simple array of strings, then the specified $phrases will be set for all of the listed locales. If this is a key => value map, then the keys will be treated as locale names and the values will be treated as the example phraeses. In the last case, the value of the $phrases parameter will be ignored.
     * @param array|string $phrases Example phrases to set for a locale/locales. This value will be ignored if $locales is a key => value map.
     * @return self
     */
    public function setExamplePhrases($locales, $phrases)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $locale => $locale_phrases) {
            $l = is_numeric($locale) ? $locale_phrases : $locale;
            $ps = is_numeric($locale) ? $phrases : $locale_phrases;

            if (!is_array($ps)) {
                $ps = [$ps];
            }

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['examplePhrases'] = $ps;
        }

        return $this;
    }

    /**
     * Sets keywords for the designated locale. Locales may be a string, which is considered a single locale; an array of strings, which will set the provided summary for all locales; or a key => value map, in which case the keys will be treated as locale names, and the value for each locale will be treated as keyword(s) for that locale.
     * @param array|string $locales The locale(s) for which to set the keywords.
     * @param mixed $keywords Keywords to set. This value will be ignored if $locales is a key => value map
     * @return self
     * @throws Exception
     */
    public function setKeywords($locales, $keywords)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $kw = is_numeric($key) ? $keywords : $value;

            if (!is_array($kw)) {
                $kw = [$kw];
            }

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['keywords'] = $kw;
        }

        return $this;
    }

    /**
     * Sets the small icon URI for the given locale(s)
     * @param array|string $locales The locale or locales for which to set the small icon URI. If this is a string, then the icon URI will be set for that specific locale. If this is a simple array of strings, then the same icon URI will be set for all the specified locales. If this is a key => value map, then each key will be treated as a locale, and each value will be the URI for that specific locale. In that case, the value for $smallIconUri will be ignored.
     * @param array|string $smallIconUri The small icon URI to set. If the $locales parameter is a key => value map, then this value will be ignored.
     * @return self
     * @throws Exception Invalid locales will not be admitted.
     */
    public function setSmallIconUri($locales, $smallIconUri)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $siu = is_numeric($key) ? $smallIconUri : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['smallIconUri'] = $siu;
        }

        return $this;
    }

    /**
     * Sets the large icon URI for the given locale(s)
     * @param array|string $locales The locale or locales for which to set the large icon URI. If this is a string, then the icon URI will be set for that specific locale. If this is a simple array of strings, then the same icon URI will be set for all the specified locales. If this is a key => value map, then each key will be treated as a locale, and each value will be the URI for that specific locale. In that case, the value for $largeIconUri will be ignored.
     * @param array|string $largeIconUri The large icon URI to set. If the $locales parameter is a key => value map, then this value will be ignored.
     * @return self
     * @throws Exception Invalid locales will not be admitted.
     */
    public function setLargeIconUri($locales, $largeIconUri)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $liu = is_numeric($key) ? $largeIconUri : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['largeIconUri'] = $liu;
        }

        return $this;
    }

    /**
     * Set the skill's name in a locale(s).
     * @param array|string $locales If this is a string, then the name will be set for just that locale. If it's a simple array of strings, then the same name will be set for each locale specified. If it's a key => value pair, then each key is treated as a locale name and the value is the name for that locale. Keep in mind that if this is the case, then the $name parameter will be ignored.
     * @param string $name Name to set for the skill. This will be ignored if the $locales parameter is a key => value array.
     * @return self
     */
    public function setName($locales, $name)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $n = is_numeric($key) ? $name : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['name'] = $n;
        }

        return $this;
    }

    /**
     * Set a description for the specified locale(s). You may provide $locales as either a string, which will be considered a single locale; an array of strings, which will be treated as a series of locales for which to set the description; or a key => value map, in which case the keys will be treated as locale names, and the values will be the descriptions for each respective locale.
     * @param array|string $locales The locale(s) for which to set the description. If this is a key => value map, then the $description parameter will be ignored.
     * @param string $description The description to set for the given locale(s). This is ignored if $locales is a key => value map.
     * @return self
     * @throws Exception
     */
    public function setDescription($locales, $description)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $d = is_numeric($key) ? $description : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['description'] = $d;
        }

        return $this;
    }
    /**
     * Set what's new description for the specified locale(s). You may provide $locales as either a string, which will be considered a single locale; an array of strings, which will be treated as a series of locales for which to set the description; or a key => value map, in which case the keys will be treated as locale names, and the values will be the descriptions for each respective locale.
     * @param array|string $locales The locale(s) for which to set the description. If this is a key => value map, then the $description parameter will be ignored.
     * @param string $description The what's new description to set for the given locale(s). This is ignored if $locales is a key => value map.
     * @return self
     * @throws Exception
     */
    public function setWhatsNew($locales, $description)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $d = is_numeric($key) ? $description : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['publishingInformation']['locales'][$l]['updatesDescription'] = $d;
        }

        return $this;
    }

    /**
     * Set whether the skill should be available worldwide.
     * @param boolean $available Available or not
     * @return self
     */
    public function setIsAvailableWorldwide($available)
    {
        $this->_manifest['publishingInformation']['isAvailableWorldwide'] = $available;

        return $this;
    }

    /**
     * Set testing instructions for the skill.
     * @param string $instructions Instructions on how to test the skill
     * @return self
     */
    public function setTestingInstructions($instructions)
    {
        $this->_manifest['publishingInformation']['testingInstructions'] = $instructions;

        return $this;
    }

    /**
     * Set the category for the skill
     * @param string $category The skill's category
     * @return self
     */
    public function setCategory($category)
    {
        $this->_manifest['publishingInformation']['category'] = $category;

        return $this;
    }

    /**
     * Set countries in which this skill is available. This call is ignored if the skill is set to be available worldwide.
     * @param string|array $countries Coutry/countries to set distribution availability
     * @return self
     */
    public function setDistributionCountries($countries)
    {
        $worldwide = $this->_manifest['publishingInformation']['isAvailableWorldwide'] ?? false;

        if ($worldwide) {
            $this->_logger->warning('Going to ignore setting distribution countries because the skill is available worldwide.');
            return $this;
        }

        if (!is_array($countries)) {
            $countries = [$countries];
        }

        $this->_manifest['publishingInformation']['distributionCountries'] = $countries;

        return $this;
    }

    /**
     * Sets the distribution mode for the skill.
     * @param string $mode Distribution mode for the skill. Allowed values are PRIVATE and PUBLIC
     * @return self
     * @throws Exception Invalid mode given
     */
    public function setDistributionMode($mode)
    {
        $mode = strtoupper(trim($mode));

        if ($mode !== self::DISTRIBUTION_MODE_PRIVATE && $mode !== self::DISTRIBUTION_MODE_PUBLIC) {
            throw new \Exception("Invalid distribution mode [$mode]. Please specify one of \"PRIVATE\" or \"PUBLIC\"");
        }

        $this->_manifest['publishingInformation']['distributionMode'] = $mode;

        return $this;
    }
    /**
     * Sets Opt in to automated locale distribution.
     * @param string $isActive
     * @param string $defaultLocale
     * @return self
     */
    public function setOptInToAutomaticLocaleDistribution($isActive, $defaultLocale)
    {
        $this->_logger->info("Incoming active value [" . $isActive . "]");

        if ($isActive === true) {
            $language = explode('-', $defaultLocale)[0];

            $this->_manifest['publishingInformation']['automaticDistribution']['isActive'] = $isActive;
            $this->_manifest['publishingInformation']['automaticDistribution']['sourceLocaleForLanguages'][0]['language'] = $language;
            $this->_manifest['publishingInformation']['automaticDistribution']['sourceLocaleForLanguages'][0]['sourceLocale'] = $defaultLocale;
        }

        return $this;
    }

    // PRIVACY AND COMPLIANCE

    /**
     * Set whether the skill allows purchases
     * @param boolean $allows `true` if the skill allows purchases, `false` otherwise
     * @return self
     */
    public function allowsPurchases($allows)
    {
        return $this->_setPrivacySetting('allowsPurchases', $allows);
    }

    /**
     * Set whether the skill uses personal information that could potentially identify the end user, e.g. first/last name, email, etc.
     * @param boolean $uses `true` if the skill collects and/or uses personal information, `false` otherwise
     * @return self
     */
    public function usesPersonalInfo($uses)
    {
        return $this->_setPrivacySetting('usesPersonalInfo', $uses);
    }

    /**
     * Set whether the skill is meant for children under the age of 13.
     * @param boolean $bool `true` if the skill is directed towards children, `false` otherwise.
     * @return self
     */
    public function isChildDirected($bool)
    {
        return $this->_setPrivacySetting('isChildDirected', $bool);
    }

    /**
     * Sets whether the skill is export compliant, i.e. whether it can be exported to other regions
     * @param boolean $bool `true` if the skill is export compliant, `false` otherwise
     * @return self
     */
    public function isExportCompliant($bool)
    {
        return $this->_setPrivacySetting('isExportCompliant', $bool);
    }

    /**
     * Sets whether the skill contains ads
     * @param boolean $contains `true` if ads play in the skill, `false` otherwise
     * @return self
     */
    public function containsAds($contains)
    {
        return $this->_setPrivacySetting('containsAds', $contains);
    }

    /**
     * Sets the privacy policy url for the specified locale. Note that you may only provide a string for the `locale` parameter because every locale MUST have a separate Privacy Policy URL.
     * @param array $locales Locales for which to set the privacy policy URL
     * @param string $url Publicly accessible URL to the privacy policy for the specified locale.
     * @return self
     * @throws Exception Invalid locale provided
     */
    public function setPrivacyPolicyUrl($locales, $url)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $d = is_numeric($key) ? $url : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['privacyAndCompliance']['locales'][$l]['privacyPolicyUrl'] = $d;
        }

        return $this;
    }

    /**
     * Set the Terms of Use URL for the specified locale. Note that you may only provide a string for the `locale` paramter, because every locale MUST have its own, separate URL for the Terms of Use.
     * @param array $locales Locales for which to set the Terms of Use URL
     * @param string $url Publicly accessible URL with the Terms of Use for the specified locale
     * @return $this
     * @throws Exception Invalid locale given
     */
    public function setTermsOfUseUrl($locales, $url)
    {
        if (!is_array($locales)) {
            $locales = [$locales];
        }

        foreach ($locales as $key => $value) {
            $l = is_numeric($key) ? $value : $key;
            $d = is_numeric($key) ? $url : $value;

            $this->_checkLocaleIsValid($l);

            $this->_manifest['privacyAndCompliance']['locales'][$l]['termsOfUseUrl'] = $d;
        }

        return $this;
    }

    // APIS

    /**
     * Sets the endpoint URI for the skill. Specifying regional endpoints will override this URI if the request is initiated from a specific region.
     * @param string $endpoint Endpoint URI for the skill
     * @return self
     */
    public function setGlobalEndpoint($endpoint)
    {
        $this->_manifest['apis']['custom']['endpoint']['uri'] = $endpoint;

        return $this;
    }

    /**
     * Sets the interfaces for the skill.
     * @param array $interfaces Supported interfaces of the Skill
     * @return self
     */
    public function setInterfaces($interfaces)
    {
        $interfacesReadyToSet = [];
        foreach ($interfaces as $interface) {
            array_push($interfacesReadyToSet, ["type" => $interface]);
        }
        $this->_manifest['apis']['custom']['interfaces'] = $interfacesReadyToSet;

        return $this;
    }

    /**
     * Clears all interfaces for the skill.
     * @return self
     */
    public function clearInterfaces()
    {
        unset($this->_manifest['apis']['custom']['interfaces']);

        return $this;
    }

    public function setGlobalCertificateType($certificateType)
	{
		$this->_manifest['apis']['custom']['endpoint']['sslCertificateType'] = $certificateType;

		return $this;
	}

    public function setRegionEndpoint($region, $endpoint)
    {
        $this->_checkRegionIsValid($region);

        $this->_manifest['apis']['custom']['regions'][$region]['endpoint']['uri'] = $endpoint;

        return $this;
    }

    public function setRegionCertificateType($region, $certificateType)
	{
		$this->_checkRegionIsValid($region);

		$this->_manifest['apis']['custom']['regions'][$region]['endpoint']['sslCertificateType'] = $certificateType;

		return $this;
	}

	// EVENTS
	public function setUseEvents($bool)
	{
		$this->_useEvents = $bool;
	}

	// PRIVACY SETTINGS
    private function _setPrivacySetting($setting, $bool)
    {
        $this->_manifest['privacyAndCompliance'][$setting] = $bool;

        return $this;
    }

	/**
	 * Sets permissions for the skill.
	 * @param array $permissions Permissions of the Skill
	 * @return self
	 */
	public function setPermissions($permissions)
	{
		$permissionsReadyToSet = [];
		foreach ($permissions as $permission) {
			array_push($permissionsReadyToSet, ['name' => $permission]);
		}
		$this->_manifest['permissions'] = $permissionsReadyToSet;

		return $this;
	}

	// UTIL
	private function _checkRegionIsValid($region)
    {
        if (!in_array($region, self::ALLOWED_REGIONS)) {
            throw new \Exception("Invalid region [$region]");
        }
    }

    private function _checkLocaleIsValid($locale)
    {
        if (!in_array($locale, self::ALLOWED_LOCALES)) {
            throw new \Exception("Invalid locale [$locale]");
        }
    }

    private function _getDefaultManifest()
    {
        $path = realpath(__DIR__.'/default_skill_manifest.json');

        if (($data = file_get_contents($path)) === false) {
            throw new \Exception("Couldn't open path [$path].");
        }

        return json_decode($data, true);
    }
}
