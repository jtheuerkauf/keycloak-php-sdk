<?php

/*
 * Use this bootstrap for minimal localized testing.
 */

declare(strict_types=1);

use App\Tests\TestClient;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv(false))->loadEnv(__DIR__ . '/.env');

return TestClient::createClient();
