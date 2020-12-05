<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

use Convo\Core\Factory\AbstractPackageDefinition;

class GoogleNlpPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-gnlp';

	/**
	 * @var \Convo\Pckg\Gnlp\Api\IGoogleNlpFactory
	 */
	private $_googleNlpFactory;

	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxParser
	 */
	private $_googleNlpSyntaxParser;

	public function __construct( \Psr\Log\LoggerInterface $logger, \Convo\Pckg\Gnlp\Api\IGoogleNlpFactory $nlpFactory, \Convo\Pckg\Gnlp\GoogleNlSyntaxParser $syntaxParser)
	{
		$this->_googleNlpFactory		=	$nlpFactory;
		$this->_googleNlpSyntaxParser	=	$syntaxParser;

		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Factory\AbstractPackageDefinition::_initDefintions()
	 */
	protected function _initDefintions()
	{
	    return array(
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\GoogleNlpRequestFilter', 'Google NLP filter', 'Analyzes user queries with Google NLP', array(
								'filter' => array(
										'editor_type' => 'service_components',
										'editor_properties' => array(
												'allow_interfaces' => array( '\Convo\Pckg\Gnlp\Filters\ITextFilter'),
												'multiple' => false
										),
										'defaultValue' => null,
										'defaultOpen' => true,
										'name' => 'Concrete Google NLP filter',
										'description' => 'Filter that filters user query',
										'valueType' => 'class'
								),
								'api_key' => array(
										'editor_type' => 'text',
										'editor_properties' => array(
										),
										'defaultValue' => '${GOOGLE_NLP_API_KEY}',
										'name' => 'Google NLP API key',
										'description' => 'API key to use when analyzing text',
										'valueType' => 'string'
								),
								'_workflow' => 'filter',
								'_factory' => new class ( $this->_googleNlpFactory, $this->_googleNlpSyntaxParser) implements \Convo\Core\Factory\IComponentFactory {
								private $_googleNlpFactory;
								private $_googleNlpSyntaxParser;
								public function __construct( \Convo\Pckg\Gnlp\Api\IGoogleNlpFactory $nlpFactory, \Convo\Pckg\Gnlp\GoogleNlSyntaxParser $syntaxParser) {
									$this->_googleNlpFactory		=	$nlpFactory;
									$this->_googleNlpSyntaxParser	=	$syntaxParser;
								}
								public function createComponent( $properties, $service) {
									return new \Convo\Pckg\Gnlp\GoogleNlpRequestFilter( $properties, $service, $this->_googleNlpFactory, $this->_googleNlpSyntaxParser);
								}
								},
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\AndFilter', 'AND filter', 'This filter is activated only if all its child filters are activated too', array(
								'filters' => array(
										'editor_type' => 'service_components',
										'editor_properties' => array(
												'allow_interfaces' => array( '\Convo\Pckg\Gnlp\Filters\ITextFilter'),
												'multiple' => true
										),
										'defaultValue' => array(),
										'defaultOpen' => true,
										'name' => 'Child filters',
										'description' => 'Child filters to be applied on user query',
										'valueType' => 'class'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\OrFilter', 'OR filter', 'This filter is activated if any of its children is activated too', array(
								'filters' => array(
										'editor_type' => 'service_components',
										'editor_properties' => array(
												'allow_interfaces' => array( '\Convo\Pckg\Gnlp\Filters\ITextFilter'),
												'multiple' => true
										),
										'defaultValue' => array(),
										'defaultOpen' => true,
										'name' => 'Child filters',
										'description' => 'Child filters to be applied on user query',
										'valueType' => 'class'
								),
								'collect_all' => array(
										'editor_type' => 'boolean',
										'editor_properties' => array(
										),
										'defaultValue' => true,
										'name' => 'Collect all values',
										'description' => 'If this value is false, the result will contain only slot values from the child filter who was activated first. Otherwise, result will contain all activated filters values.',
										'valueType' => 'boolean'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\NopFilter', 'NOP filter', 'This filter does nothing. You can use it as placeholder.', array(
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\NumberFilter', 'Number filter', 'This filter filters out numeric values', array(
								'length' => array(
								        'editor_type' => 'text',            //TODO: return to 'int' after it's fixed
										'editor_properties' => array(
										),
										'defaultValue' => 0,
										'name' => 'Number of digits',
										'description' => 'Required length of the number. Leave 0 to be ignored',
										'valueType' => 'int'
								),
								'group' => array(
										'editor_type' => 'boolean',
										'editor_properties' => array(
										),
										'defaultValue' => true,
										'name' => 'Group numbers',
										'description' => 'If true, filters negative numbers and the system automatically convert e.g. 23 hundreds to 2300',
										'valueType' => 'boolean'
								),
								'exclusive' => array(
										'editor_type' => 'boolean',
										'editor_properties' => array(
										),
										'defaultValue' => array(),
										'name' => 'Exclusive only',
										'description' => 'If set to true, filter will be activated only if the whole user query is number iteslf',
										'valueType' => 'boolean'
								),
								'slot_name' => array(
										'editor_type' => 'slot_name',
										'editor_properties' => array(
										),
										'defaultValue' => 'number',
										'name' => 'Slot name',
										'description' => 'Name of the slot where extracted value should be',
										'valueType' => 'string'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\PartOfSpeechValueFilter', 'Part of speech filter', 'This filter filters out specific word types', array(
								'exclusive' => array(
										'editor_type' => 'boolean',
										'editor_properties' => array(
										),
										'defaultValue' => array(),
										'name' => 'Exclusive only',
										'description' => 'If set to true, filter will be activated only if the whole user query is number iteslf',
										'valueType' => 'boolean'
								),
								'type' => array(
										'editor_type' => 'select',
										'editor_properties' => array(
												'options' => array( 'adv', 'adj', 'verb', 'noun', 'punct'),
												'multiple' => true,
										),
										'defaultValue' => null,
										'name' => 'Word type to match',
										'description' => 'One or multiple word types to match',
										'valueType' => 'string'
								),
								'value' => array(
										'editor_type' => 'text',
										'editor_properties' => array(
												'multiple' => true,
										),
										'defaultValue' => null,
										'name' => 'Value to match',
										'description' => 'One or multiple values to match',
										'valueType' => 'string'
								),
								'slot_name' => array(
										'editor_type' => 'slot_name',
										'editor_properties' => array(
										),
										'defaultValue' => null,
										'name' => 'Slot name',
										'description' => 'Name of the slot where extracted value should be',
										'valueType' => 'string'
								),
								'slot_value' => array(
										'editor_type' => 'text',
										'editor_properties' => array(
										),
										'defaultValue' => null,
										'name' => 'Slot value',
										'description' => 'Predefined value to set',
										'valueType' => 'string'
								),
								'_preview_angular' => array(
										'type' => 'html',
										'template' => '<div class="code">Catch one of the: <b>{{ component.properties.value}}</b> of type <b>{{ component.properties.type}}</b>'.
										'<span ng-if="component.properties.slot_name && component.properties.slot_value">, use predefined value <b>\'{{ component.properties.slot_value}}\'</b> as <b>{{ component.properties.slot_name}}</b></span>'.
										'<span ng-if="component.properties.slot_name && !component.properties.slot_value">, store match as <b>{{ component.properties.slot_name}}</b></span></div>'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\PriceRangeFilter', 'Price range filter', 'Filters out price or price range', array(
								'slot_name_min' => array(
										'editor_type' => 'slot_name',
										'editor_properties' => array(
										),
										'defaultValue' => 'price_min',
										'name' => 'Min price slot name',
										'description' => 'Name of the slot where value for minimal price should be',
										'valueType' => 'string'
								),
								'slot_name_max' => array(
										'editor_type' => 'slot_name',
										'editor_properties' => array(
										),
										'defaultValue' => 'price_max',
										'name' => 'Max price slot name',
										'description' => 'Name of the slot where value for maximal price should be',
										'valueType' => 'string'
								),
								'min_value' => array(
                                        'editor_type' => 'text',            //TODO: return to 'int' after it's fixed
										'editor_properties' => array(
										),
										'defaultValue' => 1000,
										'name' => 'Minimum allowed value',
										'description' => 'Minimum allowed value to be accepted as valid.',
										'valueType' => 'int'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
				new \Convo\Core\Factory\ComponentDefinition(
						$this->getNamespace(),
						'\Convo\Pckg\Gnlp\Filters\RelationFilter', 'Words relation filter', 'Checks how far specified words in a sentence are form one other', array(
								'distance' => array(
										'editor_type' => 'text',            //TODO: return to 'int' after it's fixed
										'editor_properties' => array(
										),
										'defaultValue' => 0,
										'name' => 'Maximal distance',
										'description' => 'Maximal allowed distance between matches to be accepted as valid.',
										'valueType' => 'int'
								),
								'filter_1' => array(
										'editor_type' => 'service_components',
										'editor_properties' => array(
												'allow_interfaces' => array( '\Convo\Pckg\Gnlp\Filters\ITextFilter'),
												'multiple' => false
										),
										'defaultValue' => null,
										'name' => 'Filter 1 to check',
										'description' => 'Child filter to be executed and its result compared',
										'valueType' => 'class'
								),
								'filter_2' => array(
										'editor_type' => 'service_components',
										'editor_properties' => array(
												'allow_interfaces' => array( '\Convo\Pckg\Gnlp\Filters\ITextFilter'),
												'multiple' => false
										),
										'defaultValue' => null,
										'name' => 'Filter 2 to check',
										'description' => 'Child filter to be executed and its result compared',
										'valueType' => 'class'
								),
								'_workflow' => 'filter',
								'_descend' => true,
						)),
		);
	}
}
