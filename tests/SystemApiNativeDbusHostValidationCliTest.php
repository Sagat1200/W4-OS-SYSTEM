<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiNativeDbusHostValidationCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;
    /** @var list<string> */
    private array $repoCleanupDirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-host-validation-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        foreach ($this->repoCleanupDirs as $path) {
            $this->removeDirectory($path);
        }

        parent::tearDown();
    }

    public function testValidationReportsBlockedHostWhenNativeRuntimeCapabilitiesAreMissing(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'blocked-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-native-runtime-validation-report', $payload['kind']);
        self::assertSame('blocked', $payload['status']);
        self::assertFalse($payload['ownership_validated']);
        self::assertFalse($payload['coupling_ready']);
        self::assertSame('install-native-dbus-extension', $payload['next_action']);
        self::assertContains('install-native-dbus-extension', $payload['recommended_next_actions']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
        self::assertFileExists($payload['preparation_shell_script_path']);
        self::assertFileExists($payload['preparation_checklist_path']);
        self::assertFileExists($payload['handoff_path']);
        self::assertFileExists($payload['step_files']['diagnosis']);
        self::assertFileExists($payload['step_files']['service_definition']);
        self::assertFileExists($payload['step_files']['startup']);
        self::assertFileExists($payload['step_files']['coupling']);
        self::assertFileExists($payload['step_files']['ownership']);
        self::assertIsArray($payload['preparation_plan']);
        self::assertContains(
            'install-native-dbus-extension',
            array_map(
                static fn (array $step): string => (string) ($step['action'] ?? ''),
                $payload['preparation_plan']
            )
        );

        $savedReport = $this->decodeJsonFile($payload['report_path']);
        self::assertSame('blocked', $savedReport['status']);
        self::assertSame('native-runtime-service-blocked', $savedReport['steps']['startup']['status']);
        self::assertSame('missing-runtime-capabilities', $savedReport['steps']['diagnosis']['blocking_reason'] ?? 'missing-runtime-capabilities');
        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('## Preparation Plan', $savedSummary);
        self::assertStringContainsString('install-native-dbus-extension', $savedSummary);
        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('install-native-dbus-extension', $preparationChecklist);
        $preparationShellScript = file_get_contents($payload['preparation_shell_script_path']);
        self::assertNotFalse($preparationShellScript);
        self::assertStringContainsString('echo', $preparationShellScript);
        $handoff = file_get_contents($payload['handoff_path']);
        self::assertNotFalse($handoff);
        self::assertStringContainsString('## Re-run Commands', $handoff);
    }

    public function testValidationReportsLinuxPhpCompatibilityBlockBeforeNativeCapabilities(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-php-compatibility-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '7.4.3',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('linux-php-runtime-too-old', $payload['blocking_reason']);
        self::assertSame('upgrade-runtime-php', $payload['next_action']);
        self::assertContains('upgrade-runtime-php', $payload['recommended_next_actions']);
        self::assertContains('migrate-to-modern-linux-host', $payload['recommended_next_actions']);
        self::assertFalse($payload['host_compatibility']['compatible']);
        self::assertSame('Linux', $payload['host_compatibility']['os_family']);
        self::assertSame('7.4.3', $payload['host_compatibility']['php_version']);
        self::assertSame('^8.4', $payload['host_compatibility']['required_php_constraint']);
        self::assertSame('8.4.0', $payload['host_compatibility']['required_php_version']);

        $preparationActions = array_map(
            static fn (array $step): string => (string) ($step['action'] ?? ''),
            $payload['preparation_plan']
        );
        self::assertSame(
            ['upgrade-runtime-php', 'rerun-native-runtime-validation'],
            $preparationActions
        );

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('## Host Compatibility', $savedSummary);
        self::assertStringContainsString('- Host compatibility: `blocked`', $savedSummary);
        self::assertStringContainsString('- Detected PHP version: `7.4.3`', $savedSummary);
        self::assertStringContainsString('- Required PHP constraint: `^8.4`', $savedSummary);
        self::assertStringContainsString('upgrade-runtime-php', $savedSummary);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('php8.4-cli', $preparationChecklist);
        self::assertStringContainsString('cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux', $preparationChecklist);
        self::assertStringNotContainsString('install-native-dbus-extension', $preparationChecklist);
    }

    public function testValidationAddsInspectionStepWhenNativeDbusApiIsIncomplete(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-api-incomplete-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_NATIVE_DBUS_EXTENSION_LOADED' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '0',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('dbus-extension-api-incomplete', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['next_action']);
        self::assertContains('inspect-native-dbus-extension-api', $payload['recommended_next_actions']);

        $preparationActions = array_map(
            static fn (array $step): string => (string) ($step['action'] ?? ''),
            $payload['preparation_plan']
        );
        self::assertContains('inspect-native-dbus-extension-api', $preparationActions);
        self::assertContains('retry-native-bus-ownership', $preparationActions);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-extension-api', $preparationChecklist);
        self::assertStringContainsString('php --ri dbus', $preparationChecklist);
        self::assertStringContainsString('registerObject', $preparationChecklist);
        self::assertStringContainsString('waitLoop', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('inspect-native-dbus-extension-api', $savedSummary);
    }

    public function testValidationReportsExtensionBuildFailureAsExplicitHostBlock(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-build-failed-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_STATE' => 'build-failed',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_REASON' => 'zend_object_store_get_object',
                'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_LOG_PATH' => '/tmp/pecl-dbus.log',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('native-dbus-extension-build-failed', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-build-make', $payload['next_action']);
        self::assertContains('inspect-native-dbus-build-make', $payload['recommended_next_actions']);
        self::assertContains('migrate-to-modern-linux-host', $payload['recommended_next_actions']);
        self::assertTrue($payload['host_compatibility']['compatible']);
        self::assertTrue($payload['native_dbus_extension_installability']['blocked']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('make', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('/tmp/pecl-dbus.log', $payload['native_dbus_extension_installability']['log_path']);

        $preparationActions = array_map(
            static fn (array $step): string => (string) ($step['action'] ?? ''),
            $payload['preparation_plan']
        );
        self::assertSame(
            ['inspect-native-dbus-build-make', 'rerun-native-runtime-validation'],
            $preparationActions
        );

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-build-make', $preparationChecklist);
        self::assertStringContainsString('pecl remote-info channel://pecl.php.net/dbus-0.1.1', $preparationChecklist);
        self::assertStringContainsString('/tmp/pecl-dbus.log', $preparationChecklist);
        self::assertStringContainsString('cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux', $preparationChecklist);
        self::assertStringNotContainsString('install-native-dbus-extension', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('## Native D-Bus Extension Installation', $savedSummary);
        self::assertStringContainsString('- Installation state: `build-failed`', $savedSummary);
        self::assertStringContainsString('- Installation reason: `zend_object_store_get_object`', $savedSummary);
    }

    public function testValidationCanInferExtensionBuildFailureFromPersistedLinuxEvidence(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-build-failed-from-evidence-host';
        self::assertTrue(mkdir($evidenceDir, 0777, true) || is_dir($evidenceDir));
        $evidencePath = $evidenceDir . DIRECTORY_SEPARATOR . 'wsl-ubuntu-operational-attempt.md';
        file_put_contents($evidencePath, <<<TEXT
# Contraste real WSL Ubuntu 20.04

Se intento pecl install channel://pecl.php.net/dbus-0.1.1
La compilacion sigue fallando en make.
referencias a zend_object_store_get_object(...)
make: *** [Makefile:192: dbus.lo] Error 1
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('native-dbus-extension-build-failed', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-build-make', $payload['next_action']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('make', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('zend_object_store_get_object', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $payload['native_dbus_extension_installability']['failure_signature']);
        self::assertSame($evidencePath, $payload['native_dbus_extension_installability']['log_path']);
        self::assertFileExists($payload['native_dbus_extension_build_evidence_path']);

        $structuredEvidence = $this->decodeJsonFile($payload['native_dbus_extension_build_evidence_path']);
        self::assertSame('system-api-native-dbus-extension-build-evidence', $structuredEvidence['kind']);
        self::assertSame('build-failed', $structuredEvidence['state']);
        self::assertSame('make', $structuredEvidence['build_phase']);
        self::assertSame('zend_object_store_get_object', $structuredEvidence['reason']);
        self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $structuredEvidence['failure_signature']);
        self::assertSame($evidencePath, $structuredEvidence['source_path']);
        self::assertSame($evidencePath, $structuredEvidence['log_path']);

        $preparationActions = array_map(
            static fn (array $step): string => (string) ($step['action'] ?? ''),
            $payload['preparation_plan']
        );
        self::assertSame(
            ['inspect-native-dbus-build-make', 'rerun-native-runtime-validation'],
            $preparationActions
        );
    }

    public function testValidationCanMaterializeStructuredBuildEvidenceFromExplicitRunnerArguments(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-build-failed-from-explicit-runner-args';
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-build.log';
        file_put_contents($buildLogPath, "make: *** [Makefile:192: dbus.lo] Error 1\n");

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-log-path',
            $buildLogPath,
            '--native-dbus-build-state',
            'build-failed',
            '--native-dbus-build-reason',
            'zend_object_store_get_object',
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('native-dbus-extension-build-failed', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-build-make', $payload['next_action']);
        self::assertFileExists($payload['native_dbus_extension_build_evidence_path']);

        $structuredEvidence = $this->decodeJsonFile($payload['native_dbus_extension_build_evidence_path']);
        self::assertSame('system-api-native-dbus-extension-build-evidence', $structuredEvidence['kind']);
        self::assertSame('build-failed', $structuredEvidence['state']);
        self::assertSame('make', $structuredEvidence['build_phase']);
        self::assertSame('zend_object_store_get_object', $structuredEvidence['reason']);
        self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $structuredEvidence['failure_signature']);
        self::assertSame($buildLogPath, $structuredEvidence['source_path']);
        self::assertSame($buildLogPath, $structuredEvidence['log_path']);
        self::assertSame(['zend_object_store_get_object', 'make'], $structuredEvidence['markers']);
    }

    public function testValidationCanConsumeStructuredBuildEvidenceJsonFromRunnerArgument(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-build-failed-from-structured-json-arg';
        $structuredEvidencePath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json';
        file_put_contents($structuredEvidencePath, json_encode([
            'api_version' => '1.0',
            'kind' => 'system-api-native-dbus-extension-build-evidence',
            'state' => 'build-failed',
            'build_phase' => 'pecl-download',
            'reason' => 'pecl-network-resolution-failed',
            'failure_signature' => 'Channel "pecl.php.net" is not responding over http://, failed with message: php_network_getaddresses: getaddrinfo for pecl.php.net failed: Temporary failure in name resolution',
            'log_path' => '/tmp/native-dbus-pecl-network.log',
            'markers' => ['pecl-network-resolution-failed', 'pecl-download'],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-evidence-json-path',
            $structuredEvidencePath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('native-dbus-extension-build-failed', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-pecl-network', $payload['next_action']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('pecl-download', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('pecl-network-resolution-failed', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'Channel "pecl.php.net" is not responding over http://, failed with message: php_network_getaddresses: getaddrinfo for pecl.php.net failed: Temporary failure in name resolution',
            $payload['native_dbus_extension_installability']['failure_signature']
        );
        self::assertSame('/tmp/native-dbus-pecl-network.log', $payload['native_dbus_extension_installability']['log_path']);
        self::assertSame('inspect-native-dbus-pecl-network', $payload['preparation_plan'][0]['action']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-pecl-network', $preparationChecklist);
        self::assertStringContainsString('getent hosts pecl.php.net || nslookup pecl.php.net || true', $preparationChecklist);
        self::assertStringContainsString('pecl channel-discover pecl.php.net || true', $preparationChecklist);
    }

    public function testValidationPropagatesReusableStructuredBuildEvidencePathIntoRerunCommands(): void
    {
        $evidenceDir = $this->repoRoot
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'system-api'
            . DIRECTORY_SEPARATOR . 'validation'
            . DIRECTORY_SEPARATOR . 'native-dbus-relative-json-' . bin2hex(random_bytes(4));
        $this->repoCleanupDirs[] = $evidenceDir;
        self::assertTrue(mkdir($evidenceDir, 0777, true) || is_dir($evidenceDir));

        $structuredEvidencePath = $evidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json';
        file_put_contents($structuredEvidencePath, json_encode([
            'api_version' => '1.0',
            'kind' => 'system-api-native-dbus-extension-build-evidence',
            'state' => 'build-failed',
            'build_phase' => 'pecl-download',
            'reason' => 'pecl-network-resolution-failed',
            'failure_signature' => 'Channel "pecl.php.net" is not responding over http://, failed with message: php_network_getaddresses: getaddrinfo for pecl.php.net failed: Temporary failure in name resolution',
            'log_path' => '/tmp/native-dbus-pecl-network.log',
            'markers' => ['pecl-network-resolution-failed', 'pecl-download'],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-evidence-json-path',
            $structuredEvidencePath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        $expectedRelativePath = str_replace('\\', '/', substr($structuredEvidencePath, strlen($this->repoRoot) + 1));

        self::assertStringContainsString(
            '--native-dbus-build-evidence-json-path ' . $expectedRelativePath,
            $payload['preparation_plan'][1]['commands'][0]
        );

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString(
            '--native-dbus-build-evidence-json-path ' . $expectedRelativePath,
            $preparationChecklist
        );

        $handoff = file_get_contents($payload['handoff_path']);
        self::assertNotFalse($handoff);
        self::assertStringContainsString(
            '--native-dbus-build-evidence-json-path ' . $expectedRelativePath,
            $handoff
        );
    }

    public function testValidationTranslatesPeclDownloadFailureIntoSpecificPreparationStep(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-download-failed-host';
        self::assertTrue(mkdir($evidenceDir, 0777, true) || is_dir($evidenceDir));
        $evidencePath = $evidenceDir . DIRECTORY_SEPARATOR . 'wsl-ubuntu-operational-attempt.md';
        file_put_contents($evidencePath, <<<TEXT
Failed to download pecl/dbus within preferred state "stable", latest release is beta
pecl install dbus
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '0',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('pecl-download', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('pecl-release-is-beta', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'Failed to download pecl/dbus within preferred state "stable", latest release is beta',
            $payload['native_dbus_extension_installability']['failure_signature']
        );
        self::assertSame('inspect-native-dbus-pecl-release', $payload['preparation_plan'][0]['action']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-pecl-release', $preparationChecklist);
        self::assertStringContainsString('pecl channel-update pecl.php.net || true', $preparationChecklist);
        self::assertStringContainsString('pecl remote-info pecl/dbus || true', $preparationChecklist);
        self::assertStringContainsString('pecl remote-info channel://pecl.php.net/dbus-0.1.1', $preparationChecklist);
        self::assertStringContainsString('pecl install channel://pecl.php.net/dbus-0.1.1 || true', $preparationChecklist);
        self::assertStringContainsString('Firma detectada del fallo: Failed to download pecl/dbus within preferred state', $preparationChecklist);
        self::assertStringContainsString('latest release is beta', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Installation build phase: `pecl-download`', $savedSummary);
        self::assertStringContainsString('- Installation failure signature: `Failed to download pecl/dbus within preferred state "stable", latest release is beta`', $savedSummary);
    }

    public function testValidationSeparatesPeclPackageResolutionFailureFromBetaReleaseCase(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-pecl-channel-failed-host';
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-pecl-channel.log';
        file_put_contents($buildLogPath, <<<TEXT
Failed to download pecl/dbus within preferred state "stable", no releases available for package "pecl.php.net/dbus"
pecl install dbus
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-log-path',
            $buildLogPath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('pecl-download', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('pecl-package-resolution-failed', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'Failed to download pecl/dbus within preferred state "stable", no releases available for package "pecl.php.net/dbus"',
            $payload['native_dbus_extension_installability']['failure_signature']
        );
        self::assertSame('inspect-native-dbus-pecl-package', $payload['preparation_plan'][0]['action']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-pecl-package', $preparationChecklist);
        self::assertStringContainsString('pecl search dbus || true', $preparationChecklist);
        self::assertStringContainsString('pecl remote-info pecl/dbus || true', $preparationChecklist);
        self::assertStringContainsString('pecl list-all | grep -i dbus || true', $preparationChecklist);
        self::assertStringContainsString('pecl remote-info pecl/dbus || true', $preparationChecklist);
        self::assertStringNotContainsString('pecl install channel://pecl.php.net/dbus-0.1.1 || true', $preparationChecklist);
        self::assertStringNotContainsString('pecl channel-discover pecl.php.net || true', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Installation build phase: `pecl-download`', $savedSummary);
        self::assertStringContainsString('- Installation next action: `inspect-native-dbus-pecl-package`', $savedSummary);
    }

    public function testValidationSeparatesPeclNetworkFailureFromPackageResolutionCase(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-pecl-network-failed-host';
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-pecl-network.log';
        file_put_contents($buildLogPath, <<<TEXT
Channel "pecl.php.net" is not responding over http://, failed with message: php_network_getaddresses: getaddrinfo for pecl.php.net failed: Temporary failure in name resolution
Trying channel "pecl.php.net" over https:// instead
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-log-path',
            $buildLogPath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('pecl-download', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('pecl-network-resolution-failed', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'Channel "pecl.php.net" is not responding over http://, failed with message: php_network_getaddresses: getaddrinfo for pecl.php.net failed: Temporary failure in name resolution',
            $payload['native_dbus_extension_installability']['failure_signature']
        );
        self::assertSame('inspect-native-dbus-pecl-network', $payload['preparation_plan'][0]['action']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-pecl-network', $preparationChecklist);
        self::assertStringContainsString('getent hosts pecl.php.net || nslookup pecl.php.net || true', $preparationChecklist);
        self::assertStringContainsString('ping -c 1 pecl.php.net || true', $preparationChecklist);
        self::assertStringContainsString('pecl list-channels || true', $preparationChecklist);
        self::assertStringContainsString('pecl channel-discover pecl.php.net || true', $preparationChecklist);
        self::assertStringNotContainsString('pecl install channel://pecl.php.net/dbus-0.1.1 || true', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Installation build phase: `pecl-download`', $savedSummary);
        self::assertStringContainsString('- Installation next action: `inspect-native-dbus-pecl-network`', $savedSummary);
    }

    public function testValidationCanInferConfigureFailureFromRawBuildLogPathWithoutExplicitState(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-configure-failed-host';
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-configure.log';
        file_put_contents($buildLogPath, <<<TEXT
checking for xml2-config path... not found
configure: error: Package requirements (libxml-2.0) were not met
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-log-path',
            $buildLogPath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('configure', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('inspect-native-dbus-build-configure', $payload['next_action']);
        self::assertSame('libxml-2.0', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'configure: error: Package requirements (libxml-2.0) were not met',
            $payload['native_dbus_extension_installability']['failure_signature']
        );

        $structuredEvidence = $this->decodeJsonFile($payload['native_dbus_extension_build_evidence_path']);
        self::assertSame('configure', $structuredEvidence['build_phase']);
        self::assertSame('configure: error: Package requirements (libxml-2.0) were not met', $structuredEvidence['failure_signature']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-build-configure', $preparationChecklist);
        self::assertStringContainsString('pkg-config --modversion libxml-2.0 || true', $preparationChecklist);
        self::assertStringContainsString('Firma detectada del fallo: configure: error: Package requirements (libxml-2.0) were not met', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Installation build phase: `configure`', $savedSummary);
        self::assertStringContainsString('- Installation failure signature: `configure: error: Package requirements (libxml-2.0) were not met`', $savedSummary);
    }

    public function testValidationCanInferPhpizeFailureFromRawBuildLogPathWithoutExplicitState(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-native-dbus-phpize-failed-host';
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-phpize.log';
        file_put_contents($buildLogPath, <<<TEXT
Cannot find autoconf. Please check your autoconf installation and the \$PHP_AUTOCONF environment variable.
phpize failed
TEXT);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
            '--native-dbus-build-log-path',
            $buildLogPath,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
                'W4_SYSTEM_API_PROBE_HAS_PECL' => '1',
            ]
        ));

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('phpize', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('inspect-native-dbus-build-phpize', $payload['next_action']);
        self::assertSame('autoconf-missing', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame(
            'Cannot find autoconf. Please check your autoconf installation and the $PHP_AUTOCONF environment variable.',
            $payload['native_dbus_extension_installability']['failure_signature']
        );

        $structuredEvidence = $this->decodeJsonFile($payload['native_dbus_extension_build_evidence_path']);
        self::assertSame('phpize', $structuredEvidence['build_phase']);
        self::assertSame(
            'Cannot find autoconf. Please check your autoconf installation and the $PHP_AUTOCONF environment variable.',
            $structuredEvidence['failure_signature']
        );
        self::assertSame(['autoconf-missing', 'phpize'], $structuredEvidence['markers']);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('inspect-native-dbus-build-phpize', $preparationChecklist);
        self::assertStringContainsString('autoconf --version || true', $preparationChecklist);
        self::assertStringContainsString('autoheader --version || true', $preparationChecklist);
        self::assertStringContainsString('aclocal --version || true', $preparationChecklist);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Installation build phase: `phpize`', $savedSummary);
        self::assertStringContainsString(
            '- Installation failure signature: `Cannot find autoconf. Please check your autoconf installation and the $PHP_AUTOCONF environment variable.`',
            $savedSummary
        );
    }

    public function testValidationCanPrepareOwnedRuntimeAgainstFakeNativeDbusService(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'fake-owned-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => $this->repoRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ownership-validated', $payload['status']);
        self::assertTrue($payload['ownership_validated']);
        self::assertTrue($payload['coupling_ready']);
        self::assertTrue($payload['startup_acknowledged']);
        self::assertSame('runtime-service-active', $payload['next_action']);
        self::assertSame('native-runtime-bus-owned', $payload['steps']['ownership']['status']);
        self::assertSame('bus-name-owned', $payload['steps']['ownership']['ownership_state']);
        self::assertSame('native-runtime-service-observed-active', $payload['steps']['observation_after_ownership']['status']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
        self::assertFileExists($payload['preparation_shell_script_path']);
        self::assertFileExists($payload['preparation_checklist_path']);
        self::assertFileExists($payload['handoff_path']);
        self::assertDirectoryExists($payload['artifacts_dir']);
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready');
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json');
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid');
        self::assertContains(
            'capture-release-evidence',
            array_map(
                static fn (array $step): string => (string) ($step['action'] ?? ''),
                $payload['preparation_plan']
            )
        );

        $savedReport = $this->decodeJsonFile($payload['report_path']);
        self::assertSame('ownership-validated', $savedReport['status']);
        self::assertSame('W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver', $savedReport['steps']['ownership']['ownership_driver']['class_name']);
        $preparationShellScript = file_get_contents($payload['preparation_shell_script_path']);
        self::assertNotFalse($preparationShellScript);
        self::assertStringContainsString('php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux', $preparationShellScript);
    }

    public function testValidationCanRenderMarkdownSummaryDirectlyFromRunner(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'markdown-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--format',
            'markdown',
            '--attempt-ownership',
            'false',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('# Native D-Bus Runtime Validation', $result['stdout']);
        self::assertStringContainsString('## Preparation Plan', $result['stdout']);
        self::assertStringContainsString('install-native-dbus-extension', $result['stdout']);
        self::assertStringContainsString('- Handoff path: `', $result['stdout']);
        self::assertSame(1, substr_count($result['stdout'], '- Report path: `'));
    }

    public function testValidationCanPrepareLinuxArtifactsFromNonLinuxHost(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-target-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Windows',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('Linux', $payload['preparation_target_os_family']);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Preparation target OS family: `Linux`', $savedSummary);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('sudo apt-get install -y php-pear php-dev libdbus-1-dev pkg-config', $preparationChecklist);
        self::assertStringContainsString('php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux', $preparationChecklist);

        $preparationShellScript = file_get_contents($payload['preparation_shell_script_path']);
        self::assertNotFalse($preparationShellScript);
        self::assertStringContainsString('sudo apt-get install -y php-pear php-dev libdbus-1-dev pkg-config', $preparationShellScript);
        self::assertStringNotContainsString('echo "Resolver manualmente', $preparationShellScript);

        $handoff = file_get_contents($payload['handoff_path']);
        self::assertNotFalse($handoff);
        self::assertStringContainsString('busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager', $handoff);
        self::assertStringContainsString('cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux', $handoff);
    }

    public function testValidationCanOverrideTargetRepoRootForLinuxHandoff(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'linux-target-custom-root-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--target-os-family',
            'Linux',
            '--target-repo-root',
            '/srv/w4/os-system',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Windows',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('/srv/w4/os-system', $payload['preparation_target_repo_root']);

        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('- Preparation target repo root: `/srv/w4/os-system`', $savedSummary);

        $preparationChecklist = file_get_contents($payload['preparation_checklist_path']);
        self::assertNotFalse($preparationChecklist);
        self::assertStringContainsString('cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux', $preparationChecklist);

        $handoff = file_get_contents($payload['handoff_path']);
        self::assertNotFalse($handoff);
        self::assertStringContainsString('- Target repo root: `/srv/w4/os-system`', $handoff);
        self::assertStringContainsString('cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux', $handoff);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_real_host_validation.php';
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, array $environment = []): array
    {
        array_unshift($command, PHP_BINARY);
        $processEnvironment = [];
        foreach (array_merge($_ENV, $_SERVER, $environment) as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $processEnvironment[$key] = (string) $value;
            }
        }

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'rb'],
                1 => ['pipe', 'wb'],
                2 => ['pipe', 'wb'],
            ],
            $pipes,
            $this->repoRoot,
            $processEnvironment
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readyRuntimeEnvironment(): array
    {
        return [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
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
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
