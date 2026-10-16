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

    public function testProbeCanReportFailedNativeDbusExtensionBuildEvidence(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_STATE' => 'build-failed',
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_REASON' => 'zend_object_store_get_object',
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_LOG_PATH' => '/tmp/pecl-dbus.log',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertFalse($payload['ready']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
        self::assertTrue($payload['native_dbus_extension_installation']['pecl_available']);
        self::assertSame('make', $payload['native_dbus_extension_installation']['build_phase']);
        self::assertSame('native-dbus-extension-build-failed', $payload['native_dbus_extension_installation']['blocking_reason']);
        self::assertSame('inspect-native-dbus-build-make', $payload['native_dbus_extension_installation']['next_action']);
        self::assertSame('zend_object_store_get_object', $payload['native_dbus_extension_installation']['reason']);
        self::assertSame('/tmp/pecl-dbus.log', $payload['native_dbus_extension_installation']['log_path']);
    }

    public function testProbeCanInferFailedNativeDbusExtensionBuildFromEvidenceFile(): void
    {
        $evidencePath = tempnam(sys_get_temp_dir(), 'w4-dbus-build-evidence-');
        self::assertNotFalse($evidencePath);

        try {
            file_put_contents($evidencePath, <<<TEXT
Se intento pecl install channel://pecl.php.net/dbus-0.1.1
La compilacion sigue fallando en make.
referencias a zend_object_store_get_object(...)
make: *** [Makefile:192: dbus.lo] Error 1
TEXT);

            $result = $this->runPhpScript([
                $this->scriptPath(),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '0',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH' => $evidencePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
            self::assertTrue($payload['native_dbus_extension_installation']['pecl_available']);
            self::assertSame('make', $payload['native_dbus_extension_installation']['build_phase']);
            self::assertSame('native-dbus-extension-build-failed', $payload['native_dbus_extension_installation']['blocking_reason']);
            self::assertSame('inspect-native-dbus-build-make', $payload['native_dbus_extension_installation']['next_action']);
            self::assertSame('zend_object_store_get_object', $payload['native_dbus_extension_installation']['reason']);
            self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $payload['native_dbus_extension_installation']['failure_signature']);
            self::assertSame($evidencePath, $payload['native_dbus_extension_installation']['log_path']);
        } finally {
            @unlink($evidencePath);
        }
    }

    public function testProbePrefersStructuredNativeDbusBuildEvidenceWhenAvailable(): void
    {
        $evidencePath = tempnam(sys_get_temp_dir(), 'w4-dbus-build-evidence-json-');
        self::assertNotFalse($evidencePath);

        try {
            file_put_contents($evidencePath, json_encode([
                'api_version' => '1.0',
                'kind' => 'system-api-native-dbus-extension-build-evidence',
                'state' => 'build-failed',
                'reason' => 'zend_object_store_get_object',
                'log_path' => '/tmp/native-dbus-build.log',
            ], JSON_THROW_ON_ERROR));

            $result = $this->runPhpScript([
                $this->scriptPath(),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_JSON_PATH' => $evidencePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
            self::assertTrue($payload['native_dbus_extension_installation']['pecl_available']);
            self::assertSame('make', $payload['native_dbus_extension_installation']['build_phase']);
            self::assertSame('zend_object_store_get_object', $payload['native_dbus_extension_installation']['reason']);
            self::assertSame('/tmp/native-dbus-build.log', $payload['native_dbus_extension_installation']['log_path']);
        } finally {
            @unlink($evidencePath);
        }
    }

    public function testProbeCanInferPeclDownloadFailurePhaseFromEvidenceFile(): void
    {
        $evidencePath = tempnam(sys_get_temp_dir(), 'w4-dbus-build-evidence-download-');
        self::assertNotFalse($evidencePath);

        try {
            file_put_contents($evidencePath, <<<TEXT
Failed to download pecl/dbus within preferred state "stable", latest release is beta
pecl install dbus
TEXT);

            $result = $this->runPhpScript([
                $this->scriptPath(),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '0',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH' => $evidencePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
            self::assertSame('pecl-download', $payload['native_dbus_extension_installation']['build_phase']);
            self::assertSame('pecl-release-is-beta', $payload['native_dbus_extension_installation']['reason']);
            self::assertSame('inspect-native-dbus-pecl-download', $payload['native_dbus_extension_installation']['next_action']);
            self::assertTrue($payload['native_dbus_extension_installation']['pecl_available']);
        } finally {
            @unlink($evidencePath);
        }
    }

    public function testProbeCanInferConfigureFailureSignatureFromEvidenceFile(): void
    {
        $evidencePath = tempnam(sys_get_temp_dir(), 'w4-dbus-build-evidence-configure-');
        self::assertNotFalse($evidencePath);

        try {
            file_put_contents($evidencePath, <<<TEXT
checking for xml2-config path... not found
configure: error: Package requirements (libxml-2.0) were not met
TEXT);

            $result = $this->runPhpScript([
                $this->scriptPath(),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH' => $evidencePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
            self::assertSame('configure', $payload['native_dbus_extension_installation']['build_phase']);
            self::assertSame('inspect-native-dbus-build-configure', $payload['native_dbus_extension_installation']['next_action']);
            self::assertSame('libxml-2.0', $payload['native_dbus_extension_installation']['reason']);
            self::assertSame('configure: error: Package requirements (libxml-2.0) were not met', $payload['native_dbus_extension_installation']['failure_signature']);
        } finally {
            @unlink($evidencePath);
        }
    }

    public function testProbeCanInferPhpizeFailureFromEvidenceFile(): void
    {
        $evidencePath = tempnam(sys_get_temp_dir(), 'w4-dbus-build-evidence-phpize-');
        self::assertNotFalse($evidencePath);

        try {
            file_put_contents($evidencePath, <<<TEXT
Cannot find autoconf. Please check your autoconf installation and the \$PHP_AUTOCONF environment variable.
phpize failed
TEXT);

            $result = $this->runPhpScript([
                $this->scriptPath(),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH' => $evidencePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('build-failed', $payload['native_dbus_extension_installation']['state']);
            self::assertSame('phpize', $payload['native_dbus_extension_installation']['build_phase']);
            self::assertSame('inspect-native-dbus-build-phpize', $payload['native_dbus_extension_installation']['next_action']);
            self::assertSame('autoconf-missing', $payload['native_dbus_extension_installation']['reason']);
            self::assertSame(
                'Cannot find autoconf. Please check your autoconf installation and the $PHP_AUTOCONF environment variable.',
                $payload['native_dbus_extension_installation']['failure_signature']
            );
        } finally {
            @unlink($evidencePath);
        }
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
