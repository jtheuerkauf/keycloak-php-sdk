<?php

namespace App\Tests;

use GuzzleHttp\Client;
use Keycloak\Exception\KeycloakCredentialsException;
use Keycloak\KeycloakClient;
use League\OAuth2\Client\Provider\GenericProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class KeycloakClientTest extends TestCase
{
    public function testInvalidKeycloakClient(): void
    {
        $brokenClient = new KeycloakClient('this', 'client', 'is', 'http://broken.com');
        $this->expectException(KeycloakCredentialsException::class);
        $brokenClient->sendRequest('GET', '');
    }

    public function testValidKeycloakClient(): void
    {
        $client = TestClient::createClient();
        $res = $client->sendRequest('GET', '');
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testKeycloakClientBaseUrl(): void
    {
        /*
         * The test Reflects into the instance because there's no other way to see the base URL built
         * from combining $url + $basePath arguments.
         */

        /*
         * Default
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/auth/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (default)",
        );
        $this->assertEquals(
            'http://localhost/auth/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (default)",
        );

        /*
         * Custom "foobar"
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, 'foobar');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/foobar/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (custom as 'foobar')",
        );
        $this->assertEquals(
            'http://localhost/foobar/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (custom as 'foobar')",
        );
        /*
         * Custom "/foobar/"
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, '/foobar/');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/foobar/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (custom as '/foobar/')",
        );
        $this->assertEquals(
            'http://localhost/foobar/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (custom as '/foobar/')",
        );
        /*
         * Custom "/boofar"
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, '/boofar');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/boofar/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (custom as '/boofar')",
        );
        $this->assertEquals(
            'http://localhost/boofar/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (custom as '/boofar')",
        );
        /*
         * Custom "boofar/"
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, 'boofar/');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/boofar/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (custom as 'boofar/')",
        );
        $this->assertEquals(
            'http://localhost/boofar/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (custom as 'boofar/')",
        );

        /*
         * None ""
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, '');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (none as '')",
        );
        $this->assertEquals(
            'http://localhost/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (none as '')",
        );
        /*
         * None "/"
         */
        $client = new KeycloakClient('foo', 'bar', 'master', 'http://localhost', null, '/');
        [$oauthProvider, $guzzleClient] = $this->extractClientURLs($client);
        $this->assertEquals(
            'http://localhost/realms/master/protocol/openid-connect/token',
            $oauthProvider,
            "The OAuth provider URLs don't match (none as '/')",
        );
        $this->assertEquals(
            'http://localhost/admin/realms/',
            $guzzleClient,
            "The OAuth provider URLs don't match (none as '/')",
        );
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    /**
     * @test
     * @throws \ReflectionException
     */
    public function setCredentials(): void
    {
        $client = TestClient::createClient();
        $reflection = new ReflectionProperty($client, 'credentials');
        $reflection->setAccessible(true);

        $client->setCredentials('flimflam', 'foo', 'bar');
        $credentials = $reflection->getValue($client);
        $this->assertArrayHasKey('flimflam', $credentials);
        $this->assertSame(['foo', 'bar'], $credentials['flimflam']);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function unsetCredentials(): void
    {
        $client = TestClient::createClient();
        $reflection = new ReflectionProperty($client, 'credentials');
        $reflection->setAccessible(true);

        $client->setCredentials('flimflam', 'foo', 'bar');
        $credentials = $reflection->getValue($client);
        $this->assertArrayHasKey('flimflam', $credentials);

        $client->unsetCredentials('flimflam');
        $credentials = $reflection->getValue($client);
        $this->assertArrayNotHasKey('flimflam', $credentials);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function unsetCredentials_CannotRemoveClientCredentials(): void
    {
        $client = TestClient::createClient();
        $reflection = new ReflectionProperty($client, 'credentials');
        $reflection->setAccessible(true);

        $this->expectExceptionObject(
            new KeycloakCredentialsException(
                '"client_credentials" grant type cannot be removed from the base client',
            ),
        );
        $client->unsetCredentials('client_credentials');
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setGrantType_WithNewCredentials(): void
    {
        $client = TestClient::createClient();
        $refCredentials = new ReflectionProperty($client, 'credentials');
        $refCredentials->setAccessible(true);
        $refGrantType = new ReflectionProperty($client, 'grantType');
        $refGrantType->setAccessible(true);

        $client->setGrantType('flimflam', ['foo', 'bar']);
        $credentials = $refCredentials->getValue($client);
        $this->assertArrayHasKey('flimflam', $credentials);
        $this->assertSame(['foo', 'bar'], $credentials['flimflam']);
        $grantType = $refGrantType->getValue($client);
        $this->assertSame('flimflam', $grantType);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setGrantType_WithOnlyExistingCredentials(): void
    {
        $client = TestClient::createClient();
        $refCredentials = new ReflectionProperty($client, 'credentials');
        $refCredentials->setAccessible(true);
        $refGrantType = new ReflectionProperty($client, 'grantType');
        $refGrantType->setAccessible(true);

        $client->setCredentials('flipflop', 'bleep', 'bloop');
        $client->setCredentials('flimflam', 'foo', 'bar');
        $grantType = $refGrantType->getValue($client);
        $this->assertSame('client_credentials', $grantType);

        $client->setGrantType('flipflop');
        $grantType = $refGrantType->getValue($client);
        $this->assertSame('flipflop', $grantType);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setGrantType_WithoutAnyCredentials(): void
    {
        $client = TestClient::createClient();
        $refCredentials = new ReflectionProperty($client, 'credentials');
        $refCredentials->setAccessible(true);
        $refGrantType = new ReflectionProperty($client, 'grantType');
        $refGrantType->setAccessible(true);

        $client->setCredentials('flimflam', 'foo', 'bar');

        $this->expectException(KeycloakCredentialsException::class);
        $client->setGrantType('flipflop');
    }

    /**
     * @param KeycloakClient $client
     * @return string[]
     */
    private function extractClientURLs(KeycloakClient $client): array
    {
        $reflection = new \ReflectionClass($client);
        $oauthProvider = $reflection->getProperty('oauthProvider');
        $oauthProvider->setAccessible(true);
        /** @var GenericProvider $oauthProviderInstance */
        $oauthProviderInstance = $oauthProvider->getValue($client);
        $oauthProviderUrl = $oauthProviderInstance->getBaseAccessTokenUrl([]);

        $guzzleClient = $reflection->getProperty('guzzleClient');
        $guzzleClient->setAccessible(true);
        /** @var Client $guzzleClientInstance */
        $guzzleClientInstance = $guzzleClient->getValue($client);
        if (method_exists($guzzleClientInstance, 'getConfig')) {
            $guzzleClientUrl = $guzzleClientInstance->getConfig('base_uri')->jsonSerialize();
        } else {
            $guzzleClientReflection = new \ReflectionClass($guzzleClientInstance);
            $gcConfigReflection = $guzzleClientReflection->getProperty('config');
            $gcConfigReflection->setAccessible(true);
            $guzzleClientUrl = $gcConfigReflection['base_uri']->jsonSerialize();
        }

        return [$oauthProviderUrl, $guzzleClientUrl];
    }
}
