<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiOperationsAuthScopeWorkerCliTest extends TestCase
{
    public function testWorkerProducesValidatedReport(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-ops-auth-scope-' . bin2hex(random_bytes(6));
        $evidenceDir = $base . DIRECTORY_SEPARATOR . 'evidence';

        mkdir($evidenceDir, 0777, true);

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--evidence-dir',
                $evidenceDir,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertTrue($payload['validated']);
            self::assertTrue($payload['checks']['capabilities_permission']);
            self::assertTrue($payload['checks']['other_get_not_found']);
            self::assertTrue($payload['checks']['other_cancel_not_found']);
            self::assertFileExists($evidenceDir . DIRECTORY_SEPARATOR . 'report.json');
        } finally {
            $this->rmTree($base);
        }
    }

    private function runPhpScript(array $command, array $environment = []): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $processEnvironment = array_merge($_ENV, $environment);
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__), $processEnvironment);
        self::assertIsResource($process);

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

    private function decodeJson(string $json): array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_system_api_operations_auth_scope_worker.php';
    }

    private function rmTree(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }
            unlink($item->getPathname());
        }

        rmdir($path);
    }
}

