<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Adapters\Alexa\AmazonSkillManifest;
use Convo\Core\IAdminUser;
use Convo\Core\IConvoServiceLanguageMapper;
use Convo\Core\Rest\RequestInfo;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ConfigurationRestHandler implements \Psr\Http\Server\RequestHandlerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    public function __construct($logger, $httpFactory)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info = new RequestInfo($request);

        $user = $info->getAuthUser();

        if ($info->get() && $route = $info->route('config-options')) {
            return $this->_handleConfigOptionsGet($user);
        }

        throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
    }

    private function _handleConfigOptionsGet(IAdminUser $user)
    {
        $data = [
            'CONVO_SERVICE_LANGUAGES' => [
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH, 'name' => 'English'],
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_GERMAN, 'name' => 'German']
            ],
            'CONVO_SERVICE_LOCALES' => [
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_AU, 'name' => 'English (Australia)', 'checked' => false],
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_CA, 'name' => 'English (Canada)', 'checked' => false],
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_GB, 'name' => 'English (UK)', 'checked' => false],
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_IN, 'name' => 'English (India)', 'checked' => false],
                ['code' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US, 'name' => 'English (US)', 'checked' => true],
                ['code' => 'de-DE', 'name' => 'German (Germany)', 'checked' => true]
            ],
            'CONVO_VIBER_WEBHOOK_EVENT_TYPES' => [
                ['name' => 'conversation_started', 'checked' => true, 'mandatory' => true],
                ['name' => 'delivered', 'checked' => false, 'mandatory' => false],
                ['name' => 'seen', 'checked' => false, 'mandatory' => false],
                ['name' => 'failed', 'checked' => false, 'mandatory' => false]
            ],
            'CONVO_AMAZON_INTERACTION_MODEL_SENSITIVITIES' => [
                ['value' => 'LOW', 'name' => 'Low'],
                ['value' => 'MEDIUM', 'name' => 'Medium'],
                ['value' => 'HIGH', 'name' => 'High']
            ],
            'CONVO_AMAZON_SKILL_ENDPOINT_SSL_CERTIFICATE' => [
                ['value' => AmazonSkillManifest::CERTIFICATE_TYPE_TRUSTED, 'description' => 'My Development endpoint has a certificate from a trusted certificate authority'],
                ['value' => AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD, 'description' => 'My Development endpoint is a sub-domain of a domain that has a wildcard certificate from a certificate authority'],
                ['value' => AmazonSkillManifest::CERTIFICATE_TYPE_SELF_SIGNED, 'description' => 'I will upload a self-signed certificate in X 509 format']
            ],
            'CONVO_AMAZON_SKILL_CATEGORIES' => [
                ['value' => AmazonSkillManifest::CATEGORY_ALARMS_AND_CLOCKS, 'description' => 'Alarms and Clocks'],
                ['value' => AmazonSkillManifest::CATEGORY_ASTROLOGY, 'description' => 'Astrology'],
                ['value' => AmazonSkillManifest::CATEGORY_BUSINESS_AND_FINANCE, 'description' => 'Business and Finance'],
                ['value' => AmazonSkillManifest::CATEGORY_CALCULATORS, 'description' => 'Calculators'],
                ['value' => AmazonSkillManifest::CATEGORY_CALENDARS_AND_REMINDERS, 'description' => 'Calendars and Reminders'],
                ['value' => AmazonSkillManifest::CATEGORY_CHILDRENS_EDUCATION_AND_REFERENCE, 'description' => 'Childrens Education and Reference'],
                ['value' => AmazonSkillManifest::CATEGORY_CHILDRENS_GAMES , 'description' => 'Childrens Games'],
                ['value' => AmazonSkillManifest::CATEGORY_CHILDRENS_MUSIC_AND_AUDIO , 'description' => 'Childrens Music and Audio'],
                ['value' => AmazonSkillManifest::CATEGORY_CHILDRENS_NOVELTY_AND_HUMOR , 'description' => 'Childrens Novelty and Humor'],
                ['value' => AmazonSkillManifest::CATEGORY_COMMUNICATION , 'description' => 'Communication'],
                ['value' => AmazonSkillManifest::CATEGORY_CONNECTED_CAR , 'description' => 'Connected Car'],
                ['value' => AmazonSkillManifest::CATEGORY_COOKING_AND_RECIPE , 'description' => 'Cooking an Recipe'],
                ['value' => AmazonSkillManifest::CATEGORY_CURRENCY_GUIDES_AND_CONVERTERS , 'description' => 'Currency, Guides and Converters'],
                ['value' => AmazonSkillManifest::CATEGORY_DATING , 'description' => 'Dating'],
                ['value' => AmazonSkillManifest::CATEGORY_DELIVERY_AND_TAKEOUT , 'description' => 'Delivery and Takeout'],
                ['value' => AmazonSkillManifest::CATEGORY_DEVICE_TRACKING , 'description' => 'Device Tracking'],
                ['value' => AmazonSkillManifest::CATEGORY_EDUCATION_AND_REFERENCE , 'description' => 'Education and Reference'],
                ['value' => AmazonSkillManifest::CATEGORY_EVENT_FINDERS , 'description' => 'Event Finders'],
                ['value' => AmazonSkillManifest::CATEGORY_EXERCISE_AND_WORKOUT , 'description' => 'Exercise and Workout'],
                ['value' => AmazonSkillManifest::CATEGORY_FASHION_AND_STYLE , 'description' => 'Fashion and Style'],
                ['value' => AmazonSkillManifest::CATEGORY_FLIGHT_FINDERS  , 'description' => 'Flight Finders'],
                ['value' => AmazonSkillManifest::CATEGORY_FRIENDS_AND_FAMILY   , 'description' => 'Friends and Family'],
                ['value' => AmazonSkillManifest::CATEGORY_GAME_INFO_AND_ACCESSORY   , 'description' => 'Game Info and Accessory'],
                ['value' => AmazonSkillManifest::CATEGORY_GAMES , 'description' => 'Games'],
                ['value' => AmazonSkillManifest::CATEGORY_HEALTH_AND_FITNESS   , 'description' => 'Health and Fitness'],
                ['value' => AmazonSkillManifest::CATEGORY_HOTEL_FINDERS   , 'description' => 'Hotel Finders'],
                ['value' => AmazonSkillManifest::CATEGORY_KNOWLEDGE_AND_TRIVIA   , 'description' => 'Knowledge and Trivia'],
                ['value' => AmazonSkillManifest::CATEGORY_MOVIE_AND_TV_KNOWLEDGE_AND_TRIVIA   , 'description' => 'Movie and TV Knowledge and Trivia'],
                ['value' => AmazonSkillManifest::CATEGORY_MOVIE_INFO_AND_REVIEWS   , 'description' => 'Movie Info and Reviews'],
                ['value' => AmazonSkillManifest::CATEGORY_MOVIE_SHOWTIMES   , 'description' => 'Movie Showtimes'],
                ['value' => AmazonSkillManifest::CATEGORY_MUSIC_AND_AUDIO_ACCESSORIES   , 'description' => 'Music and Audio Accessories'],
                ['value' => AmazonSkillManifest::CATEGORY_MUSIC_AND_AUDIO_KNOWLEDGE_AND_TRIVIA   , 'description' => 'Music and Audio Knowledge and Trivia'],
                ['value' => AmazonSkillManifest::CATEGORY_MUSIC_INFO_REVIEWS_AND_RECOGNITION_SERVICE    , 'description' => 'Music  Info Review and Recognition Service'],
                ['value' => AmazonSkillManifest::CATEGORY_NAVIGATION_AND_TRIP_PLANNER    , 'description' => 'Navigation and Trip Planner'],
                ['value' => AmazonSkillManifest::CATEGORY_NEWS    , 'description' => 'News'],
                ['value' => AmazonSkillManifest::CATEGORY_NOVELTY    , 'description' => 'Novelty'],
                ['value' => AmazonSkillManifest::CATEGORY_ORGANIZERS_AND_ASSISTANTS    , 'description' => 'Organizers and Assistants'],
                ['value' => AmazonSkillManifest::CATEGORY_PETS_AND_ANIMAL    , 'description' => 'Pets and Animal'],
                ['value' => AmazonSkillManifest::CATEGORY_PODCAST    , 'description' => 'Podcast'],
                ['value' => AmazonSkillManifest::CATEGORY_PUBLIC_TRANSPORTATION    , 'description' => 'Public Transportation'],
                ['value' => AmazonSkillManifest::CATEGORY_RELIGION_AND_SPIRITUALITY    , 'description' => 'Religion and Spirituality'],
                ['value' => AmazonSkillManifest::CATEGORY_RESTAURANT_BOOKING_INFO_AND_REVIEW     , 'description' => 'Restaurant Booking Info and Review'],
                ['value' => AmazonSkillManifest::CATEGORY_SCHOOLS     , 'description' => 'School'],
                ['value' => AmazonSkillManifest::CATEGORY_SCORE_KEEPING     , 'description' => 'Score Keeping'],
                ['value' => AmazonSkillManifest::CATEGORY_SELF_IMPROVEMENT     , 'description' => 'Self Improvement'],
                ['value' => AmazonSkillManifest::CATEGORY_SHOPPING     , 'description' => 'Shopping'],
                ['value' => AmazonSkillManifest::CATEGORY_SMART_HOME     , 'description' => 'Smart Home'],
                ['value' => AmazonSkillManifest::CATEGORY_SOCIAL_NETWORKING     , 'description' => 'Social Networking'],
                ['value' => AmazonSkillManifest::CATEGORY_SPORTS_GAMES     , 'description' => 'Sports Games'],
                ['value' => AmazonSkillManifest::CATEGORY_SPORTS_NEWS     , 'description' => 'Sports News'],
                ['value' => AmazonSkillManifest::CATEGORY_STREAMING_SERVICE     , 'description' => 'Streaming Service'],
                ['value' => AmazonSkillManifest::CATEGORY_TAXI_AND_RIDESHARING     , 'description' => 'Taxi and Ridesharing'],
                ['value' => AmazonSkillManifest::CATEGORY_TO_DO_LISTS_AND_NOTES      , 'description' => 'TODO Lists and Notes'],
                ['value' => AmazonSkillManifest::CATEGORY_TRANSLATORS      , 'description' => 'Translators'],
                ['value' => AmazonSkillManifest::CATEGORY_TV_GUIDES      , 'description' => 'TV Guides'],
                ['value' => AmazonSkillManifest::CATEGORY_UNIT_CONVERTERS      , 'description' => 'Unit Converters'],
                ['value' => AmazonSkillManifest::CATEGORY_WEATHER      , 'description' => 'Weather'],
                ['value' => AmazonSkillManifest::CATEGORY_WINE_AND_BEVERAGE      , 'description' => 'Wine and Beverage'],
                ['value' => AmazonSkillManifest::CATEGORY_ZIP_CODE_LOOKUP       , 'description' => 'ZIP Code Lookup'],
            ],
            'CONVO_DIALOGFLOW_TIMEZONES' => [
                ['value' => 'Etc/GMT+12', 'name' => '(GMT-12=>00) Etc/GMT+12'],
                ['value' => 'Pacific/Midway', 'name' => '(GMT-11=>00) Pacific/Midway'],
                ['value' => 'Pacific/Honolulu', 'name' => '(GMT-10=>00) Pacific/Honolulu'],
                ['value' => 'America/Anchorage', 'name' => '(GMT-9=>00) America/Anchorage'],
                ['value' => 'US/Alaska', 'name' => '(GMT-9=>00) US/Alaska'],
                ['value' => 'America/Los_Angeles', 'name' => '(GMT-8=>00) America/Los_Angeles'],
                ['value' => 'America/Denver', 'name' => '(GMT-7=>00) America/Denver'],
                ['value' => 'America/Chicago', 'name' => '(GMT-6=>00) America/Chicago'],
                ['value' => 'America/New_York', 'name' => '(GMT-5=>00) America/New_York'],
                ['value' => 'America/Barbados', 'name' => '(GMT-4=>00) America/Barbados'],
                ['value' => 'America/Buenos_Aires', 'name' => '(GMT-3=>00) America/Buenos_Aires'],
                ['value' => 'Atlantic/South_Georgia', 'name' => '(GMT-2=>00) Atlantic/South_Georgia'],
                ['value' => 'Atlantic/Cape_Verde', 'name' => '(GMT-1=>00) Atlantic/Cape_Verde'],
                ['value' => 'Africa/Casablanca', 'name' => '(GMT0=>00) Africa/Casablanca'],
                ['value' => 'Europe/Madrid', 'name' => '(GMT+2=>00) Europe/Madrid'],
                ['value' => 'Europe/Kaliningrad', 'name' => '(GMT+2=>00) Europe/Kaliningrad'],
                ['value' => 'Europe/Moscow', 'name' => '(GMT+3=>00) Europe/Moscow'],
                ['value' => 'Asia/Dubai', 'name' => '(GMT+4=>00) Asia/Dubai'],
                ['value' => 'Asia/Kabul', 'name' => '(GMT+4=>30) Asia/Kabul'],
                ['value' => 'Asia/Yekaterinburg', 'name' => '(GMT+5=>00) Asia/Yekaterinburg'],
                ['value' => 'Asia/Colombo', 'name' => '(GMT+5=>30) Asia/Colombo'],
                ['value' => 'Asia/Kathmandu', 'name' => '(GMT+5=>45) Asia/Kathmandu'],
                ['value' => 'Asia/Almaty', 'name' => '(GMT+6=>00) Asia/Almaty'],
                ['value' => 'Asia/Rangoon', 'name' => '(GMT+6=>30) Asia/Rangoon'],
                ['value' => 'Asia/Bangkok', 'name' => '(GMT+7=>00) Asia/Bangkok'],
                ['value' => 'Asia/Hong_Kong', 'name' => '(GMT+8=>00) Asia/Hong_Kong'],
                ['value' => 'Asia/Tokyo', 'name' => '(GMT+9=>00) Asia/Tokyo'],
                ['value' => 'Asia/Tokyo', 'name' => '(GMT+9=>00) Asia/Tokyo'],
                ['value' => 'Australia/Darwin', 'name' => '(GMT+9=>30) Australia/Darwin'],
                ['value' => 'Australia/Sydney', 'name' => '(GMT+10=>00) Australia/Sydney'],
                ['value' => 'Pacific/Noumea', 'name' => '(GMT+11=>00) Pacific/Noumea'],
                ['value' => 'Pacific/Fiji', 'name' => '(GMT+12=>00) Pacific/Fiji'],
                ['value' => 'Pacific/Tongatapu', 'name' => '(GMT+13=>00) Pacific/Tongatapu']
            ],
            'CONVO_FACEBOOK_MESSENGER_WEBHOOK_EVENTS' => [
                ['name' => 'messages', 'checked' => true, 'mandatory' => true],
                ['name' => 'messaging_postbacks', 'checked' => true, 'mandatory' => true],
                ['name' => 'messaging_optins', 'checked' => false, 'mandatory' => false],
                ['name' => 'message_deliveries', 'checked' => false, 'mandatory' => false],
                ['name' => 'message_reads', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_payments', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_pre_checkouts', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_checkout_updates', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_account_linking', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_referrals', 'checked' => false, 'mandatory' => false],
                ['name' => 'message_echoes', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_game_plays', 'checked' => false, 'mandatory' => false],
                ['name' => 'standby', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_handovers', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_policy_enforcement', 'checked' => false, 'mandatory' => false],
                ['name' => 'message_reactions', 'checked' => false, 'mandatory' => false],
                ['name' => 'inbox_labels', 'checked' => false, 'mandatory' => false],
                ['name' => 'messaging_fblogin_account_linking', 'checked' => false, 'mandatory' => false]
            ],
            'CONVO_VIBER_WEBHOOK_EVENT_TYPES' => [
                ['name' => 'conversation_started', 'checked' => true, 'mandatory' => true],
                ['name' => 'delivered', 'checked' => false, 'mandatory' => false],
                ['name' => 'seen', 'checked' => false, 'mandatory' => false],
                ['name' => 'failed', 'checked' => false, 'mandatory' => false]
            ],
            'CONVO_ALEXA_INTERFACES' => [
                ['type' => 'AUDIO_PLAYER', 'name'=> 'Audio Player', 'checked' => false],
                ['type' => 'RENDER_TEMPLATE', 'name'=> 'Display Interface', 'checked' => false],
                ['type' => 'VIDEO_APP', 'name'=> 'Video App', 'checked' => false],
                ['type' => 'CAN_FULFILL_INTENT_REQUEST', 'name'=> 'CanFulfillIntentRequest', 'checked' => false],
                ['type' => 'ALEXA_PRESENTATION_APL', 'name'=> 'Alexa Presentation Language', 'checked' => false],
                ['type' => 'CUSTOM_INTERFACE', 'name'=> 'Custom Interface Controller', 'checked' => false],
                ['type' => 'ALEXA_PRESENTATION_HTML', 'name'=> 'Alexa Web API for Games', 'checked' => false]
            ],
            'CONVO_DIALOGFLOW_INTERFACES' => [
                ['type' => 'AUDIO_PLAYER', 'name'=> 'Audio Player', 'checked' => false]
            ],
            'CONVO_DIALOGFLOW_SERVICE_ACCOUNT_FIELDS' => [
                'type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri', 'auth_provider_x509_cert_url', 'client_x509_cert_url'
            ]
        ];

        return $this->_httpFactory->buildResponse($data);
    }
}
