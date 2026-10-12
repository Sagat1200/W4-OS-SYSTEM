<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class W4ctlCliTest extends TestCase
{
    public function testProfileListReturnsProfiles(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'profile:list',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('profile:list', $payload['command']);
        self::assertSame(3, $payload['count']);
        self::assertSame('w4-os-business', $payload['profiles'][0]['id']);
        self::assertSame('w4-os-home', $payload['profiles'][1]['id']);
        self::assertSame('w4-os-server', $payload['profiles'][2]['id']);
    }

    public function testProfileShowResolvesSelectedProfile(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'profile:show',
            '--profile',
            'w4-os-home',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('profile:show', $payload['command']);
        self::assertSame('w4-os-home', $payload['profile']['id']);
        self::assertSame('w4-os-home.profile.json', $payload['profile']['source_manifest']);
        self::assertContains('gdm3', $payload['resolved_profile']['required_packages']);
        self::assertContains('w4-home-meta', $payload['resolved_profile']['required_meta_packages']);
    }

    public function testBuildValidateReturnsCanonicalPayload(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'build:validate',
            '--profile',
            'w4-os-business',
            '--channel',
            'stable',
            '--format',
            'iso',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('build:validate', $payload['command']);
        self::assertSame('w4-os-business', $payload['profile_id']);
        self::assertSame('stable', $payload['release_channel']);
        self::assertSame('iso', $payload['image_format']);
        self::assertSame('w4-linux-base.manifest.json', $payload['source_manifests']['base']);
        self::assertSame('w4-os-business.profile.json', $payload['source_manifests']['profile']);
        self::assertContains('inventory-remote-ready', $payload['features']);
    }

    public function testBuildValidateRejectsMissingProfile(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'build:validate',
        ]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('Debe indicar un PROFILE_ID usando --profile', $result['stderr']);
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__));
        self::assertIsResource($process, 'No se pudo ejecutar w4ctl');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'w4ctl.php';
    }
}
