<?php
/**
 * @author Juan Pablo Villafáñez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2022, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_external_dropbox;

use OCP\Security\ICredentialsManager;

/**
 * Store and retrieve phpseclib3 RSA private keys
 */
class OAuth2Store {
	/**
	 * Recommended expiration offset the token should have.
	 * The token should be considered expired in t1 + token_duration - offset
	 */
	public const TOKEN_EXP_OFFSET = 600;

	/** @var OAuth2Store */
	private static $oAuth2Store = null;

	/** @var ICredentialsManager */
	private $credentialsManager;

	/**
	 * Get the global instance of the OAuth2Store. If no one is set yet, a new
	 * one will be created using real server components.
	 * @return OAuth2Store
	 */
	public static function getGlobalInstance(): OAuth2Store {
		if (self::$oAuth2Store === null) {
			self::$oAuth2Store = new OAuth2Store(
				\OC::$server->getCredentialsManager()
			);
		}
		return self::$oAuth2Store;
	}

	/**
	 * Set a new OAuth2Store instance as a global instance overwriting whatever
	 * instance was there.
	 * This shouldn't be needed outside of unit tests
	 * @param OAuth2Store|null The OAuth2Store to be set as global instance, or null
	 * to destroy the global instance (destroying the global instance will allow
	 * getting the default one again)
	 */
	public static function setGlobalInstance(?OAuth2Store $oAuth2Store) {
		self::$oAuth2Store = $oAuth2Store;
	}

	/**
	 * @param ICredentialsManager $credentialsManager
	 */
	public function __construct(ICredentialsManager $credentialsManager) {
		$this->credentialsManager = $credentialsManager;
	}

	/**
	 * Store the $accessTokenData. The $clientId will be used as prefix for easier
	 * identification inside the credentials manager. A token will be returned
	 * in order to retrieve the stored key
	 * @param array $accessTokenData the data to be stored
	 * @param string $clientId a prefix for easier identification
	 * @return string an opaque token to be used to retrieve the stored key later
	 */
	public function storeData(array $accessTokenData, string $clientId): string {
		$keyId = \uniqid("dropbox:oauth2:$clientId:", true);
		$this->credentialsManager->store('', $keyId, $accessTokenData);

		$keyData = [
			'keyId' => $keyId
		];

		return \base64_encode(\json_encode($keyData));
	}

	/**
	 * Retrieve a previously stored access token data using the token that was returned
	 * when the data was stored
	 * @param string $token the token returned previously by the "storeData"
	 * method when the key was stored.
	 * @return array the access token data that was stored
	 */
	public function retrieveData(string $token): ?array {
		$keyData = \json_decode(\base64_decode($token), true);
		if ($keyData === null) {
			return null;
		}
		$tokenData = $this->credentialsManager->retrieve('', $keyData['keyId']);
		return $tokenData;
	}

	/**
	 * Update a previously stored access token data using the token that was returned
	 * when the data was stored
	 * @param string $token the token returned previously by the "storeData"
	 * method when the key was stored.
	 */
	public function updateData(string $token, array $accessTokenData) {
		$keyData = \json_decode(\base64_decode($token), true);
		if ($keyData === null) {
			return;
		}
		$this->credentialsManager->store('', $keyData['keyId'], $accessTokenData);
	}
}
