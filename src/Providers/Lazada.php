<?php

declare(strict_types=1);

namespace Vocweb\Oauth2Lazada\Providers;

use League\OAuth2\Client\OptionProvider\HttpBasicAuthOptionProvider;
use League\OAuth2\Client\OptionProvider\PostAuthOptionProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Vocweb\Oauth2Lazada\Grants\LazadaAuthorizationCodeGrant;
use Vocweb\Oauth2Lazada\Grants\LazadaRefreshTokenGrant;

class Lazada extends AbstractProvider
{
	use BearerAuthorizationTrait;

	/**
	 * Name of the resource owner identifier field that is
	 * present in the access token response (if applicable)
	 */
	const ACCESS_TOKEN_RESOURCE_OWNER_ID = null;

	/**
	 * Default host
	 */
	protected $host = 'https://auth.lazada.com/rest';

	/**
	 * Api authenticate endpoint
	 * Doc: https://open.lazada.com/apps/doc/doc?nodeId=10777&docId=108260
	 *
	 * @var string
	 */
	public $apiAuthEndPoint = 'https://auth.lazada.com/oauth/authorize';

	/**
	 * Api token endpoint
	 * Doc: https://open.lazada.com/apps/doc/api?path=%2Fauth%2Ftoken%2Fcreate
	 *
	 * @var string
	 */
	public $apiTokenEndPoint = 'https://auth.lazada.com/rest/auth/token/create';

	public function __construct(array $options = [], array $collaborators = [])
	{
		parent::__construct($options, $collaborators);

		$this->getGrantFactory()->setGrant('authorization_code', new LazadaAuthorizationCodeGrant());
		$this->getGrantFactory()->setGrant('refresh_token', new LazadaRefreshTokenGrant());
		// $this->setOptionProvider(new LazadaOptionProvider());
		$this->setOptionProvider(new HttpBasicAuthOptionProvider());
		// $this->setOptionProvider(new PostAuthOptionProvider());
	}

	/**
	 * Get authorization url to start the oauth-flow
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl(): string
	{
		return $this->apiAuthEndPoint;
	}

	/**
	 * Get access token url to retrieve token
	 *
	 * @param array $params
	 * @return string
	 */
	public function getBaseAccessTokenUrl(array $params): string
	{
		return $this->apiTokenEndPoint;
	}

	public function getAccessTokenUrl(array $params): string
	{
		if ($params['grant_type'] === 'refresh_token') {
			// Refresh token requires calling a different URL
			return $this->apiTokenEndPoint;
		}

		return $this->apiTokenEndPoint;
	}

	/**
	 * Set authorization parameters
	 */
	protected function getAuthorizationParameters(array $options): array
	{
		$options = parent::getAuthorizationParameters($options);

		$options['client_id'] 	= $options['client_id'];
		$options['force_auth'] 	= "true";
		$options['country'] 	= "vn";

		return $options;
	}

	// protected function prepareAccessTokenResponse(array $result): array
	// {
	// 	$result['data']['resource_owner_id'] = $result['data']['open_id'];
	// 	return $result['data'];
	// }


	/**
	 * @param null|AccessToken $token
	 * @return string[]
	 */
	// protected function getAuthorizationHeaders($token = null): array
	// {
	// 	return ['Authorization' => 'Bearer ' . $token->getToken()];
	// }

	/**
	 * Get provider URl to fetch the user info.
	 */
	/**
	 * Get provider url to fetch user details
	 *
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl(AccessToken $token): string
	{
		return 'https://api.lazada.vn/integration/v2/sellers/me';
	}

	/**
	 * Requests and returns the resource owner of given access token.
	 *
	 * @throws IdentityProviderException
	 */
	public function fetchResourceOwnerDetails(AccessToken $token): array
	{
		$url = $this->getResourceOwnerDetailsUrl($token);

		$options = [
			'headers' => $this->getDefaultHeaders(),
			'body' => json_encode(
				[
					'open_id' => $token->getResourceOwnerId(),
					'access_token' => $token->getToken(),
					'fields' => [
						"open_id",
						"union_id",
						"avatar_url",
						"avatar_url_100",
						"avatar_url_200",
						"avatar_large_url",
						"display_name",
						"profile_deep_link",
						"bio_description",
					],
				]
			),
		];

		$request = $this->createRequest(self::METHOD_POST, $url, null, $options);

		return $this->getParsedResponse($request);
	}

	/**
	 * Checks a provider response for errors.
	 *
	 * @throws IdentityProviderException
	 */
	public function checkResponse(ResponseInterface $response, $data): void
	{
		if (isset($data['error']) && $data['error_description']) {
			$err = $data['error'] . ";\n\r " . $data['error_description'];
			if (isset($data['error_hint']) && $data['error_hint']) {
				$err .= ";\n\r " . $data['error_hint'];
			}
			throw new IdentityProviderException(
				$err,
				$data['status_code'],
				$data
			);
		}

		if (isset($data['error']['code']) && $data['error']['code']) {
			throw new IdentityProviderException(
				$data['error']['message'],
				$data['error']['code'],
				$data
			);
		}

		if (isset($data['data']['error_code']) && $data['data']['error_code']) {
			throw new IdentityProviderException(
				$data['data']['description'],
				$data['data']['error_code'],
				$data
			);
		}

		if ($response->getStatusCode() === 401) {
			throw new IdentityProviderException(
				$response->getReasonPhrase(),
				$response->getStatusCode(),
				$data
			);
		}
	}

	public function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
	{
		return new LazadaResourceOwner($response);
	}

	/**
	 * Get the default scopes used by this provider.
	 *
	 * This should not be a complete list of all scopes, but the minimum
	 * required for the provider user interface!
	 *
	 * @return array
	 */
	public function getDefaultScopes(): array
	{
		return [
			'order product inventory offline',
		];
	}
}
