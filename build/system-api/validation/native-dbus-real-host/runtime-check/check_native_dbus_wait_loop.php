<?php

declare(strict_types=1);

use W4\OS\SystemApi\NativeDbusExtensionCallbackBridge;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;

$rootDir = '/home/w4live/w4-os-system';
require $rootDir . '/scripts/bootstrap.php';

$serviceDefinition = [
    'bus_name' => 'org.w4.System1',
    'object_path' => '/org/w4/System1',
    'interface' => 'org.w4.System1.Manager',
    'service_lifecycle' => 'request-name-register-object-wait-loop',
];

$result = [
    'constructor' => null,
    'request_name' => null,
    'register_object' => null,
    'wait_loop' => null,
];

fwrite(STDERR, "phase:construct\n");
$connection = new Dbus(Dbus::BUS_SYSTEM);
$result['constructor'] = 'bus-system-constructor';

fwrite(STDERR, "phase:request-name\n");
$result['request_name'] = $connection->requestName($serviceDefinition['bus_name']);

fwrite(STDERR, "phase:register-object\n");
$runtimeObject = new NativeDbusExtensionRuntimeObject($serviceDefinition, $rootDir);
$callbackClass = NativeDbusExtensionCallbackBridge::prime($runtimeObject);
$result['register_object'] = $connection->registerObject(
    $serviceDefinition['object_path'],
    $serviceDefinition['interface'],
    $callbackClass
);

fwrite(STDERR, "phase:before-wait-loop\n");
$result['wait_loop'] = $connection->waitLoop(250);
fwrite(STDERR, "phase:after-wait-loop\n");

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
