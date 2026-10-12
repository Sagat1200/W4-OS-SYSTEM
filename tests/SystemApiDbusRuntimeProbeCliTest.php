<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeProbeCliTest extends TestCase
{
    public function testProbeReportsPendingRuntimeWhenCapabilitiesAreMissing(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertFalse($payload['ready']);
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertContains('php-dbus-runtime-adapter', $payload['missing_capabilities']);
        self::assertContains('dbus-client-tooling', $payload['missing_capabilities']);
        self::assertContains('dbus-daemon', $payload['missing_capabilities']);
        self::assertContains('system-bus-socket', $payload['missing_capabilities']);
    }

    public function testProbeCanRequireReadyAndFailWithExitCodeTwo(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--require-ready',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
        ]);

        self::assertSame(2, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertFalse($payload['ready']);
    }

    public function testProbeReportsReadyWhenCapabilitiesArePresent(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--require-ready',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertTrue($payload['ready']);
        self::assertSame('ready-for-runtime-adapter', $payload['status']);
        self::assertSame([], $payload['missing_capabilities']);
        self::assertTrue($payload['probes']['php_dbus_extension']);
        self::assertTrue($payload['probes']['busctl']);
        self::assertTrue($payload['probes']['dbus_daemon']);
        self::assertTrue($payload['probes']['system_bus_socket']);
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $extraEnvironment
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, array $extraEnvironment): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = array_merge($_ENV, $extraEnvironment);
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__), $environment);
        self::assertIsResource($process, 'No se pudo ejecutar check_system_api_dbus_runtime.php');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'check_system_api_dbus_runtime.php';
    }
}
