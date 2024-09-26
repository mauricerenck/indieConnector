<?php

namespace cjrasmussen\BlueskyApi;

use JsonException;
use RuntimeException;

class BlueskyApiSessionHelper
{
	private BlueskyApi $blueskyApi;

	public function __construct(BlueskyApi $blueskyApi)
	{
		$this->blueskyApi = $blueskyApi;
	}

	/**
	 * Attempt to auth a session using the contents of a file, falling back on optional handle/password
	 *
	 * Upon successful authorization, the specified file will be updated with the new session token.
	 *
	 * @param string $tokenPath
	 * @param ?string $handle
	 * @param ?string $app_password
	 * @return bool
	 * @throws RuntimeException|JsonException
	 */
	public function auth(string $tokenPath, ?string $handle = null, ?string $app_password = null): bool
	{
		if ($tokenPath === '') {
			throw new RuntimeException('Token path must not be empty string');
		}

		if (($this->blueskyApi->getAccountDid()) && ($this->blueskyApi->isSessionActive())) {
			// ALREADY HAVE AN ACTIVE SESSION
			return true;
		}

		$token = $throwException = null;
		$authed = false;

		if (file_exists($tokenPath)) {
			$token = file_get_contents($tokenPath);
		}

		if ($token) {
			try {
				$authed = $this->blueskyApi->auth($token);
			} catch (RuntimeException|JsonException $e) {
				// CACHE THE EXCEPTION, IT'S OKAY FOR THIS TO FAIL IF THE NEXT ONE SUCCEEDS
				$throwException = $e;
			}
		}

		if ((!$authed) && ($handle) && ($app_password)) {
			try {
				$authed = $this->blueskyApi->auth($handle, $app_password);
			} catch (RuntimeException|JsonException $e) {
				// CACHE THE EXCEPTION, WE'LL THROW IT LATER
				$throwException = $e;
			}
		}

		$token = ($authed) ? $this->blueskyApi->getRefreshToken() : '';
		file_put_contents($tokenPath, $token);

		if ((!$authed) && ($throwException)) {
			// AUTH FAILED AND GOT AN EXCEPTION? THROW IT
			throw $throwException;
		}

		return $authed;
	}
}
