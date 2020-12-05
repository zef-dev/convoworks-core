<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

class RelationFilter implements ITextFilter
{
	/**
	 * @var int
	 */
	private $_distance;
	/**
	 * @var \Convo\Pckg\Gnlp\Filters\ITextFilter
	 */
	private $_filter1;
	
	/**
	 * @var \Convo\Pckg\Gnlp\Filters\ITextFilter
	 */
	private $_filter2;
	
	/**
	 * @var \Convo\Pckg\Gnlp\NlpFilterResult
	 */
	private $_filterResult;

	private $_strict = false;
	
	public function __construct( $config)
	{
		$this->_filterResult	=	new \Convo\Pckg\Gnlp\NlpFilterResult();
		
		if (func_num_args() == 3) {
			$this->_distance		=	func_get_arg( 0);
			$this->_filter1			=	func_get_arg( 1);
			$this->_filter2			=	func_get_arg( 2);
			return ;
		}
		
		if (func_num_args() == 4) {
			$this->_distance = func_get_arg(0);
			$this->_filter1 = func_get_arg(1);
			$this->_filter2 = func_get_arg(2);
			$this->_strict = func_get_arg(3);
			return;
		}

		$this->_distance = $config['distance'];
		$this->_filter1 = $config['filter_1'];
		$this->_filter2 = $config['filter_2'];
		$this->_strict = isset($config['strict']) ? $config['strict'] : $this->_strict;
	}
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
		$this->_filter1->visitToken( $token);
		$result1 = $this->_filter1->getFilterResult();
		
		$this->_filter2->visitToken( $token);
		$result2 = $this->_filter2->getFilterResult();
		
		if ($result1->equals($result2)) {
			return;
		}

		if (!($result1->isEmpty() || $result2->isEmpty())) {
			/** @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken[] $tokens */
			try {
				if ($this->_strict) {
					$tokens = $result1->getTokensAtDistance($result2, $this->_distance);
				} else {
					$tokens = $result1->getClosestTokens($result2);
				}
			} catch (\Convo\Core\DataItemNotFoundException $e) {
				// $this->_logger->error($e->getMessage());
				return;
			}

			$distance = $tokens[0]->getDistance($tokens[1]);

			if ($this->_strict && $distance === $this->_distance || !$this->_strict && $distance <= $this->_distance)
			{
				// OK
				$this->_filterResult->addToken($tokens[0]);
				$this->_filterResult->addToken($tokens[1]);

				foreach ($result1->getData() as $key => $val) {
					$val = $result1->getSlotTokenValue($key, $tokens[0]);
					$this->_filterResult->setSlotValue($key, $val);
				}

				foreach ($result2->getData() as $key => $val) {
					$val = $result2->getSlotTokenValue($key, $tokens[1]);
					$this->_filterResult->setSlotValue($key, $val);
				}
			}
			else
			{
				// Invalid distance?
			}
		} else {
			if ($result1->isEmpty() && $result2->isEmpty()) {
				// $this->_logger->debug('Both results are empty. Skipping token ['.$token.']');
			} else {
				// $this->_logger->debug('One result is empty. Skipping token ['.$token.']');
			}
		}
	}
	
	public function getFilterResult()
	{
		return $this->_filterResult;
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}