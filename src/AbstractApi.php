<?php

declare(strict_types=1);

namespace Keycloak;

use Keycloak\Exception\KeycloakCredentialsException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractApi
{
    protected KeycloakClient $client;
    protected string $grantType = 'client_credentials';
    protected array $credentials = [];

    /**
     * @param KeycloakClient $client
     * @param array          $credentials [ 'grant-type' => [ 'id', 'secret' ] ]
     */
    public function __construct(
        KeycloakClient $client,
        #[\SensitiveParameter]
        array $credentials = []
    ) {
        $this->client = $client;

        foreach ($credentials as $grantType => $idSecretPair) {
            $this->setCredentials($grantType, ...$idSecretPair);
        }
    }

    public function setCredentials(
        string $grantType,
        string $identity,
        #[\SensitiveParameter]
        string $secret
    ): void {
        $this->credentials[strtolower($grantType)] = [$identity, $secret];
    }

    public function unsetCredentials(string $grantType): void
    {
        unset($this->credentials[$grantType]);
    }

    /**
     * @param string $grantType
     * @param array $credentials
     *
     * @return $this
     */
    public function setGrantType(
        string $grantType,
        #[\SensitiveParameter]
        array $credentials = []
    ): self {
        if (empty($this->credentials[$grantType]) && count($credentials) !== 2) {
            throw new KeycloakCredentialsException(
                sprintf('Cannot use grant type "%s": credentials not found, and were not provided', $grantType),
            );
        }
        $this->grantType = $grantType;
        if ($credentials) {
            $this->setCredentials($grantType, ...array_values($credentials));
        }

        return $this;
    }

    protected function sendRequest(string $method, string $path, $body = null, array $headers = []): ResponseInterface
    {
        return $this->client
            ->setGrantType($this->grantType, $this->credentials[$this->grantType] ?? [])
            ->sendRequest($method, $path, $body, $headers);
    }
}
