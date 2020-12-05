<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IConvoResponse
{

	/**
	 * @param boolean $endSession
	 */
	public function setShouldEndSession( $endSession);

	/**
	 * @return boolean
	 */
	public function shouldEndSession();

	/**
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * @deprecated
	 * @return boolean
	 */
	public function isSsml();

	public function addText($text, $append = false);

	public function addRepromptText($text, $append = false);

	public function getText();

	public function getRepromptText();

	public function getTextSsml();

	public function getRepromptTextSsml();


	/**
	 * Get full platform response
	 * @return array
	 */
	public function getPlatformResponse();

}
