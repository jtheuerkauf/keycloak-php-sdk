<?php

declare(strict_types=1);

namespace App\Tests;

use Keycloak\AbstractApi;
use Keycloak\Exception\KeycloakCredentialsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AbstractApiTest extends TestCase
{
    /**
     * @var AbstractApi|MockObject
     */
    private $api;

    /**
     * @test
     * @throws \ReflectionException
     */
    public function construct_WithAdditionalCredentials(): void
    {
        $this->makeNewAbstract(['flimflam' => ['foo', 'bar']]);

        $reflection = new ReflectionProperty($this->api, 'credentials');
        $reflection->setAccessible(true);

        $credentials = $reflection->getValue($this->api);
        $this->assertArrayHasKey('flimflam', $credentials);
        $this->assertSame(['foo', 'bar'], $credentials['flimflam']);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setCredentials(): void
    {
        $this->makeNewAbstract();

        $reflection = new ReflectionProperty($this->api, 'credentials');
        $reflection->setAccessible(true);

        $this->api->setCredentials('flimflam', 'foo', 'bar');
        // Get the value from the attached client, not the API
        $credentials = $reflection->getValue($this->api);
        $this->assertArrayHasKey('flimflam', $credentials);
        $this->assertSame(['foo', 'bar'], $credentials['flimflam']);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function unsetCredentials(): void
    {
        $this->makeNewAbstract();

        $reflection = new ReflectionProperty($this->api, 'credentials');
        $reflection->setAccessible(true);

        $this->api->setCredentials('flimflam', 'foo', 'bar');
        // Get the value from the attached client, not the API
        $credentials = $reflection->getValue($this->api);
        $this->assertArrayHasKey('flimflam', $credentials);

        $this->api->unsetCredentials('flimflam');
        $credentials = $reflection->getValue($this->api);
        $this->assertArrayNotHasKey('flimflam', $credentials);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setGrantType_WithNewCredentials(): void
    {
        $this->makeNewAbstract();

        $refCredentials = new ReflectionProperty($this->api, 'credentials');
        $refCredentials->setAccessible(true);
        $refGrantType = new ReflectionProperty($this->api, 'grantType');
        $refGrantType->setAccessible(true);

        $this->api->setGrantType('flimflam', ['foo', 'bar']);
        $credentials = $refCredentials->getValue($this->api);
        $this->assertArrayHasKey('flimflam', $credentials);
        $this->assertSame(['foo', 'bar'], $credentials['flimflam']);
        $grantType = $refGrantType->getValue($this->api);
        $this->assertSame('flimflam', $grantType);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function setGrantType_WithOnlyExistingCredentials(): void
    {
        $this->makeNewAbstract();

        $refCredentials = new ReflectionProperty($this->api, 'credentials');
        $refCredentials->setAccessible(true);
        $refGrantType = new ReflectionProperty($this->api, 'grantType');
        $refGrantType->setAccessible(true);

        $this->api->setCredentials('flipflop', 'bleep', 'bloop');
        $this->api->setCredentials('flimflam', 'foo', 'bar');
        $grantType = $refGrantType->getValue($this->api);
        $this->assertSame('client_credentials', $grantType);

        $this->api->setGrantType('flipflop');
        $grantType = $refGrantType->getValue($this->api);
        $this->assertSame('flipflop', $grantType);
    }

    /**
     * @test
     */
    public function setGrantType_WithNoCredentials(): void
    {
        $this->makeNewAbstract();

        $this->expectExceptionObject(
            new KeycloakCredentialsException(
                'Cannot use grant type "flipflop": credentials not found, and were not provided',
            )
        );
        $this->api->setGrantType('flipflop');
    }

    /**
     * @param array $credentials
     *
     * @return void
     */
    private function makeNewAbstract(
        #[\SensitiveParameter]
        array $credentials = []
    ): void {
        $this->api = $this->getMockForAbstractClass(
            AbstractApi::class,
            [TestClient::createClient(), $credentials],
        );
    }
}
