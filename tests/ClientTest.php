<?php

namespace App\Tests;

use Keycloak\Client\ClientApi;
use Keycloak\Client\Entity\Client;
use Keycloak\User\Entity\CompositeRole;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected ClientApi $clientApi;

    protected function setUp(): void
    {
        global $client;
        $this->clientApi = new ClientApi($client);
    }

    public function testFindAll(): void
    {
        $allClients = $this->clientApi->findAll();
        $this->assertNotEmpty($allClients);
    }

    public function testFind(): void
    {
        // account is a standard client that should always exist
        $client = $this->clientApi->findByClientId('account');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Client::class, $this->clientApi->find($client->id));
    }

    public function testFindNothing(): void
    {
        $this->assertNull($this->clientApi->findByClientId('blipblop'));
        $this->assertNull($this->clientApi->find('blipblop'));
    }

    public function testGetCompositeRolesWithPermissions(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $compositeRoles = $this->clientApi->getCompositeRolesWithPermissions($client->id);
        $this->assertNotEmpty($compositeRoles);
        $this->assertInstanceOf(CompositeRole::class, $compositeRoles[0]);
    }
}
