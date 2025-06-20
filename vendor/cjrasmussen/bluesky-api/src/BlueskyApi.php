<?php

namespace cjrasmussen\BlueskyApi;

use JsonException;
use RuntimeException;

/**
 * Class for interacting with the Bluesky API/AT protocol
 */
class BlueskyApi
{
	private string $defaultApiHost;
	private ?string $lastResponseHeader = null;
	private ?object $activeSession = null;

	public function __construct(string $api_host = 'bsky.social')
	{
		$this->defaultApiHost = $this->sanitizeApiHost($api_host);
	}

	/**
	 * Authorize a user
	 *
	 * If handle and password are provided, a new session will be created. If a refresh token is provided, the session
	 * will be refreshed.
	 *
	 * @param string $handleOrToken
	 * @param string|null $app_password
	 * @return bool
	 * @throws RuntimeException|JsonException
	 */
	public function auth(string $handleOrToken, ?string $app_password = null): bool
	{
		if (($handleOrToken) && ($app_password)) {
			$data = $this->startNewSession($handleOrToken, $app_password);
		} else {
			$data = $this->refreshSession($handleOrToken);
		}

		if ($data) {
			return (bool)$data->did;
		}

		return false;
	}

	/**
	 * Check to see if the current session is active
	 *
	 * @return object|null
	 * @throws JsonException
	 */
	public function getSession(): ?object
	{
		$data = $this->request('GET', 'com.atproto.server.getSession');

		if (empty($data->error)) {
			$this->activeSession = $data;
		} else {
			$this->activeSession = null;
		}

		return $data;
	}

	/**
	 * Check to see if the current session is active
	 *
	 * @return bool
	 * @throws JsonException
	 */
	public function isSessionActive(): bool
	{
		$this->getSession();
		return ($this->activeSession !== null);
	}

	/**
	 * Get the endpoint URI from the session if available
	 *
	 * @return string
	 */
	public function getSessionHost(): string
	{
		if (($this->activeSession) && (isset($this->activeSession->didDoc)) && (is_array($this->activeSession->didDoc->service))) {
			foreach ($this->activeSession->didDoc->service AS $service) {
				if (!empty($service->serviceEndpoint)) {
					return $this->sanitizeApiHost($service->serviceEndpoint);
				}
			}
		}

		return $this->defaultApiHost;
	}

	/**
	 * Get the current account DID
	 *
	 * @return string
	 */
	public function getAccountDid(): ?string
	{
		if ($this->activeSession) {
			return $this->activeSession->did;
		}

		return null;
	}

	/**
	 * Get the refresh token
	 *
	 * @return string
	 */
	public function getRefreshToken(): ?string
	{
		return $this->activeSession->refreshJwt;
	}

	/**
	 * Get the response header from the most recent API request
	 *
	 * @return ?string
	 */
	public function getLastResponseHeader(): ?string
	{
		return $this->lastResponseHeader;
	}

	/**
	 * Make a request to the Bluesky API
	 *
	 * @param string $type
	 * @param string $request
	 * @param array $args
	 * @param string|null $body
	 * @param string|null $content_type
	 * @param string|null $endpoint_host
	 * @param string|null $token
	 * @return ?object
	 * @throws JsonException
	 */
	public function request(string $type, string $request, array $args = [], ?string $body = null, ?string $content_type = null, ?string $endpoint_host = null, ?string $token = null): ?object
	{
		if ($endpoint_host) {
			$endpoint_host = $this->sanitizeApiHost($endpoint_host);
		}

		$url = $this->formatApiUri($endpoint_host ?: $this->getApiHost()) . $request;

		if (($type === 'GET') && (count($args))) {
			$url .= '?' . http_build_query($args);
		} elseif (($type === 'POST') && (!$content_type)) {
			$content_type = 'application/json';
		}

		$headers = [];

		if ($token) {
			$headers[] = 'Authorization: Bearer ' . $token;
		} elseif ($this->activeSession) {
			$headers[] = 'Authorization: Bearer ' . $this->activeSession->accessJwt;
		}

		$headers[] = 'Accept: application/json';

		if ($content_type) {
			$headers[] = 'Content-Type: ' . $content_type;

			if (($content_type === 'application/json') && (count($args))) {
				$body = json_encode($args, JSON_THROW_ON_ERROR);
				$args = [];
			}
		}

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);

		if (count($headers)) {
			curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		}

		switch ($type) {
			case 'POST':
				curl_setopt($c, CURLOPT_POST, 1);
				break;
			case 'GET':
				curl_setopt($c, CURLOPT_HTTPGET, 1);
				break;
			default:
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
		}

		if ($body) {
			curl_setopt($c, CURLOPT_POSTFIELDS, $body);
		} elseif (($type !== 'GET') && (count($args))) {
			curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($args, JSON_THROW_ON_ERROR));
		} elseif ($type === 'POST') {
			curl_setopt($c, CURLOPT_POSTFIELDS, null);
		}

		curl_setopt($c, CURLOPT_HEADER, 1);
		curl_setopt($c, CURLOPT_VERBOSE, 0);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_ENCODING, '');
		curl_setopt($c, CURLOPT_MAXREDIRS, 10);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($c, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 1);
		$response = curl_exec($c);
		$header_length = curl_getinfo($c, CURLINFO_HEADER_SIZE);
		curl_close($c);

		if (!$response) {
			return null;
		}

		$this->lastResponseHeader = substr($response, 0, $header_length);
		$body = substr($response, $header_length);

		return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Start a new user session using handle and app password
	 *
	 * @param string $handle
	 * @param string $app_password
	 * @return ?object
	 * @throws RuntimeException|JsonException
	 */
	private function startNewSession(string $handle, string $app_password): ?object
	{
		$this->activeSession = null;

		$args = [
			'identifier' => $handle,
			'password' => $app_password,
		];
		$data = $this->request('POST', 'com.atproto.server.createSession', $args);

		if (($data !== null) && (!empty($data->error))) {
			throw new RuntimeException($data->message);
		}

		$this->activeSession = $data;

		return $data;
	}

	/**
	 * Refresh a user session using a refresh token
	 *
	 * @param string $refresh_token
	 * @return ?object
	 * @throws RuntimeException|JsonException
	 */
	private function refreshSession(string $refresh_token): ?object
	{
		$data = $this->request('POST', 'com.atproto.server.refreshSession', [], null, null, null, $refresh_token);

		if (($data !== null) && (!empty($data->error))) {
			throw new RuntimeException($data->message);
		}

		$this->activeSession = $data;

		return $data;
	}

	/**
	 * Determine the appropriate API host to use
	 *
	 * @return string
	 */
	private function getApiHost(): string
	{
		if ($this->activeSession) {
			return $this->getSessionHost();
		}

		return $this->defaultApiHost;
	}

	/**
	 * Sanitize a URI for use as an API host
	 *
	 * @param string $api_host
	 * @return string|null
	 */
	private function sanitizeApiHost(string $api_host): ?string
	{
		$output = parse_url($api_host, PHP_URL_HOST);

		if ($output === null) {
			$api_host = 'https://' . $api_host;
			$output = parse_url($api_host, PHP_URL_HOST);
		}

		if ($output === false) {
			return null;
		}

		return $output;
	}

	/**
	 * Convert the API host into a proper API URI
	 *
	 * @param string $api_host
	 * @return string
	 */
	private function formatApiUri(string $api_host): string
	{
		return 'https://' . $api_host . '/xrpc/';
	}
}
