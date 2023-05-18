<?php

/*
 * Use this bootstrap for minimal localized testing.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$ini = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_TYPED);
$_ENV ??= [];
foreach ($ini as $name => $value) {
    if (!array_key_exists($name, $_ENV)) {
        $_ENV[$name] = $value;
    }
    if (!array_key_exists($name, $_SERVER)) {
        $_SERVER[$name] = $value;
    }
}
