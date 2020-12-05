<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Contains request filtering result. 
 * 
 * It might be empty or not and it can have populated slot values.
 * @author Tole
 *
 */
interface IRequestFilterResult
{
	/**
	 * @return boolean
	 */
	public function isEmpty();
	
	/**
	 * Tests is the result equals to given one.
	 * @param \Convo\Core\Workflow\IRequestFilterResult $result
	 * @return boolean
	 */
	public function equals(\Convo\Core\Workflow\IRequestFilterResult $result);

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function isSlotEmpty($name);
	
	/**
	 * Returns slot value
	 * @param string $name
	 * @return mixed
	 */
	public function getSlotValue($name);
	
	/**
	 * Set the slot value
	 * @param string $name
	 * @param mixed $value
	 */
	public function setSlotValue($name, $value);
	
	/**
	 * Reads passed results into own data.
	 * @param \Convo\Core\Workflow\IRequestFilterResult ...$results
	 */
	public function read(\Convo\Core\Workflow\IRequestFilterResult ...$results);
	
	/**
	 * WIll return associative array with slot values.
	 * @return array
	 */
	public function getData();
	
}