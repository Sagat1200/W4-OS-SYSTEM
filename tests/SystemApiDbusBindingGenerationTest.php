<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusBindingGenerationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-system-api-dbus-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateSystemApiDbusBindingPublishesManifestAndArtifacts(): void
    {
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'org.w4.System1';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--output',
            $outputDir,
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('system-api-dbus-binding', $payload['kind']);
        self::assertSame('pending-runtime-adapter', $payload['binding_status']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'system-api-dbus-binding-manifest.json');
        self::assertSame(1, $manifest['binding_schema_version']);
        self::assertSame('system-api-dbus-binding', $manifest['kind']);
        self::assertSame('1.0', $manifest['api_version']);
        self::assertSame('org.w4.System1', $manifest['bus_name']);
        self::assertSame('/org/w4/System1', $manifest['object_path']);
        self::assertSame('org.w4.System1.Manager', $manifest['interface']);
        self::assertSame('pending-runtime-adapter', $manifest['binding_status']);
        self::assertFalse($manifest['enabled_by_default']);
        self::assertSame('stdio-ndjson-launcher', $manifest['transport']['current_local_runtime']);
        self::assertSame('dbus-system-bus', $manifest['transport']['future_public_binding']);
        self::assertSame('files/usr/share/w4/system-api/org.w4.System1.runtime-adapter.json', $manifest['activation']['adapter_specification']);
        self::assertSame('files/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json', $manifest['activation']['bootstrap_specification']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-runtime-launcher', $manifest['activation']['runtime_entrypoint']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php', $manifest['runtime_launcher']['entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-runtime-launcher', $manifest['runtime_launcher']['wrapper']);
        self::assertSame('launcher-ready', $manifest['runtime_launcher']['runtime_status']);
        self::assertSame('stdio-ndjson-launcher', $manifest['runtime_launcher']['transport']);
        self::assertSame('child-process-stdio-relay', $manifest['runtime_launcher']['handoff_mode']);
        self::assertContains('DescribeLauncher', $manifest['runtime_launcher']['commands']);
        self::assertContains('DescribeLaunchPlan', $manifest['runtime_launcher']['commands']);
        self::assertContains('DescribeActivationBootstrap', $manifest['runtime_launcher']['commands']);
        self::assertContains('DescribeActivationPreparation', $manifest['runtime_launcher']['commands']);
        self::assertContains('AttemptActivation', $manifest['runtime_launcher']['commands']);
        self::assertContains('ExecuteActivation', $manifest['runtime_launcher']['commands']);
        self::assertContains('serve-stdio-launcher', $manifest['runtime_launcher']['commands']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php', $manifest['runtime_host']['entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-runtime-host', $manifest['runtime_host']['wrapper']);
        self::assertContains('DescribeHost', $manifest['runtime_host']['commands']);
        self::assertContains('serve-stdio-host', $manifest['runtime_host']['commands']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php', $manifest['runtime_adapter']['entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-runtime-adapter', $manifest['runtime_adapter']['wrapper']);
        self::assertContains('DescribeSpecification', $manifest['runtime_adapter']['commands']);
        self::assertContains('DescribeActivationPreparation', $manifest['runtime_adapter']['commands']);
        self::assertContains('AttemptActivation', $manifest['runtime_adapter']['commands']);
        self::assertContains('ExecuteActivation', $manifest['runtime_adapter']['commands']);
        self::assertContains('ObserveNativeRuntimeServiceState', $manifest['runtime_adapter']['commands']);
        self::assertContains('MaterializeNativeBusOwnership', $manifest['runtime_adapter']['commands']);
        self::assertContains('serve-stdio-adapter', $manifest['runtime_adapter']['commands']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php', $manifest['runtime_bridge']['entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-bridge', $manifest['runtime_bridge']['wrapper']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php', $manifest['runtime_bridge']['readiness_entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-bridge-readiness', $manifest['runtime_bridge']['readiness_wrapper']);
        self::assertSame('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php', $manifest['runtime_bridge']['diagnostics_entrypoint']);
        self::assertSame('files/usr/local/bin/w4-system-api-dbus-runtime-diagnose', $manifest['runtime_bridge']['diagnostics_wrapper']);
        self::assertSame('pending-runtime-adapter', $manifest['runtime_bridge']['runtime_status']);
        self::assertContains('Call', $manifest['runtime_bridge']['commands']);
        self::assertContains('GetCapabilities', $manifest['runtime_bridge']['commands']);
        self::assertContains('DiagnoseRuntime', $manifest['runtime_bridge']['commands']);
        self::assertContains('DescribeRuntime', $manifest['runtime_bridge']['commands']);
        self::assertContains('serve-stdio-bridge', $manifest['runtime_bridge']['commands']);
        self::assertContains('check-readiness', $manifest['runtime_bridge']['commands']);
        self::assertContains('system-api-dbus-binding-manifest.json', $manifest['generated_files']);
        self::assertContains('apply-binding.sh', $manifest['generated_files']);
        self::assertContains('files/usr/share/w4/system-api/org.w4.System1.runtime-adapter.json', $manifest['generated_files']);
        self::assertContains('files/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json', $manifest['generated_files']);
        self::assertContains('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php', $manifest['generated_files']);
        self::assertContains('files/usr/local/bin/w4-system-api-dbus-runtime-launcher', $manifest['generated_files']);
        self::assertContains('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php', $manifest['generated_files']);
        self::assertContains('files/usr/local/bin/w4-system-api-dbus-runtime-host', $manifest['generated_files']);
        self::assertContains('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php', $manifest['generated_files']);
        self::assertContains('files/usr/local/bin/w4-system-api-dbus-runtime-adapter', $manifest['generated_files']);
        self::assertContains('files/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php', $manifest['generated_files']);
        self::assertContains('files/usr/local/bin/w4-system-api-dbus-bridge-readiness', $manifest['generated_files']);
        self::assertContains('files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php', $manifest['generated_files']);

        $xml = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'org.w4.System1.Manager.xml');
        self::assertNotFalse($xml);
        self::assertStringContainsString('<interface name="org.w4.System1.Manager">', $xml);
        self::assertStringContainsString('<method name="Call">', $xml);
        self::assertStringContainsString('<method name="GetCapabilities">', $xml);
        self::assertStringContainsString('<property name="BindingStatus" type="s" access="read"/>', $xml);

        $adapterSpecification = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'org.w4.System1.runtime-adapter.json');
        self::assertSame('system-api-dbus-runtime-adapter-spec', $adapterSpecification['kind']);
        self::assertSame('pending-runtime-adapter', $adapterSpecification['status']);
        self::assertSame('serve-dbus', $adapterSpecification['activation']['entry_command']);
        self::assertContains('bus-name-ownership', $adapterSpecification['required_capabilities']);

        $runtimeBootstrap = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'org.w4.System1.runtime-bootstrap.json');
        self::assertSame('system-api-dbus-runtime-bootstrap', $runtimeBootstrap['kind']);
        self::assertSame('serve-dbus', $runtimeBootstrap['launcher']['command']);
        self::assertSame('child-process-stdio-relay', $runtimeBootstrap['host_handoff']['mode']);
        self::assertContains('own-org.w4.System1', $runtimeBootstrap['phases']);

        $serviceFile = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'dbus-1' . DIRECTORY_SEPARATOR . 'system-services' . DIRECTORY_SEPARATOR . 'org.w4.System1.service');
        self::assertNotFalse($serviceFile);
        self::assertStringContainsString('Name=org.w4.System1', $serviceFile);
        self::assertStringContainsString('SystemdService=w4-system-api.service', $serviceFile);
        self::assertStringContainsString('Exec=/usr/local/bin/w4-system-api-dbus-runtime-launcher serve-dbus', $serviceFile);
        self::assertStringContainsString('packaged runtime launcher orchestrates the host handoff', $serviceFile);

        $systemdUnit = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-system-api.service');
        self::assertNotFalse($systemdUnit);
        self::assertStringContainsString('Type=dbus', $systemdUnit);
        self::assertStringContainsString('BusName=org.w4.System1', $systemdUnit);
        self::assertStringContainsString('EnvironmentFile=-/etc/w4/system-api-binding.env', $systemdUnit);
        self::assertStringContainsString('ExecStart=/usr/local/bin/w4-system-api-dbus-runtime-launcher serve-dbus', $systemdUnit);

        $bindingEnv = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api-binding.env');
        self::assertNotFalse($bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_BINDING_STATUS="pending-runtime-adapter"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_BRIDGE_ENTRY="/usr/local/bin/w4-system-api-dbus-bridge"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-adapter"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_LOCAL_TRANSPORT="stdio-ndjson-launcher"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_RUNTIME_HOST_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-host"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-launcher"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC="/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_READINESS_ENTRY="/usr/local/bin/w4-system-api-dbus-bridge-readiness"', $bindingEnv);
        self::assertStringContainsString('W4_SYSTEM_API_DIAGNOSTICS_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-diagnose"', $bindingEnv);

        $bridgeScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-bridge.php');
        self::assertNotFalse($bridgeScript);
        self::assertStringContainsString("const W4_SYSTEM_API_STATUS = 'pending-runtime-adapter';", $bridgeScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-bridge-runtime'", $bridgeScript);
        self::assertStringContainsString("'Call'", $bridgeScript);
        self::assertStringContainsString("'GetCapabilities'", $bridgeScript);
        self::assertStringContainsString("'DescribeRuntime'", $bridgeScript);
        self::assertStringContainsString("'serve-stdio-bridge'", $bridgeScript);
        self::assertStringContainsString("case 'Call':", $bridgeScript);
        self::assertStringContainsString("case 'GetCapabilities':", $bridgeScript);
        self::assertStringContainsString("case 'DiagnoseRuntime':", $bridgeScript);
        self::assertStringContainsString("case 'DescribeRuntime':", $bridgeScript);
        self::assertStringContainsString("case 'serve-stdio-bridge':", $bridgeScript);
        self::assertStringContainsString("case 'serve-dbus':", $bridgeScript);
        self::assertStringContainsString("'code' => 'BINDING_PENDING'", $bridgeScript);
        self::assertStringContainsString('El runtime D-Bus para %s sigue pendiente', $bridgeScript);
        self::assertStringContainsString('org.w4.System1', $bridgeScript);

        $bridgeWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-bridge');
        self::assertNotFalse($bridgeWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php "$@"', $bridgeWrapper);

        $bridgeScriptPath = $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-bridge.php';
        $bridgeDescribe = $this->runGeneratedPhpScript([
            $bridgeScriptPath,
            'DescribeRuntime',
        ]);
        self::assertSame(0, $bridgeDescribe['exitCode'], $bridgeDescribe['stderr']);
        $bridgeDescribePayload = $this->decodeJson($bridgeDescribe['stdout']);
        self::assertSame('system-api-dbus-bridge-runtime', $bridgeDescribePayload['kind']);
        self::assertSame('pending-runtime-adapter', $bridgeDescribePayload['binding_status']);
        self::assertContains('Call', $bridgeDescribePayload['commands']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $bridgeDescribePayload['adapter_specification']['kind']);

        $pendingCallRequestPath = $outputDir . DIRECTORY_SEPARATOR . 'pending-call-request.json';
        file_put_contents(
            $pendingCallRequestPath,
            json_encode([
                'api_version' => '1.0',
                'request_id' => 'binding-call-001',
                'method' => 'system.dbus-runtime',
                'params' => [],
            ], JSON_THROW_ON_ERROR)
        );
        $bridgeCall = $this->runGeneratedPhpScript([
            $bridgeScriptPath,
            'Call',
            '--request-file',
            $pendingCallRequestPath,
        ]);
        self::assertSame(0, $bridgeCall['exitCode'], $bridgeCall['stderr']);
        $bridgeCallPayload = $this->decodeJson($bridgeCall['stdout']);
        self::assertSame('binding-call-001', $bridgeCallPayload['request_id']);
        self::assertNull($bridgeCallPayload['result']);
        self::assertSame('BINDING_PENDING', $bridgeCallPayload['error']['code']);

        $bridgeServeDbus = $this->runGeneratedPhpScript([
            $bridgeScriptPath,
            'serve-dbus',
        ]);
        self::assertSame(3, $bridgeServeDbus['exitCode'], $bridgeServeDbus['stderr']);
        $bridgeServeDbusPayload = $this->decodeJson($bridgeServeDbus['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $bridgeServeDbusPayload['kind']);
        self::assertSame('packaged-dbus-bridge', $bridgeServeDbusPayload['bridge_kind']);
        self::assertSame('serve-dbus', $bridgeServeDbusPayload['requested_command']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $bridgeServeDbusPayload['adapter_specification']['kind']);
        self::assertArrayHasKey('runtime_diagnosis', $bridgeServeDbusPayload);

        $runtimeAdapterScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-adapter.php');
        self::assertNotFalse($runtimeAdapterScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-adapter'", $runtimeAdapterScript);
        self::assertStringContainsString("case 'DescribeActivationPreparation':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'AttemptActivation':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'ExecuteActivation':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'StartNativeRuntimeService':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'ObserveNativeRuntimeServiceState':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'MaterializeNativeBusOwnership':", $runtimeAdapterScript);
        self::assertStringContainsString("case 'serve-stdio-adapter':", $runtimeAdapterScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-activation-attempt'", $runtimeAdapterScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-activation-result'", $runtimeAdapterScript);
        self::assertStringContainsString("'execution-attempted-pending-runtime-adapter'", $runtimeAdapterScript);
        self::assertStringContainsString("'experimental-runtime-backend-materialized'", $runtimeAdapterScript);

        $runtimeAdapterWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-adapter');
        self::assertNotFalse($runtimeAdapterWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php "$@"', $runtimeAdapterWrapper);

        $runtimeLauncherScriptPath = $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-launcher.php';
        $runtimeAdapterScriptPath = $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-adapter.php';
        $runtimeLauncherScript = file_get_contents($runtimeLauncherScriptPath);
        self::assertNotFalse($runtimeLauncherScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-launcher'", $runtimeLauncherScript);
        self::assertStringContainsString("case 'DescribeLauncher':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'DescribeLaunchPlan':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'DescribeActivationBootstrap':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'DescribeActivationPreparation':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'AttemptActivation':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'ExecuteActivation':", $runtimeLauncherScript);
        self::assertStringContainsString("case 'serve-stdio-launcher':", $runtimeLauncherScript);
        self::assertStringContainsString("'handoff_mode' => 'child-process-stdio-relay'", $runtimeLauncherScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-bootstrap'", $runtimeLauncherScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-activation-context'", $runtimeLauncherScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-activation-attempt'", $runtimeLauncherScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-activation-result'", $runtimeLauncherScript);
        self::assertStringContainsString("'execution-attempted-pending-runtime-adapter'", $runtimeLauncherScript);
        self::assertStringContainsString("'launcher_transport' => 'stdio-ndjson-launcher'", $runtimeLauncherScript);

        $runtimeLauncherWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-launcher');
        self::assertNotFalse($runtimeLauncherWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php "$@"', $runtimeLauncherWrapper);

        $runtimeLauncherDescribe = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'DescribeLauncher',
        ]);
        self::assertSame(0, $runtimeLauncherDescribe['exitCode'], $runtimeLauncherDescribe['stderr']);
        $runtimeLauncherDescribePayload = $this->decodeJson($runtimeLauncherDescribe['stdout']);
        self::assertSame('system-api-dbus-runtime-launcher', $runtimeLauncherDescribePayload['kind']);
        self::assertSame('launcher-ready', $runtimeLauncherDescribePayload['status']);
        self::assertSame('child-process-stdio-relay', $runtimeLauncherDescribePayload['handoff_mode']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $runtimeLauncherDescribePayload['launch_plan']['kind']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $runtimeLauncherDescribePayload['launch_plan']['activation_bootstrap']['kind']);
        self::assertSame('child-process-stdio-relay', $runtimeLauncherDescribePayload['launch_plan']['handoff']['mode']);

        $runtimeLauncherBootstrap = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'DescribeActivationBootstrap',
        ]);
        self::assertSame(0, $runtimeLauncherBootstrap['exitCode'], $runtimeLauncherBootstrap['stderr']);
        $runtimeLauncherBootstrapPayload = $this->decodeJson($runtimeLauncherBootstrap['stdout']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $runtimeLauncherBootstrapPayload['kind']);
        self::assertSame('child-process-stdio-relay', $runtimeLauncherBootstrapPayload['host_handoff']['mode']);

        $runtimeLauncherPreparation = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'DescribeActivationPreparation',
        ]);
        self::assertSame(0, $runtimeLauncherPreparation['exitCode'], $runtimeLauncherPreparation['stderr']);
        $runtimeLauncherPreparationPayload = $this->decodeJson($runtimeLauncherPreparation['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-context', $runtimeLauncherPreparationPayload['kind']);
        self::assertSame('/usr/local/bin/w4-system-api-dbus-runtime-launcher', $runtimeLauncherPreparationPayload['resolved_contract']['runtime_launcher_entry']);
        self::assertSame('/usr/local/bin/w4-system-api-dbus-runtime-host', $runtimeLauncherPreparationPayload['resolved_contract']['runtime_host_entry']);
        self::assertSame('/usr/local/bin/w4-system-api-dbus-runtime-adapter', $runtimeLauncherPreparationPayload['resolved_contract']['runtime_adapter_entry']);

        $runtimeLauncherAttempt = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'AttemptActivation',
        ]);
        self::assertSame(0, $runtimeLauncherAttempt['exitCode'], $runtimeLauncherAttempt['stderr']);
        $runtimeLauncherAttemptPayload = $this->decodeJson($runtimeLauncherAttempt['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $runtimeLauncherAttemptPayload['kind']);
        self::assertSame('packaged-runtime-launcher', $runtimeLauncherAttemptPayload['bridge_kind']);
        self::assertSame('missing-runtime-capabilities', $runtimeLauncherAttemptPayload['blocking_reason']);

        $runtimeLauncherExecution = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'ExecuteActivation',
        ]);
        self::assertSame(3, $runtimeLauncherExecution['exitCode'], $runtimeLauncherExecution['stderr']);
        $runtimeLauncherExecutionPayload = $this->decodeJson($runtimeLauncherExecution['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-result', $runtimeLauncherExecutionPayload['kind']);
        self::assertSame('packaged-runtime-launcher', $runtimeLauncherExecutionPayload['bridge_kind']);
        self::assertSame('missing-runtime-capabilities', $runtimeLauncherExecutionPayload['blocking_reason']);

        $runtimeLauncherExecuteReady = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'ExecuteActivation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
        ]));
        self::assertSame(3, $runtimeLauncherExecuteReady['exitCode'], $runtimeLauncherExecuteReady['stderr']);
        $runtimeLauncherExecuteReadyPayload = $this->decodeJson($runtimeLauncherExecuteReady['stdout']);
        self::assertTrue($runtimeLauncherExecuteReadyPayload['executed']);
        self::assertSame('execution-attempted-pending-runtime-adapter', $runtimeLauncherExecuteReadyPayload['status']);
        self::assertSame('runtime-adapter-returned-pending', $runtimeLauncherExecuteReadyPayload['blocking_reason']);
        self::assertSame($runtimeAdapterScriptPath, $runtimeLauncherExecuteReadyPayload['command_result']['entrypoint']);
        self::assertSame('system-api-dbus-runtime-activation', $runtimeLauncherExecuteReadyPayload['command_result']['payload']['kind']);

        $runtimeLauncherPreparationNative = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'DescribeActivationPreparation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]));
        self::assertSame(0, $runtimeLauncherPreparationNative['exitCode'], $runtimeLauncherPreparationNative['stderr']);
        $runtimeLauncherPreparationNativePayload = $this->decodeJson($runtimeLauncherPreparationNative['stdout']);
        self::assertSame('launch-native-runtime-service', $runtimeLauncherPreparationNativePayload['next_action']);
        self::assertSame('StartNativeRuntimeService', $runtimeLauncherPreparationNativePayload['execution_plan']['runtime_backend']['command']);
        self::assertSame('native-runtime-service-bootstrap', $runtimeLauncherPreparationNativePayload['execution_plan']['runtime_backend']['command_role']);

        $runtimeLauncherServeDbusMaterialized = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'serve-dbus',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'experimental-embedded-runtime',
        ]));
        self::assertSame(0, $runtimeLauncherServeDbusMaterialized['exitCode'], $runtimeLauncherServeDbusMaterialized['stderr']);
        $runtimeLauncherServeDbusMaterializedPayload = $this->decodeJson($runtimeLauncherServeDbusMaterialized['stdout']);
        self::assertSame('execution-completed', $runtimeLauncherServeDbusMaterializedPayload['status']);
        self::assertSame('serve-dbus', $runtimeLauncherServeDbusMaterializedPayload['requested_command']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $runtimeLauncherServeDbusMaterializedPayload['launch_plan']['kind']);

        $runtimeLauncherExecuteNative = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'ExecuteActivation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]));
        self::assertSame(0, $runtimeLauncherExecuteNative['exitCode'], $runtimeLauncherExecuteNative['stderr']);
        $runtimeLauncherExecuteNativePayload = $this->decodeJson($runtimeLauncherExecuteNative['stdout']);
        self::assertSame('ready-to-launch-native-runtime', $runtimeLauncherExecuteNativePayload['activation_attempt']['status']);
        self::assertSame('StartNativeRuntimeService', $runtimeLauncherExecuteNativePayload['target_command']['command']);
        self::assertSame('native-runtime-service-bootstrap', $runtimeLauncherExecuteNativePayload['target_command']['command_role']);
        self::assertSame('system-api-dbus-native-runtime-startup', $runtimeLauncherExecuteNativePayload['command_result']['payload']['kind']);
        self::assertSame('native-runtime-service-started', $runtimeLauncherExecuteNativePayload['command_result']['payload']['status']);
        self::assertSame('observe-native-runtime-service-state', $runtimeLauncherExecuteNativePayload['next_action']);

        $runtimeLauncherServeDbus = $this->runGeneratedPhpScript([
            $runtimeLauncherScriptPath,
            'serve-dbus',
        ]);
        self::assertSame(3, $runtimeLauncherServeDbus['exitCode'], $runtimeLauncherServeDbus['stderr']);
        $runtimeLauncherServeDbusPayload = $this->decodeJson($runtimeLauncherServeDbus['stdout']);
        self::assertSame('packaged-runtime-launcher', $runtimeLauncherServeDbusPayload['bridge_kind']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $runtimeLauncherServeDbusPayload['activation_bootstrap']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-context', $runtimeLauncherServeDbusPayload['activation_preparation']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $runtimeLauncherServeDbusPayload['activation_attempt']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-result', $runtimeLauncherServeDbusPayload['activation_execution']['kind']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $runtimeLauncherServeDbusPayload['launch_plan']['kind']);

        $runtimeAdapterPreparation = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'DescribeActivationPreparation',
        ]);
        self::assertSame(0, $runtimeAdapterPreparation['exitCode'], $runtimeAdapterPreparation['stderr']);
        $runtimeAdapterPreparationPayload = $this->decodeJson($runtimeAdapterPreparation['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-context', $runtimeAdapterPreparationPayload['kind']);
        self::assertSame('/usr/local/bin/w4-system-api-dbus-runtime-adapter', $runtimeAdapterPreparationPayload['resolved_contract']['runtime_adapter_entry']);

        $runtimeAdapterAttempt = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'AttemptActivation',
        ]);
        self::assertSame(0, $runtimeAdapterAttempt['exitCode'], $runtimeAdapterAttempt['stderr']);
        $runtimeAdapterAttemptPayload = $this->decodeJson($runtimeAdapterAttempt['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $runtimeAdapterAttemptPayload['kind']);
        self::assertSame('packaged-runtime-adapter', $runtimeAdapterAttemptPayload['bridge_kind']);

        $runtimeAdapterExecution = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'ExecuteActivation',
        ]);
        self::assertSame(3, $runtimeAdapterExecution['exitCode'], $runtimeAdapterExecution['stderr']);
        $runtimeAdapterExecutionPayload = $this->decodeJson($runtimeAdapterExecution['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-result', $runtimeAdapterExecutionPayload['kind']);
        self::assertSame('packaged-runtime-adapter', $runtimeAdapterExecutionPayload['bridge_kind']);

        $runtimeAdapterDescribeNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'DescribeRuntime',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]));
        self::assertSame(0, $runtimeAdapterDescribeNative['exitCode'], $runtimeAdapterDescribeNative['stderr']);
        $runtimeAdapterDescribeNativePayload = $this->decodeJson($runtimeAdapterDescribeNative['stdout']);
        self::assertSame('native-dbus-extension', $runtimeAdapterDescribeNativePayload['runtime_backend']['selected_backend']);
        self::assertSame('dbus-extension-api-incomplete', $runtimeAdapterDescribeNativePayload['runtime_backend']['blocking_reason']);
        self::assertContains('registerObject', $runtimeAdapterDescribeNativePayload['runtime_backend']['native_runtime_api']['missing_api']);

        $runtimeAdapterPreparationNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'DescribeActivationPreparation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]));
        self::assertSame(0, $runtimeAdapterPreparationNative['exitCode'], $runtimeAdapterPreparationNative['stderr']);
        $runtimeAdapterPreparationNativePayload = $this->decodeJson($runtimeAdapterPreparationNative['stdout']);
        self::assertSame('launch-native-runtime-service', $runtimeAdapterPreparationNativePayload['next_action']);
        self::assertSame('StartNativeRuntimeService', $runtimeAdapterPreparationNativePayload['execution_plan']['runtime_backend']['command']);
        self::assertSame('native-runtime-service-bootstrap', $runtimeAdapterPreparationNativePayload['execution_plan']['runtime_backend']['command_role']);

        $runtimeAdapterExecuteReady = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'ExecuteActivation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
        ]));
        self::assertSame(3, $runtimeAdapterExecuteReady['exitCode'], $runtimeAdapterExecuteReady['stderr']);
        $runtimeAdapterExecuteReadyPayload = $this->decodeJson($runtimeAdapterExecuteReady['stdout']);
        self::assertTrue($runtimeAdapterExecuteReadyPayload['executed']);
        self::assertSame('execution-attempted-pending-runtime-adapter', $runtimeAdapterExecuteReadyPayload['status']);
        self::assertSame('runtime-adapter-returned-pending', $runtimeAdapterExecuteReadyPayload['blocking_reason']);
        self::assertSame($runtimeAdapterScriptPath, $runtimeAdapterExecuteReadyPayload['command_result']['entrypoint']);
        self::assertSame('system-api-dbus-runtime-activation', $runtimeAdapterExecuteReadyPayload['command_result']['payload']['kind']);

        $runtimeAdapterServeDbusMaterialized = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'serve-dbus',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'experimental-embedded-runtime',
        ]));
        self::assertSame(0, $runtimeAdapterServeDbusMaterialized['exitCode'], $runtimeAdapterServeDbusMaterialized['stderr']);
        $runtimeAdapterServeDbusMaterializedPayload = $this->decodeJson($runtimeAdapterServeDbusMaterialized['stdout']);
        self::assertSame('experimental-runtime-backend-materialized', $runtimeAdapterServeDbusMaterializedPayload['status']);
        self::assertTrue($runtimeAdapterServeDbusMaterializedPayload['materialized']);
        self::assertSame(0, $runtimeAdapterServeDbusMaterializedPayload['exit_code']);

        $runtimeAdapterExecuteMaterialized = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'ExecuteActivation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'experimental-embedded-runtime',
        ]));
        self::assertSame(0, $runtimeAdapterExecuteMaterialized['exitCode'], $runtimeAdapterExecuteMaterialized['stderr']);
        $runtimeAdapterExecuteMaterializedPayload = $this->decodeJson($runtimeAdapterExecuteMaterialized['stdout']);
        self::assertTrue($runtimeAdapterExecuteMaterializedPayload['executed']);
        self::assertSame('execution-completed', $runtimeAdapterExecuteMaterializedPayload['status']);
        self::assertSame(0, $runtimeAdapterExecuteMaterializedPayload['command_result']['exit_code']);
        self::assertSame('experimental-runtime-backend-materialized', $runtimeAdapterExecuteMaterializedPayload['command_result']['payload']['status']);

        $runtimeAdapterExecuteNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'ExecuteActivation',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY' => $runtimeAdapterScriptPath,
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]));
        self::assertSame(0, $runtimeAdapterExecuteNative['exitCode'], $runtimeAdapterExecuteNative['stderr']);
        $runtimeAdapterExecuteNativePayload = $this->decodeJson($runtimeAdapterExecuteNative['stdout']);
        self::assertSame('ready-to-launch-native-runtime', $runtimeAdapterExecuteNativePayload['activation_attempt']['status']);
        self::assertSame('StartNativeRuntimeService', $runtimeAdapterExecuteNativePayload['target_command']['command']);
        self::assertSame('native-runtime-service-bootstrap', $runtimeAdapterExecuteNativePayload['target_command']['command_role']);
        self::assertSame('system-api-dbus-native-runtime-startup', $runtimeAdapterExecuteNativePayload['command_result']['payload']['kind']);
        self::assertSame('native-runtime-service-started', $runtimeAdapterExecuteNativePayload['command_result']['payload']['status']);
        self::assertSame('observe-native-runtime-service-state', $runtimeAdapterExecuteNativePayload['next_action']);

        $nativeArtifactsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'native-runtime-artifacts';
        $runtimeAdapterStartNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'StartNativeRuntimeService',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeArtifactsDir,
        ]));
        self::assertSame(0, $runtimeAdapterStartNative['exitCode'], $runtimeAdapterStartNative['stderr']);

        $runtimeAdapterObserveNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'ObserveNativeRuntimeServiceState',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeArtifactsDir,
        ]));
        self::assertSame(3, $runtimeAdapterObserveNative['exitCode'], $runtimeAdapterObserveNative['stderr']);
        $runtimeAdapterObserveNativePayload = $this->decodeJson($runtimeAdapterObserveNative['stdout']);
        self::assertSame('system-api-dbus-native-runtime-observation', $runtimeAdapterObserveNativePayload['kind']);
        self::assertSame('native-runtime-service-observed-pending-ownership', $runtimeAdapterObserveNativePayload['status']);
        self::assertTrue($runtimeAdapterObserveNativePayload['observed']);
        self::assertSame('pending-bus-name-ownership', $runtimeAdapterObserveNativePayload['ownership_state']);
        self::assertSame('materialize-native-bus-ownership', $runtimeAdapterObserveNativePayload['next_action']);

        $runtimeAdapterOwnNative = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'MaterializeNativeBusOwnership',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeArtifactsDir,
            'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE' => 'owned',
            'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE' => 'registered',
            'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE' => 'running',
        ]));
        self::assertSame(0, $runtimeAdapterOwnNative['exitCode'], $runtimeAdapterOwnNative['stderr']);
        $runtimeAdapterOwnNativePayload = $this->decodeJson($runtimeAdapterOwnNative['stdout']);
        self::assertSame('system-api-dbus-native-runtime-ownership', $runtimeAdapterOwnNativePayload['kind']);
        self::assertSame('native-runtime-bus-owned', $runtimeAdapterOwnNativePayload['status']);
        self::assertSame('phase-overrides', $runtimeAdapterOwnNativePayload['execution_mode']);
        self::assertSame('bus-name-owned', $runtimeAdapterOwnNativePayload['ownership_state']);
        self::assertSame('runtime-service-active', $runtimeAdapterOwnNativePayload['next_action']);

        $nativeDriverArtifactsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'native-runtime-driver-artifacts';
        $runtimeAdapterStartNativeDriver = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'StartNativeRuntimeService',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeDriverArtifactsDir,
        ]));
        self::assertSame(0, $runtimeAdapterStartNativeDriver['exitCode'], $runtimeAdapterStartNativeDriver['stderr']);

        $runtimeAdapterOwnNativeDriver = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'MaterializeNativeBusOwnership',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeDriverArtifactsDir,
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => $this->rootDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeRuntimeOwnershipDriverStub.php',
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\Tests\\Fixtures\\NativeRuntimeOwnershipDriverStub',
        ]));
        self::assertSame(0, $runtimeAdapterOwnNativeDriver['exitCode'], $runtimeAdapterOwnNativeDriver['stderr']);
        $runtimeAdapterOwnNativeDriverPayload = $this->decodeJson($runtimeAdapterOwnNativeDriver['stdout']);
        self::assertSame('system-api-dbus-native-runtime-ownership', $runtimeAdapterOwnNativeDriverPayload['kind']);
        self::assertSame('native-runtime-bus-owned', $runtimeAdapterOwnNativeDriverPayload['status']);
        self::assertSame('native-driver', $runtimeAdapterOwnNativeDriverPayload['execution_mode']);
        self::assertSame('W4\\OS\\Tests\\Fixtures\\NativeRuntimeOwnershipDriverStub', $runtimeAdapterOwnNativeDriverPayload['ownership_driver']['class_name']);
        self::assertSame('bus-name-owned', $runtimeAdapterOwnNativeDriverPayload['ownership_state']);
        self::assertSame('runtime-service-active', $runtimeAdapterOwnNativeDriverPayload['next_action']);

        $nativeExtensionDriverArtifactsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'native-runtime-extension-driver-artifacts';
        $runtimeAdapterStartNativeExtensionDriver = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'StartNativeRuntimeService',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeExtensionDriverArtifactsDir,
        ]));
        self::assertSame(0, $runtimeAdapterStartNativeExtensionDriver['exitCode'], $runtimeAdapterStartNativeExtensionDriver['stderr']);

        $runtimeAdapterOwnNativeExtensionDriver = $this->runGeneratedPhpScript([
            $runtimeAdapterScriptPath,
            'MaterializeNativeBusOwnership',
        ], array_merge($this->readyRuntimeEnvironment(), [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $nativeExtensionDriverArtifactsDir,
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => $this->rootDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
        ]));
        self::assertSame(0, $runtimeAdapterOwnNativeExtensionDriver['exitCode'], $runtimeAdapterOwnNativeExtensionDriver['stderr']);
        $runtimeAdapterOwnNativeExtensionDriverPayload = $this->decodeJson($runtimeAdapterOwnNativeExtensionDriver['stdout']);
        self::assertSame('system-api-dbus-native-runtime-ownership', $runtimeAdapterOwnNativeExtensionDriverPayload['kind']);
        self::assertSame('native-runtime-bus-owned', $runtimeAdapterOwnNativeExtensionDriverPayload['status']);
        self::assertSame('native-driver', $runtimeAdapterOwnNativeExtensionDriverPayload['execution_mode']);
        self::assertSame('W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver', $runtimeAdapterOwnNativeExtensionDriverPayload['ownership_driver']['class_name']);
        self::assertSame('W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService', $runtimeAdapterOwnNativeExtensionDriverPayload['ownership_driver']['runtime_class_name']);
        self::assertTrue($runtimeAdapterOwnNativeExtensionDriverPayload['ownership_driver']['register_object_signature']['compatible']);
        self::assertSame(
            ['object_path', 'introspection_xml', 'definition', 'exported_object'],
            $runtimeAdapterOwnNativeExtensionDriverPayload['ownership_driver']['register_object_signature']['preferred_parameter_group_match']
        );
        self::assertSame('bus-name-owned', $runtimeAdapterOwnNativeExtensionDriverPayload['ownership_state']);
        self::assertSame('runtime-service-active', $runtimeAdapterOwnNativeExtensionDriverPayload['next_action']);

        $runtimeHostScriptPath = $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-host.php';
        $runtimeHostScript = file_get_contents($runtimeHostScriptPath);
        self::assertNotFalse($runtimeHostScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-host'", $runtimeHostScript);
        self::assertStringContainsString("case 'DescribeHost':", $runtimeHostScript);
        self::assertStringContainsString("case 'serve-stdio-host':", $runtimeHostScript);

        $runtimeHostWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-host');
        self::assertNotFalse($runtimeHostWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php "$@"', $runtimeHostWrapper);

        $runtimeHostDescribe = $this->runGeneratedPhpScript([
            $runtimeHostScriptPath,
            'DescribeHost',
        ]);
        self::assertSame(0, $runtimeHostDescribe['exitCode'], $runtimeHostDescribe['stderr']);
        $runtimeHostDescribePayload = $this->decodeJson($runtimeHostDescribe['stdout']);
        self::assertSame('system-api-dbus-runtime-host', $runtimeHostDescribePayload['kind']);
        self::assertSame('stdio-ndjson-host', $runtimeHostDescribePayload['transport']);

        $runtimeHostServeDbus = $this->runGeneratedPhpScript([
            $runtimeHostScriptPath,
            'serve-dbus',
        ]);
        self::assertSame(3, $runtimeHostServeDbus['exitCode'], $runtimeHostServeDbus['stderr']);
        $runtimeHostServeDbusPayload = $this->decodeJson($runtimeHostServeDbus['stdout']);
        self::assertSame('packaged-runtime-host', $runtimeHostServeDbusPayload['bridge_kind']);

        $readinessScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-bridge-readiness.php');
        self::assertNotFalse($readinessScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-bridge-readiness'", $readinessScript);
        self::assertStringContainsString("'ready' => false", $readinessScript);

        $readinessWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-bridge-readiness');
        self::assertNotFalse($readinessWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php "$@"', $readinessWrapper);

        $diagnosticsScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-diagnose.php');
        self::assertNotFalse($diagnosticsScript);
        self::assertStringContainsString("'kind' => 'system-api-dbus-runtime-diagnosis'", $diagnosticsScript);
        self::assertStringContainsString("'missing_capabilities' => \$missingCapabilities", $diagnosticsScript);

        $diagnosticsWrapper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'w4-system-api-dbus-runtime-diagnose');
        self::assertNotFalse($diagnosticsWrapper);
        self::assertStringContainsString('/usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php "$@"', $diagnosticsWrapper);

        $applyScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'apply-binding.sh');
        self::assertNotFalse($applyScript);
        self::assertStringContainsString('usr/share/dbus-1/system-services', $applyScript);
        self::assertStringContainsString('etc/systemd/system/w4-system-api.service', $applyScript);
        self::assertStringContainsString('usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php', $applyScript);
        self::assertStringContainsString('usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php', $applyScript);
        self::assertStringContainsString('usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php', $applyScript);
        self::assertStringContainsString('usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php', $applyScript);
        self::assertStringContainsString('usr/local/bin/w4-system-api-dbus-runtime-adapter', $applyScript);
        self::assertStringContainsString('usr/local/bin/w4-system-api-dbus-runtime-host', $applyScript);
        self::assertStringContainsString('usr/local/bin/w4-system-api-dbus-runtime-launcher', $applyScript);
        self::assertStringContainsString('usr/local/bin/w4-system-api-dbus-bridge-readiness', $applyScript);
        self::assertStringContainsString('usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php', $applyScript);
        self::assertStringContainsString('usr/local/bin/w4-system-api-dbus-runtime-diagnose', $applyScript);
        self::assertStringContainsString('chmod 0755', $applyScript);
        self::assertStringContainsString('service remains disabled', $applyScript);
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

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, 'No se pudo ejecutar generate_system_api_dbus_binding.php');

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
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runGeneratedPhpScript(array $command, array $extraEnvironment = []): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = array_merge($_ENV, $extraEnvironment);
        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir, $environment);
        self::assertIsResource($process, 'No se pudo ejecutar el bridge empaquetado');

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

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_system_api_dbus_binding.php';
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
