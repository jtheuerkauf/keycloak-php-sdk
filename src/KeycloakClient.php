<?php

namespace Keycloak;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Keycloak\Exception\KeycloakCredentialsException;
use Keycloak\Exception\KeycloakException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;

class KeycloakClient
{
    /**
     * @var GenericProvider
     */
    private $oauthProvider;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var string
     */
    private $realm;

    /**
     * KeycloakClient constructor.
     * @param string $clientId
     * @param string $clientSecret
     * @param string $realm
     * @param string $url
     * @param string|null $altAuthRealm
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $realm,
        string $url,
        ?string $altAuthRealm = null
    ) {
        $this->realm = $realm;

        $authRealm = $altAuthRealm ?: $realm;
        $this->oauthProvider = new GenericProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'urlAccessToken' => "$url/auth/realms/$authRealm/protocol/openid-connect/token",
            'urlAuthorize' => '',
            'urlResourceOwnerDetails' => '',
        ]);
        $this->guzzleClient = new GuzzleClient(['base_uri' => "$url/auth/admin/realms/"]);
    }

    public function sendRealmlessRequest(string $method, string $uri, $body = null, array $headers = []): ResponseInterface
    {
        try {
            $accessToken = $this->oauthProvider->getAccessToken('client_credentials');
        } catch (Exception $ex) {
            throw new KeycloakCredentialsException();
        }

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        $request = $this->oauthProvider->getAuthenticatedRequest(
            $method,
            $uri,
            $accessToken,
            ['headers' => $headers, 'body' => json_encode($body)]
        );

        try {
            return $this->guzzleClient->send($request);
        } catch (GuzzleException $ex) {
            throw new KeycloakException(
                $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param mixed $body
     * @param array $headers
     * @return ResponseInterface
     * @throws KeycloakException
     */
    public function sendRequest(string $method, string $uri, $body = null, array $headers = []): ResponseInterface
    {
        return $this->sendRealmlessRequest(
            $method,
            "{$this->realm}/$uri",
            $body,
            $headers
        );
    }

    /**
     * @return GenericProvider
     */
    public function getOAuthProvider(): GenericProvider
    {
        return $this->oauthProvider;
    }
}
