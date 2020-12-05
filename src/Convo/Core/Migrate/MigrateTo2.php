<?php declare(strict_types=1);

namespace Convo\Core\Migrate;


class MigrateTo2 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 2;
	}
	
	
	protected function _migrateComponent( $componentData) {
		
		$rename	=	[
				// GOOGLE
				'\\Adm\\Nlp\\Alexax\\GoogleRequestFilter' => '\\Convo\\Pckg\\Gnlp\\GoogleNlpRequestFilter',
				'\\Adm\\Nlp\\Filter\\OrFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\OrFilter',
				'\\Adm\\Nlp\\Filter\\AndFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\AndFilter',
				'\\Adm\\Nlp\\Filter\\NopFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\NopFilter',
				'\\Adm\\Nlp\\Filter\\NumberFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\NumberFilter',
				'\\Adm\\Nlp\\Filter\\PartOfSpeechValueFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\PartOfSpeechValueFilter',
				'\\Adm\\Nlp\\Filter\\PriceRangeFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\PriceRangeFilter',
				'\\Adm\\Nlp\\Filter\\RelationFilter' => '\\Convo\\Pckg\\Gnlp\\Filters\\RelationFilter',
				
				// AMAZON
				'\\Adm\\Alexax\\Amz\\AmazonIntentRequestFilter' => '\\Convo\\Pckg\\Alexa\\AmazonIntentRequestFilter',
				'\\Adm\\Alexax\\Amz\\AmazonIntentReader' => '\\Convo\\Pckg\\Alexa\\AmazonIntentReader',
				'\\Adm\\Alexax\\Amz\\AmazonConfiguration' => '\\Convo\\Core\\Adapters\\Alexa\\AmazonConfiguration',
				
				// CORE ELEMS
				'\\Adm\\Alexax\\Elem\\AudioPlayer' => '\\Convo\\Pckg\\Core\\Elements\\AudioPlayer',
				'\\Adm\\Alexax\\Elem\\CommentElement' => '\\Convo\\Pckg\\Core\\Elements\\CommentElement',
				'\\Adm\\Alexax\\Elem\\ConversationBlock' => '\\Convo\\Pckg\\Core\\Elements\\ConversationBlock',
				'\\Adm\\Alexax\\Elem\\ElementCollection' => '\\Convo\\Pckg\\Core\\Elements\\ElementCollection',
				'\\Adm\\Alexax\\Elem\\ElementRandomizer' => '\\Convo\\Pckg\\Core\\Elements\\ElementRandomizer',
				'\\Adm\\Alexax\\Elem\\ElementsSubroutine' => '\\Convo\\Pckg\\Core\\Elements\\ElementsSubroutine',
				'\\Adm\\Alexax\\Elem\\EndSessionElement' => '\\Convo\\Pckg\\Core\\Elements\\EndSessionElement',
				'\\Adm\\Alexax\\Elem\\FileReader' => '\\Convo\\Pckg\\Core\\Elements\\FileReader',
				'\\Adm\\Alexax\\Elem\\GoogleAnalyticsElement' => '\\Convo\\Pckg\\Core\\Elements\\GoogleAnalyticsElement',
				'\\Adm\\Alexax\\Elem\\HttpQueryElement' => '\\Convo\\Pckg\\Core\\Elements\\HttpQueryElement',
				'\\Adm\\Alexax\\Elem\\IfElement' => '\\Convo\\Pckg\\Core\\Elements\\IfElement',
				'\\Adm\\Alexax\\Elem\\IfElementCase' => '\\Convo\\Pckg\\Core\\Elements\\IfElementCase',
				'\\Adm\\Alexax\\Elem\\LoopElement' => '\\Convo\\Pckg\\Core\\Elements\\LoopElement',
				'\\Adm\\Alexax\\Elem\\MysqliQueryElement' => '\\Convo\\Pckg\\Core\\Elements\\MysqliQueryElement',
				'\\Adm\\Alexax\\Elem\\ReadElementsSubroutine' => '\\Convo\\Pckg\\Core\\Elements\\ReadElementsSubroutine',
				'\\Adm\\Alexax\\Elem\\SetParamElement' => '\\Convo\\Pckg\\Core\\Elements\\SetParamElement',
				'\\Adm\\Alexax\\Elem\\SetStateElement' => '\\Convo\\Pckg\\Core\\Elements\\SetStateElement',
				'\\Adm\\Alexax\\Elem\\SimpleEvalIfTest' => '\\Convo\\Pckg\\Core\\Elements\\SimpleEvalIfTest',
				'\\Adm\\Alexax\\Elem\\SimpleTextResponse' => '\\Convo\\Pckg\\Core\\Elements\\SimpleTextResponse',

				// CORE PROCESSORS
				'\\Adm\\Alexax\\Proc\\ProcessorSubroutine' => '\\Convo\\Pckg\\Core\\Processors\\ProcessorSubroutine',
				'\\Adm\\Alexax\\Proc\\ProcessProcessorSubroutine' => '\\Convo\\Pckg\\Core\\Processors\\ProcessProcessorSubroutine',
				'\\Adm\\Alexax\\Proc\\SimpleProcessor' => '\\Convo\\Pckg\\Core\\Processors\\SimpleProcessor',
				'\\Adm\\Alexax\\Proc\\YesNoProcessor' => '\\Convo\\Pckg\\Core\\Processors\\YesNoProcessor',
				
				// CORE FILTERS
				'\\Adm\\Alexax\\Txt\\PlainTextRequestFilter' => '\\Convo\\Pckg\\Core\\Filters\\PlainTextRequestFilter',
				'\\Adm\\Alexax\\Txt\\Flt\\AndFilter' => '\\Convo\\Pckg\\Core\\Filters\\Flt\\AndFilter',
				'\\Adm\\Alexax\\Txt\\Flt\\OrFilter' => '\\Convo\\Pckg\\Core\\Filters\\Flt\\OrFilter',
				'\\Adm\\Alexax\\Txt\\Flt\\RegexFilter' => '\\Convo\\Pckg\\Core\\Filters\\Flt\\RegexFilter',
				'\\Adm\\Alexax\\Txt\\Flt\\StriposFilter' => '\\Convo\\Pckg\\Core\\Filters\\Flt\\StriposFilter',
				'\\Adm\\Alexax\\NopRequestFilter' => '\\Convo\\Pckg\\Core\\Filters\\NopRequestFilter',

				// CORE INIT
				'\\Adm\\Alexax\\Init\\MysqlConnectionComponent' => '\\Convo\\Pckg\\Core\\Init\\MysqlConnectionComponent',
		];
		
		if ( isset( $rename[$componentData['class']])) {
			$this->_logger->debug( 'Changing component class ['.$componentData['class'].'] to ['.$rename[$componentData['class']].']');
			$componentData['class']	=	$rename[$componentData['class']];
		}
		
		if ( $componentData['namespace'] === 'amazon') {
			$componentData['namespace'] = 'convo-alexa';
		} else if ( $componentData['namespace'] === 'google-nlp') {
			$componentData['namespace'] = 'convo-gnlp';
		}
		
		return $componentData;
	}

}