<?php declare(strict_types=1);

namespace Convo\Core;

use Exception;

interface IAdminUserDataProvider
{
    const BASE_CONFIG = [
        'amazon' => [
            'client_id' => '',
            'client_secret' => ''
        ]
    ];

	/**
	 * @param string $username
	 * @return IAdminUser
	 * @throws DataItemNotFoundException
	 */
    public function findUser( $username);

    /**
     * @return IAdminUser[]
     * @throws Exception
     */
    public function getUsers();


	/**
	 * @param string $userId
	 * @return array
	 */
	public function getPlatformConfig( $userId);

	/**
	 * @param string $userId
	 * @param array $config
	 */
	public function updatePlatformConfig( $userId, $config);
}
