<?php

declare(strict_types=1);

use W4\OS\SystemApi\NativeDbusExtensionCallbackBridge;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;
use W4\OS\SystemApi\SystemApiDbusRuntimeActivationToolkit;
use W4\OS\SystemApi\SystemApiDbusRuntimeBackendToolkit;
use W4\OS\SystemApi\SystemApiDbusRuntimeProbeToolkit;
use W4\OS\SystemApi\SystemApiManager;
use W4\OS\SystemApi\SystemApiToolkit;

$rootDir = '/home/w4live/w4-os-system';
require $rootDir . '/scripts/bootstrap.php';

$readyFile = '/home/w4live/native-dbus-method-probe-preload-classes-only.ready.json';
$stopFile = '/home/w4live/native-dbus-method-probe-preload-classes-only.stop';
$logFile = '/home/w4live/native-dbus-method-probe-preload-classes-only.events.log';

@unlink($readyFile);
@unlink($stopFile);
@unlink($logFile);

$serviceDefinition = [
    'bus_name' => 'org.w4.System1',
    'object_path' => '/org/w4/System1',
    'interface' => 'org.w4.System1.Manager',
    'service_lifecycle' => 'request-name-register-object-wait-loop',
];

$classesToPreload = [
    NativeDbusExtensionRuntimeObject::class,
    NativeDbusExtensionCallbackBridge::class,
    SystemApiManager::class,
    SystemApiToolkit::class,
    SystemApiDbusRuntimeProbeToolkit::class,
    SystemApiDbusRuntimeActivationToolkit::class,
    SystemApiDbusRuntimeBackendToolkit::class,
];

foreach ($classesToPreload as $className) {
    class_exists($className);
}

$connection = new Dbus(Dbus::BUS_SYSTEM);
$requestNameResult = $connection->requestName($serviceDefinition['bus_name']);
$runtimeObject = new NativeDbusExtensionRuntimeObject($serviceDefinition, $rootDir);
$callbackClass = NativeDbusExtensionCallbackBridge::prime($runtimeObject);
$registerObjectResult = $connection->registerObject(
    $serviceDefinition['object_path'],
    $serviceDefinition['interface'],
    $callbackClass
);

file_put_contents(
    $readyFile,
    json_encode([
        'status' => 'ready',
        'bus_name' => $serviceDefinition['bus_name'],
        'object_path' => $serviceDefinition['object_path'],
        'interface' => $serviceDefinition['interface'],
        'request_name_result' => $requestNameResult,
        'register_object_result' => $registerObjectResult,
        'callback_class' => $callbackClass,
        'pid' => getmypid(),
        'probe_mode' => 'preload-classes-before-connection',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

for ($iteration = 1; $iteration <= 160; $iteration++) {
    if (file_exists($stopFile)) {
        file_put_contents($logFile, "stop-file-detected\n", FILE_APPEND);
        break;
    }

    $result = $connection->waitLoop(250);
    file_put_contents(
        $logFile,
        json_encode([
            'iteration' => $iteration,
            'wait_loop_result' => $result,
            'timestamp' => date(DATE_ATOM),
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

file_put_contents($logFile, "service-loop-finished\n", FILE_APPEND);
