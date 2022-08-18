<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IConvoRequest
{

	/**
	 *
	 * @return boolean
	 */
	public function isLaunchRequest();

    /**
     *
     * @return boolean
     */
    public function isSessionStart();

	/**
	 *
	 * @return boolean
	 */
	public function isSessionEndRequest();


	/**
	 * Get Convoworks service id
	 * @return string
	 */
	public function getServiceId();

	/**
	 *
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * @return string
	 */
	public function getText();

	/**
	 * Finds and returns an access token if one is present, null otherwise.
	 * @return string|null
	 */
	public function getAccessToken();

	/**
	 * Uique device id
	 * @return string
	 */
	public function getDeviceId();

	/**
	 * Unique application id
	 * @return string
	 */
	public function getApplicationId();

	/**
	 * On amazon, each installation (installation == enable skill) has unique id
	 * @return string
	 */
	public function getInstallationId();

	/**
	 * Conversation session id.
	 * @return string
	 */
	public function getSessionId();

	/**
	 * Unique request id.
	 * @return string
	 */
	public function getRequestId();

	/**
	 * Get platform raw request as associative array
	 * @return array
	 */
	public function getPlatformData();

	/**
	 * Get platform identification
	 * @return string
	 */
	public function getPlatformId();

    /**
     * @return boolean
     */
    public function isMediaRequest();

    /**
     * @return string
     */
    public function getMediaTypeRequest();

	/**
     * @return boolean
     */
    public function isSalesRequest();

    /**
     * @return boolean
     */
    public function getIsCrossSessionCapable();

    /**
     * @return boolean
     */
    public function isHealthCheck();

}
