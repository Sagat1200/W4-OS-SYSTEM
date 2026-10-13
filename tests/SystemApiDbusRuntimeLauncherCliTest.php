<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeLauncherCliTest extends TestCase
{
    public function testDescribeLauncherReturnsLauncherContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeLauncher',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-launcher', $payload['kind']);
        self::assertSame('launcher-ready', $payload['status']);
        self::assertContains('DescribeLaunchPlan', $payload['commands']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $payload['launch_plan']['kind']);
        self::assertSame('serve-stdio-host', $payload['launch_plan']['target_runtime']['command']);
    }

    public function testServeDbusReturnsActivationWithLaunchPlan(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('local-runtime-launcher', $payload['bridge_kind']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $payload['launch_plan']['kind']);
    }

    public function testServeStdioLauncherDelegatesToLauncherAndHostMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeLauncher',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'DescribeLaunchPlan',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'Call',
                'arguments' => [
                    'request' => [
                        'api_version' => '1.0',
                        'request_id' => 'runtime-launcher-call-001',
                        'method' => 'system.dbus-runtime',
                        'params' => [],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-launcher',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(3, $responses);
        self::assertSame('system-api-dbus-runtime-launcher', $responses[0]['kind']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $responses[1]['kind']);
        self::assertSame('runtime-launcher-call-001', $responses[2]['request_id']);
        self::assertSame('system.dbus-runtime', $responses[2]['result']['method']);
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_runtime_launcher.php');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_launcher.php';
    }
}
