#!/usr/bin/env php
<?php

declare(strict_types=1);

$hasDbusExtension = extension_loaded('dbus') || extension_loaded('php_dbus') || extension_loaded('php-dbus');
$hasBusctl = commandLooksAvailable('busctl');
$hasGdbus = commandLooksAvailable('gdbus');
$hasDbusDaemon = commandLooksAvailable('dbus-daemon');
$hasSystemBusSocket = file_exists('/run/dbus/system_bus_socket');

$missingCapabilities = [];
if (!$hasDbusExtension) {
    $missingCapabilities[] = 'php-dbus-runtime-adapter';
}

if (!$hasDbusDaemon) {
    $missingCapabilities[] = 'dbus-daemon';
}

if (!$hasSystemBusSocket) {
    $missingCapabilities[] = 'system-bus-socket';
}

if (!$hasBusctl && !$hasGdbus) {
    $missingCapabilities[] = 'dbus-client-tooling';
}

$ready = $missingCapabilities === [];

$payload = [
    'api_version' => '1.0',
    'kind' => 'system-api-dbus-runtime-diagnosis',
    'ready' => $ready,
    'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
    'bus_name' => 'org.w4.System1',
    'object_path' => '/org/w4/System1',
    'interface' => 'org.w4.System1.Manager',
    'environment' => [
        'os_family' => PHP_OS_FAMILY,
        'php_binary' => PHP_BINARY,
        'php_version' => PHP_VERSION,
    ],
    'probes' => [
        'php_dbus_extension' => $hasDbusExtension,
        'busctl' => $hasBusctl,
        'gdbus' => $hasGdbus,
        'dbus_daemon' => $hasDbusDaemon,
        'system_bus_socket' => $hasSystemBusSocket,
    ],
    'missing_capabilities' => $missingCapabilities,
];

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
    fwrite(STDERR, "ERROR: No se pudo serializar el payload de diagnostico.\n");
    exit(1);
}

echo $encoded . PHP_EOL;
exit($ready ? 0 : 2);

function commandLooksAvailable(string $command): bool
{
    if (PHP_OS_FAMILY === 'Windows') {
        $resolved = shell_exec(sprintf('where %s 2>NUL', escapeshellarg($command)));
    } else {
        $resolved = shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($command)));
    }

    return is_string($resolved) && trim($resolved) !== '';
}
