<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDaemonCliTest extends TestCase
{
    public function testServeStdioProcessesCapabilitiesAndCallEnvelopes(): void
    {
        $inputLines = [
            json_encode([
                'command' => 'get-capabilities',
                'request_id' => 'daemon-cap-001',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'command' => 'call',
                'request' => [
                    'api_version' => '1.0',
                    'request_id' => 'daemon-profile-001',
                    'method' => 'profiles.get',
                    'params' => [
                        'profile_id' => 'w4-os-home',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'command' => 'call',
                'request' => [
                    'api_version' => '1.0',
                    'request_id' => 'daemon-dbus-runtime-001',
                    'method' => 'system.dbus-runtime',
                    'params' => [],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runDaemon(implode(PHP_EOL, $inputLines) . PHP_EOL);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(3, $responses);

        self::assertSame('daemon-cap-001', $responses[0]['request_id']);
        self::assertNull($responses[0]['error']);
        self::assertSame('local-manager-ready', $responses[0]['result']['data']['binding']['status']);

        self::assertSame('daemon-profile-001', $responses[1]['request_id']);
        self::assertNull($responses[1]['error']);
        self::assertSame('profiles.get', $responses[1]['result']['method']);
        self::assertSame('w4-os-home', $responses[1]['result']['resource']['id']);

        self::assertSame('daemon-dbus-runtime-001', $responses[2]['request_id']);
        self::assertNull($responses[2]['error']);
        self::assertSame('system.dbus-runtime', $responses[2]['result']['method']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $responses[2]['result']['resource']['kind']);
    }

    public function testServeStdioReturnsProtocolErrorForInvalidEnvelope(): void
    {
        $result = $this->runDaemon("{bad json}\n");

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(1, $responses);
        self::assertNull($responses[0]['result']);
        self::assertSame('INVALID_ARGUMENT', $responses[0]['error']['code']);
        self::assertStringContainsString('Envelope JSON invalido', $responses[0]['error']['message']);
    }

    public function testServeStdioReturnsProtocolErrorForUnsupportedCommand(): void
    {
        $result = $this->runDaemon(json_encode([
            'command' => 'subscribe',
        ], JSON_THROW_ON_ERROR) . PHP_EOL);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(1, $responses);
        self::assertNull($responses[0]['result']);
        self::assertSame('NOT_SUPPORTED', $responses[0]['error']['code']);
        self::assertStringContainsString('Comando de daemon no soportado', $responses[0]['error']['message']);
    }

    public function testServeStdioSupportsInputAndOutputFiles(): void
    {
        $inputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-daemon-input-' . bin2hex(random_bytes(6)) . '.ndjson';
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-daemon-output-' . bin2hex(random_bytes(6)) . '.ndjson';

        try {
            $line = json_encode([
                'command' => 'call',
                'request' => [
                    'api_version' => '1.0',
                    'request_id' => 'daemon-update-001',
                    'method' => 'updates.plan',
                    'params' => [
                        'request_path' => 'examples/update/home-lab-to-1.0.1.update-request.json',
                        'operation_id' => 'daemon-update-op-001',
                        'output_path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'daemon-update-op-001.json',
                    ],
                ],
            ], JSON_THROW_ON_ERROR) . PHP_EOL;

            file_put_contents($inputPath, $line);

            $result = $this->runPhpScript([
                $this->scriptPath(),
                'serve-stdio',
                '--input-file',
                $inputPath,
                '--output',
                $outputPath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            self::assertSame('', $result['stdout']);

            $raw = file_get_contents($outputPath);
            self::assertNotFalse($raw, sprintf('No se pudo leer %s', $outputPath));

            $responses = $this->decodeNdjson($raw);
            self::assertCount(1, $responses);
            self::assertNull($responses[0]['error']);
            self::assertSame('updates.plan', $responses[0]['result']['method']);
            self::assertSame('daemon-update-op-001', $responses[0]['result']['data']['operation_id']);
        } finally {
            foreach ([$inputPath, $outputPath, sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'daemon-update-op-001.json'] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    /**
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runDaemon(string $stdin): array
    {
        return $this->runPhpScript([
            $this->scriptPath(),
            'serve-stdio',
        ], $stdin);
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_daemon.php');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_daemon.php';
    }
}
