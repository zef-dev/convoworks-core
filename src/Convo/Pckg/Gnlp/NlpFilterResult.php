<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

class NlpFilterResult extends \Convo\Core\Workflow\DefaultFilterResult
{
	/**
	 * @var GoogleNlSyntaxToken[]
	 */
	private $_tokens	=	array();
	
	/**
	 * @var SlotValue[];
	 */
	private $_tokenValues = [];

	public function setSlotTokenValue( $name, $value, GoogleNlSyntaxToken $token) {
		if (!isset($this->_tokenValues[$name])) {
			$this->_tokenValues[$name] = new SlotValue();
		}

		$this->_tokenValues[$name]->addTokenValue($value, $token);
		$this->setSlotValue($name, $value);
	}

	public function getSlotTokenValue( $name, GoogleNlSyntaxToken $token) {
		if (!isset($this->_tokenValues[$name])) {
			throw new \Exception("No token value [$name] found.");
		}

		return $this->_tokenValues[$name]->getTokenValue($token);
	}

	public function isEmpty() {
		return empty( $this->_tokens);
	}
	
	public function read( \Convo\Core\Workflow\IRequestFilterResult ...$results) 
	{
		parent::read( ...$results);

		foreach ( $results as $result) 
		{
			if ( is_a( $result, '\Convo\Pckg\Gnlp\NlpFilterResult')) 
			{
				/* @var \Convo\Pckg\Gnlp\NlpFilterResult $result */
				foreach ( $result->getTokens() as $token) {
					$this->addToken( $token);
				}
			}
		}
	}
	
	public function getClosestTokens( NlpFilterResult $result)
	{
		/** @var GoogleNlSyntaxToken[] $tokens */
		/** @var GoogleNlSyntaxToken[] $other_tokens */
		/** @var GoogleNlSyntaxToken[] $this_tokens */

		$tokens = [];

		$other_tokens = $result->getTokens();
		$this_tokens = $this->getTokens();

		$min_distance = null;

		foreach ($other_tokens as $other_token) {
			foreach ($this_tokens as $this_token) {

				if ($this_token->equals($other_token)) {
					continue;
				}

				$distance = $this_token->getDistance($other_token);

				if (is_null($min_distance) || $distance < $min_distance) {
					$tokens[0] = $this_token;
					$tokens[1] = $other_token;
					$min_distance = $distance;
				}
			}
		}

		if (count($tokens) !== 2) {
			throw new \Convo\Core\DataItemNotFoundException('Expected to find exactly 2 tokens, but found ['.count($tokens).']');
		}

		return $tokens;
	}

	public function getTokensAtDistance( NlpFilterResult $result, $targetDistance) {
		/** @var GoogleNlSyntaxToken[] $tokens */
		/** @var GoogleNlSyntaxToken[] $other_tokens */
		/** @var GoogleNlSyntaxToken[] $this_tokens */

		$tokens = [];

		$other_tokens = $result->getTokens();
		$this_tokens = $this->getTokens();

		foreach ($other_tokens as $other_token) {
			foreach ($this_tokens as $this_token) {

				if ($this_token->equals($other_token)) {
					continue;
				}

				$distance = $this_token->getDistance($other_token);

				if ($distance === $targetDistance) {
					$tokens[0] = $this_token;
					$tokens[1] = $other_token;
					return $tokens;
				}
			}
		}

		throw new \Convo\Core\DataItemNotFoundException("No tokens found at target distance of [$targetDistance]");
	}

	public function getDistance( NlpFilterResult $result) {
		
		$this_to_root	=	$this->getMinDistanceToRoot();
		$new_to_root	=	$result->getMinDistanceToRoot();
		
		return $this_to_root + $new_to_root;
	}
	
	public function getMinDistanceToRoot()
	{
		$min	=	null;
		foreach ( $this->_tokens as $token) {
			/* @var $token GoogleNlSyntaxToken */
			$distance	=	$token->getDistanceToRoot();
			
			if ( is_null( $min) || $distance < $min) {
				$min	=	$distance;
			}
		}
		
		return $min;
	}
	
	public function getTokens() {
		return $this->_tokens;
	}
	
	public function addToken( $token) {
		if ( in_array( $token, $this->_tokens, true)) {
			return ;
		}
		
		$this->_tokens[]	=	$token;
	}
	
	/**
	 * {@inheritDoc|
	 * @see \Convo\Core\Workflow\DefaultFilterResult::equals()
	 */
	public function equals( \Convo\Core\Workflow\IRequestFilterResult $result) {
		/** @var GoogleNlSyntaxToken $other_token */
		/** @var GoogleNlSyntaxToken $this_token */

		$res = parent::equals($result);

		if (!$res) {
			return false;
		}

		foreach ($this->getTokens() as $this_token) {
			$found = false;

			foreach ($result->getTokens() as $other_token) {
				if ($other_token->equals($this_token)) {
					$found = true;
				}
			}

			if (!$found) {
				return false;
			}
		}

		foreach ($result->getTokens() as $other_token) {
			$found = false;

			foreach ($this->getTokens() as $this_token) {
				if ($other_token->equals($this_token)) {
					$found = true;
				}
			}

			if (!$found) {
				return false;
			}
		}

		return true;
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.implode( ',', array_map( function ( $token) {
			/** @var GoogleNlSyntaxToken $token */
			return $token->getContent();
		}, $this->_tokens)).']';
	}
}