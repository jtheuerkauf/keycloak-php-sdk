<?php

declare(strict_types=1);

use Keycloak\Realm\RealmApi;
use Symfony\Component\Dotenv\Dotenv;

require_once 'vendor/autoload.php';

(new Dotenv(false))->loadEnv(__DIR__ . '/.env');

require_once 'TestClient.php';

$realmApi = new RealmApi($client);
$existingRealm = $realmApi->find();

if ($existingRealm !== null) {
    $client->sendRequest('DELETE', '');
}

$realm = $_SERVER['KC_REALM'];
$client->sendRealmlessRequest('POST', '', [
    'enabled' => true,
    'id' => $realm,
    'realm' => $realm
]);

$smtpConfig = [
    'smtpServer' => [
        'port' => $_SERVER['SMTP_PORT'],
        'host' => $_SERVER['SMTP_HOST'],
        'from' => $_SERVER['SMTP_FROM']
    ]
];
$client->sendRequest('PUT', '', $smtpConfig);
