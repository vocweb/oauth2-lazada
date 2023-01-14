<?php

declare(strict_types=1);

namespace Vocweb\Oauth2Lazada\Providers;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Lazada resource owner
 * https://open.lazada.com/apps/doc/api?path=%2Fseller%2Fget
 */
class LazadaResourceOwner implements ResourceOwnerInterface
{
	protected array $response;

	public function __construct(array $response)
	{
		$this->response = $response;
	}

	public function toArray(): array
	{
		return $this->response['data'];
	}

	public function getId(): string
	{
		return $this->response['data']['seller_id'];
	}

	public function getName(): string
	{
		return $this->response['data']['name'];
	}
}
