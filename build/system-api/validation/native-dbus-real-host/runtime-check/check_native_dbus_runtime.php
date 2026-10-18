<?php

declare(strict_types=1);

$busName = $argv[1] ?? 'org.w4.System1.Test';
$busMode = strtolower($argv[2] ?? 'system');
$busConstant = match ($busMode) {
    'session' => Dbus::BUS_SESSION,
    default => Dbus::BUS_SYSTEM,
};

$result = [
    'ok' => false,
    'bus_name' => $busName,
    'bus_mode' => $busMode,
    'extension_loaded' => extension_loaded('dbus'),
    'class_exists' => class_exists('Dbus'),
    'has_bus_system' => defined('Dbus::BUS_SYSTEM'),
    'has_request_name' => class_exists('Dbus') && method_exists('Dbus', 'requestName'),
    'has_register_object' => class_exists('Dbus') && method_exists('Dbus', 'registerObject'),
    'has_wait_loop' => class_exists('Dbus') && method_exists('Dbus', 'waitLoop'),
];

if (!$result['extension_loaded'] || !$result['class_exists'] || !$result['has_bus_system']) {
    $result['error'] = 'native-dbus-runtime-api-incomplete';
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(1);
}

try {
    $dbus = new Dbus($busConstant);
    $result['constructor'] = 'ok';

    $requestResult = $dbus->requestName($busName);
    $result['request_name'] = [
        'ok' => true,
        'return_value' => $requestResult,
    ];

    $result['ok'] = true;
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (Throwable $throwable) {
    $result['error'] = $throwable::class;
    $result['message'] = $throwable->getMessage();
    $result['code'] = $throwable->getCode();
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(1);
}
