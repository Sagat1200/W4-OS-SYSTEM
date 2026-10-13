<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeAdapterCliTest extends TestCase
{
    public function testDescribeSpecificationReturnsAdapterContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeSpecification',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $payload['kind']);
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertSame('serve-dbus', $payload['activation']['entry_command']);
        self::assertContains('Call', $payload['required_methods']);
        self::assertContains('DescribeRuntime', $payload['optional_methods']);
    }

    public function testCallDelegatesToSystemApiContract(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'adapter-call-001',
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
        self::assertSame('local-system-api-runtime-probe', $payload['result']['resource']['provider']);
    }

    public function testServeStdioAdapterProcessesDbusRuntimeMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeSpecification',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'GetCapabilities',
                'arguments' => [
                    'request_id' => 'adapter-cap-001',
                ],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'Call',
                'arguments' => [
                    'request' => [
                        'api_version' => '1.0',
                        'request_id' => 'adapter-call-stdio-001',
                        'method' => 'profiles.get',
                        'params' => [
                            'profile_id' => 'w4-os-home',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-adapter',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(3, $responses);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $responses[0]['kind']);
        self::assertSame('adapter-cap-001', $responses[1]['request_id']);
        self::assertSame('system.capabilities', $responses[1]['result']['method']);
        self::assertSame('adapter-call-stdio-001', $responses[2]['request_id']);
        self::assertSame('profiles.get', $responses[2]['result']['method']);
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_runtime_adapter.php');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_adapter.php';
    }
}
