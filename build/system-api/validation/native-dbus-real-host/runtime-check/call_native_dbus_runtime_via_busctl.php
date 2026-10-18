<?php

declare(strict_types=1);

$requestPath = __DIR__ . '/call_system_dbus_runtime.request.json';
$requestJson = trim((string) file_get_contents($requestPath));

$command = [
    'busctl',
    '--system',
    'call',
    'org.w4.System1',
    '/org/w4/System1',
    'org.w4.System1.Manager',
    'Call',
    's',
    $requestJson,
];

$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptorSpec, $pipes);
if (!is_resource($process)) {
    fwrite(STDERR, "No se pudo lanzar busctl\n");
    exit(1);
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($process);

if ($stdout !== false && $stdout !== '') {
    fwrite(STDOUT, $stdout);
}

if ($stderr !== false && $stderr !== '') {
    fwrite(STDERR, $stderr);
}

exit($exitCode);
