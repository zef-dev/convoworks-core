<?php

declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

use \Convo\Pckg\Gnlp\GoogleNlSyntaxToken;

class PartOfSpeechValueFilter implements ITextFilter, \Psr\Log\LoggerAwareInterface
{
	public static $DFAULTS	=	array(
		'multiple' => false,
		'exclusive' => false,
		'force_proper' => false,
	);

	private $_type;
	private $_value;
	private $_exclusive;
	private $_multiple;
	private $_forceProper;
	private $_slotName;
	private $_slotValue;

	/**
	 * @var \Convo\Pckg\Gnlp\NlpFilterResult
	 */
	private $_filterResult;

	private $_values = [];

	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	public function __construct($config = [])
	{
		$config =	array_merge(self::$DFAULTS, $config);

		$this->_filterResult = new \Convo\Pckg\Gnlp\NlpFilterResult();
		$this->_exclusive = $config['exclusive'];
		$this->_multiple = $config['multiple'];
		$this->_forceProper = $config['force_proper'];

		if (isset($config['value'])) {
			$this->_value = $this->_sanitizeValues($config['value']);
		}

		if (isset($config['type'])) {
			$this->_type = $this->_sanitizeValues($config['type']);
		}

		if (isset($config['slot_name'])) {
			$this->_slotName = $config['slot_name'];
		}

		if (isset($config['slot_value'])) {
			$this->_slotValue = $config['slot_value'];
		}

		$this->_logger	=	new \Psr\Log\NullLogger();
	}
	
	public function setLogger( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}
	
	private function _sanitizeValues($values)
	{
		if (!$values) {
			return array();
		}
		if (!is_array($values)) {
			return array($values);
		}
		return $values;
	}

	public function visitToken(GoogleNlSyntaxToken $token)
	{
		if (!empty($this->_type) && !in_array($token->getTag(), $this->_type)) {
			// 			$this->_logger->info( 'Token ['.$token.']['.$token->getTag().'] IS NOT type ['.implode( ', ', $this->_type).']. Skipping ...');
			return;
		}

		if ($this->_forceProper && !$token->isProper()) {
			$this->_logger->info('Token [' . $token . '] IS NOT proper. Skipping ...');
			return;
		}

		$value = strtolower($token->getContent());

		if (empty($this->_value)) {
			$this->_logger->info('Value matcher is empty -> accepting all [' . implode(', ', $this->_type) . '].');
		} else {
			if (!in_array($value, $this->_value)) {
				$this->_logger->info('Token value [' . $token . '][' . $value . '] IS NOT as searched [' . implode(', ', $this->_value) . ']. One more check ...');
				$value = strtolower($token->getLemaContent());
				if (!in_array($value, $this->_value)) {
					$this->_logger->info('Token value [' . $token . '][' . $value . '] IS NOT as searched [' . implode(', ', $this->_value) . ']. Skipping ...');
					return;
				}
			}
		}

		if ($this->_exclusive) {
			$all_content = str_replace(' ', '', $token->getRoot()->getAllContent());

			if ($token->getContent() != $all_content) {
				$this->_logger->debug('Token [' . $token . '] IS NOT exclusive content, but it is expected. Skipping ...');
				return;
			}
		}

		$this->_logger->debug('Token [' . $token . '] IS VALID. Adding to result');
		$this->_filterResult->addToken($token);

		if ($this->_slotName) {
			if (isset($this->_slotValue)) {
				$this->_logger->debug('Updating old value [' . $value . ']');
				if (is_array($this->_slotValue)) {
					$value_index = array_search($value, $this->_value);
					$this->_logger->debug('Found value index [' . $value_index . '] in [' . implode(', ', $this->_value) . '] for [' . implode(', ', $this->_slotValue) . ']');
					$value = $this->_slotValue[$value_index];
				} else {
					$value = $this->_slotValue;
				}

				$this->_logger->debug('Setting the predefine slot [' . $this->_slotName . '] value [' . $value . ']');
				$this->_setSlotValue($token, $value);
			} else {
				$this->_logger->debug('Setting the slot [' . $this->_slotName . '] dynamic value [' . $token->getContent() . ']');
				$this->_setSlotValue($token, $token->getContent());
			}
		}
	}

	private function _setSlotValue(GoogleNlSyntaxToken $token, $value)
	{
		if ($this->_multiple) {
			if (!in_array($value, $this->_values)) {
				$this->_values[] = $value;
				$this->_filterResult->setSlotTokenValue($this->_slotName, $this->_values, $token);
			}
		} else {
			$this->_filterResult->setSlotTokenValue($this->_slotName, $value, $token);
		}
	}

	public function getFilterResult()
	{
		return $this->_filterResult;
	}

	// UTIL
	public function __toString()
	{
		return get_class($this) . '[]';
	}
}
