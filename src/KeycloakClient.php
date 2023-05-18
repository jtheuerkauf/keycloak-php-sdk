<?php

namespace Keycloak;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Keycloak\Exception\KeycloakCredentialsException;
use Keycloak\Exception\KeycloakException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;

class KeycloakClient
{
    private array $credentials = [];
    /**
     * @var GenericProvider
     */
    private GenericProvider $oauthProvider;
    /**
     * @var GuzzleClient
     */
    private GuzzleClient $guzzleClient;
    /**
     * @var string
     */
    private string $realm;

    private string $grantType = 'client_credentials';

    /**
     * KeycloakClient constructor.
     *
     * @deprecated This constructor signature will change to support a "credentials" array
     *             where ID/Secret pairs are keyed by grant-type.
     *             $basePath will also change to empty string as default to align with more recent versions of Keycloak.
     *
     * @param string      $clientId
     * @param string      $clientSecret
     * @param string      $realm
     * @param string      $url
     * @param string|null $altAuthRealm
     * @param string      $basePath Version 17+ removed the fixed <tt>/auth</tt> base-path.
     *                              The relative base can still be set with <tt>"--http-relative-path=/..."</tt> when
     *                              Keycloak is started, so this argument should match that setting.
     *                              If no relative path is in use: <tt>"/"</tt> or <tt>""</tt>.
     */
    public function __construct(
        string $clientId,
        #[\SensitiveParameter]
        string $clientSecret,
        string $realm,
        string $url,
        ?string $altAuthRealm = null,
        string $basePath = '/auth'
    ) {
        $this->realm = $realm;
        $this->setCredentials('client_credentials', $clientId, $clientSecret);
        $baseUrl = trim(rtrim($url, '/') . '/' . ltrim($basePath, '/'), '/');
        $authRealm = $altAuthRealm ?: $realm;
        $this->oauthProvider = new GenericProvider(
            [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'urlAccessToken' => "{$baseUrl}/realms/{$authRealm}/protocol/openid-connect/token",
                'urlAuthorize' => '',
                'urlResourceOwnerDetails' => '',
            ]
        );
        $this->guzzleClient = new GuzzleClient(['base_uri' => "{$baseUrl}/admin/realms/"]);
    }

    public function getClientCredentials()
    {
        return $this->credentials['client_credentials'];
    }

    public function setCredentials(
        string $grantType,
        string $identity,
        #[\SensitiveParameter]
        string $secret
    ): self {
        $this->credentials[strtolower($grantType)] = [$identity, $secret];

        return $this;
    }

    public function setGrantType(
        string $grantType,
        #[\SensitiveParameter]
        array $credentials = []
    ): self {
        if (empty($this->credentials[$grantType]) && count($credentials) !== 2) {
            throw new KeycloakCredentialsException(
                'Cannot use grant type "%s": credentials not found, and were not provided'
            );
        }
        $this->grantType = $grantType;
        if ($credentials) {
            $this->setCredentials($grantType, ...array_values($credentials));
        }

        return $this;
    }

    public function unsetCredentials(string $grantType): void
    {
        if ($grantType !== 'client_credentials') {
            unset($this->credentials[$grantType]);
        } else {
            throw new KeycloakCredentialsException(
                '"client_credentials" grant type cannot be removed from the base client',
            );
        }
    }

    public function sendRealmlessRequest(
        string $method,
        string $uri,
        $body = null,
        array $headers = []
    ): ResponseInterface {
        try {
            $accessToken = $this->getAccessToken();
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
     * @param mixed  $body
     * @param array  $headers
     *
     * @return ResponseInterface
     * @throws KeycloakException
     */
    public function sendRequest(
        string $method,
        string $uri,
        $body = null,
        array $headers = []
    ): ResponseInterface {
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

    private function getAccessToken(): AccessTokenInterface
    {
        switch ($this->grantType) {
            case 'client_credentials':
                $credentials = $this->credentials['client_credentials'] ?? null;
                if ($credentials) {
                    $bodyKeys = ['client_id', 'client_secret'];
                }
                break;
            case 'password':
                $credentials = $this->credentials['password'] ?? null;
                if ($credentials) {
                    $bodyKeys = ['username', 'password'];
                }
                break;
            default:
                throw new KeycloakException(sprintf('Unsupported grant type: "%s"', $this->grantType));
        }
        if (!$credentials) {
            throw new KeycloakException('Unable to find usable authentication credentials');
        }

        return $this->oauthProvider->getAccessToken($this->grantType, array_combine($bodyKeys, $credentials));
    }
}
