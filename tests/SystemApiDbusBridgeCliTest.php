<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusBridgeCliTest extends TestCase
{
    public function testDescribeRuntimeReturnsBridgeMetadata(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeRuntime',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-bridge-runtime', $payload['kind']);
        self::assertSame('local-bridge-ready', $payload['status']);
        self::assertSame('pending-runtime-adapter', $payload['binding_status']);
        self::assertSame('org.w4.System1', $payload['bus_name']);
        self::assertContains('Call', $payload['commands']);
        self::assertContains('serve-stdio-bridge', $payload['commands']);
        self::assertContains('serve-dbus', $payload['commands']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $payload['adapter_specification']['kind']);
        self::assertSame('serve-dbus', $payload['adapter_specification']['activation']['entry_command']);
    }

    public function testGetCapabilitiesDelegatesToSystemApiManager(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'GetCapabilities',
            '--request-id',
            'bridge-cap-001',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('bridge-cap-001', $payload['request_id']);
        self::assertNull($payload['error']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertContains('system.dbus-runtime', array_column($payload['result']['data']['methods'], 'name'));
    }

    public function testCallDelegatesToSystemApiContract(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'bridge-call-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'Call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-runtime-contract', $payload['result']['resource']['provider']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['recovery_plan']);
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['result']['data']['activation_preparation']['kind']);
    }

    public function testServeStdioBridgeProcessesDbusStyleMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeRuntime',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'GetCapabilities',
                'request_id' => 'bridge-stdio-cap-001',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'Call',
                'request' => [
                    'api_version' => '1.0',
                    'request_id' => 'bridge-stdio-call-001',
                    'method' => 'profiles.get',
                    'params' => [
                        'profile_id' => 'w4-os-home',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-bridge',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(3, $responses);
        self::assertSame('system-api-dbus-bridge-runtime', $responses[0]['kind']);
        self::assertSame('bridge-stdio-cap-001', $responses[1]['request_id']);
        self::assertSame('system.capabilities', $responses[1]['result']['method']);
        self::assertSame('bridge-stdio-call-001', $responses[2]['request_id']);
        self::assertSame('profiles.get', $responses[2]['result']['method']);
    }

    public function testServeDbusReturnsActivationReportAndExitCodeThree(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertSame('local-dbus-bridge', $payload['bridge_kind']);
        self::assertSame('serve-dbus', $payload['requested_command']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $payload['adapter_specification']['kind']);
        self::assertArrayHasKey('runtime_diagnosis', $payload);
        self::assertArrayHasKey('missing_capabilities', $payload);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['recovery_plan']);
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, string $stdin = ''): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_bridge.php');

        fwrite($pipes[0], $stdin);
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

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeNdjson(string $content): array
    {
        $responses = [];
        $lines = preg_split('/\r?\n/', trim($content)) ?: [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $responses[] = $decoded;
        }

        return $responses;
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_bridge.php';
    }
}
