#!/usr/bin/env php
<?php

declare(strict_types=1);

$payload = [
    'api_version' => '1.0',
    'kind' => 'system-api-dbus-bridge-readiness',
    'ready' => false,
    'status' => 'pending-runtime-adapter',
    'required_bus_name' => 'org.w4.System1',
    'required_object_path' => '/org/w4/System1',
    'required_interface' => 'org.w4.System1.Manager',
    'missing_capabilities' => [
        'php-dbus-runtime-adapter',
        'bus-name-ownership',
        'dbus-method-dispatch',
    ],
];

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
    fwrite(STDERR, "ERROR: No se pudo serializar el payload de readiness.\n");
    exit(1);
}

echo $encoded . PHP_EOL;
exit(2);
