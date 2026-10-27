<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeAdapter
{
    private string $rootDir;
    private SystemApiDbusBridge $bridge;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;
    private SystemApiDbusRuntimeBackendToolkit $runtimeBackend;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->bridge = new SystemApiDbusBridge($rootDir);
        $this->activationToolkit = new SystemApiDbusRuntimeActivationToolkit();
        $this->runtimeBackend = new SystemApiDbusRuntimeBackendToolkit();
        $this->runtimeProbe = new SystemApiDbusRuntimeProbeToolkit();
    }

    public function call(string $requestJson): string
    {
        return $this->bridge->call($requestJson);
    }

    public function getCapabilities(?string $requestId = null): string
    {
        return $this->bridge->getCapabilities($requestId);
    }

    public function diagnoseRuntime(): string
    {
        return $this->bridge->diagnoseRuntime();
    }

    public function describeRuntime(): string
    {
        $runtimeDiagnosis = $this->runtimeProbe->createReport();
        $runtimeBackend = $this->runtimeBackend->createReport($runtimeDiagnosis);

        return $this->encodeJson([
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-adapter',
            'status' => $runtimeBackend['status'] ?? 'pending-runtime-adapter',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'commands' => [
                'DescribeSpecification',
                'DescribeActivationPreparation',
                'AttemptActivation',
                'ExecuteActivation',
                'DescribeNativeRuntimeService',
                'StartNativeRuntimeService',
                'DescribeNativeRuntimeCoupling',
                'ObserveNativeRuntimeServiceState',
                'MaterializeNativeBusOwnership',
                'DescribeRuntime',
                'DiagnoseRuntime',
                'GetCapabilities',
                'Call',
                'serve-stdio-adapter',
                'serve-dbus',
            ],
            'runtime_diagnosis' => $runtimeDiagnosis,
            'runtime_backend' => $runtimeBackend,
            'adapter_specification' => $this->activationToolkit->createAdapterSpecification(),
        ]);
    }

    public function serveDbus(): string
    {
        $runtimeDiagnosis = $this->runtimeProbe->createReport();
        $runtimeBackend = $this->runtimeBackend->createReport($runtimeDiagnosis);

        if (($runtimeBackend['materialized'] ?? false) === true) {
            return $this->encodeJson([
                'api_version' => '1.0',
                'kind' => 'system-api-dbus-runtime-activation',
                'status' => $runtimeBackend['status'] ?? 'runtime-backend-materialized',
                'bridge_kind' => 'local-runtime-adapter',
                'requested_command' => 'serve-dbus',
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'materialized' => true,
                'exit_code' => 0,
                'adapter_specification' => $this->activationToolkit->createAdapterSpecification(),
                'activation_bootstrap' => $this->activationToolkit->createActivationBootstrap(),
                'runtime_diagnosis' => $runtimeDiagnosis,
                'runtime_backend' => $runtimeBackend,
                'next_action' => 'observe-runtime-backend',
            ]);
        }

        $report = $this->activationToolkit->createPendingActivationReport(
            $runtimeDiagnosis,
            'local-runtime-adapter'
        );
        $report['runtime_backend'] = $runtimeBackend;
        $report['exit_code'] = 3;

        return $this->encodeJson($report);
    }

    public function describeNativeRuntimeService(): string
    {
        return $this->encodeJson(
            $this->runtimeBackend->createNativeRuntimeServiceDefinition($this->runtimeProbe->createReport())
        );
    }

    public function startNativeRuntimeService(): string
    {
        $environment = $this->captureEnvironment();
        $runtimeDiagnosis = $this->runtimeProbe->createReport($environment);
        $report = $this->runtimeBackend->createNativeRuntimeServiceStartupReport($runtimeDiagnosis, $environment);
        $this->runtimeBackend->persistNativeRuntimeStartupArtifacts($report);

        if (
            ($report['startup_acknowledged'] ?? false) === true
            && $this->resolveNativeRuntimeStartupMode($environment) === 'launch-process'
        ) {
            $report = $this->launchNativeRuntimeServiceProcess($report, $runtimeDiagnosis, $environment);
            if (($report['launched'] ?? false) !== true && ($report['terminal_observation_present'] ?? false) !== true) {
                $this->runtimeBackend->persistNativeRuntimeStartupArtifacts($report);
            }
        }

        return $this->encodeJson($report);
    }

    public function serveNativeRuntimeService(): string
    {
        $environment = $this->captureEnvironment();
        $environment['W4_SYSTEM_API_NATIVE_RUNTIME_PERSIST_TERMINAL_STATE'] = '1';
        $runtimeDiagnosis = $this->runtimeProbe->createReport($environment);
        $startupReport = $this->runtimeBackend->createNativeRuntimeServiceStartupReport($runtimeDiagnosis, $environment);

        if (($startupReport['startup_acknowledged'] ?? false) !== true) {
            return $this->encodeJson($startupReport);
        }

        $pid = getmypid();
        if (!is_int($pid) || $pid <= 0) {
            $startupReport['status'] = 'native-runtime-service-launch-failed';
            $startupReport['exit_code'] = 1;
            $startupReport['blocking_reason'] = 'native-runtime-service-pid-unavailable';
            $startupReport['next_action'] = 'inspect-native-runtime-service-report';

            return $this->encodeJson($startupReport);
        }

        $startupReport['status'] = 'native-runtime-service-launch-recorded';
        $startupReport['ownership_state'] = 'pending-bus-name-ownership';
        $startupReport['blocking_reason'] = 'native-runtime-bus-ownership-pending';
        $startupReport['next_action'] = 'observe-native-runtime-service-state';
        $startupReport['launch_mode'] = 'background-process';
        $startupReport['launched'] = true;
        $startupReport['pid'] = $pid;
        $startupReport['service_definition'] = $this->markNativeRuntimeServiceDefinitionAsLaunched(
            is_array($startupReport['service_definition'] ?? null) ? $startupReport['service_definition'] : []
        );
        $this->runtimeBackend->persistNativeRuntimeStartupArtifacts($startupReport);

        $report = $this->runtimeBackend->createNativeBusOwnershipReport($runtimeDiagnosis, $environment);
        if (!$this->hasPersistedTerminalNativeRuntimeArtifacts($report)) {
            $this->runtimeBackend->persistNativeRuntimeOwnershipArtifacts($report);
        }

        $keepAliveSeconds = $this->resolveNativeRuntimeKeepAliveSeconds($environment);
        if ($keepAliveSeconds > 0) {
            sleep($keepAliveSeconds);
        }

        return $this->encodeJson($report);
    }

    public function describeNativeRuntimeCoupling(): string
    {
        return $this->encodeJson(
            $this->runtimeBackend->createNativeRuntimeCouplingReport($this->runtimeProbe->createReport())
        );
    }

    public function observeNativeRuntimeServiceState(): string
    {
        return $this->encodeJson(
            $this->runtimeBackend->createNativeRuntimeServiceObservationReport($this->runtimeProbe->createReport())
        );
    }

    public function materializeNativeBusOwnership(): string
    {
        $report = $this->runtimeBackend->createNativeBusOwnershipReport($this->runtimeProbe->createReport());
        $this->runtimeBackend->persistNativeRuntimeOwnershipArtifacts($report);

        return $this->encodeJson($report);
    }

    public function describeSpecification(): string
    {
        return $this->encodeJson($this->activationToolkit->createAdapterSpecification());
    }

    public function describeActivationPreparation(): string
    {
        return $this->encodeJson($this->activationToolkit->createActivationPreparation(
            $this->runtimeProbe->createReport(),
            null,
            [
                'runtime_launcher_entry' => 'scripts/system_api_dbus_runtime_launcher.php',
                'runtime_host_entry' => 'scripts/system_api_dbus_runtime_host.php',
                'runtime_adapter_entry' => 'scripts/system_api_dbus_runtime_adapter.php',
                'runtime_bootstrap_spec' => 'embedded:DescribeActivationBootstrap',
            ]
        ));
    }

    public function attemptActivation(): string
    {
        return $this->encodeJson($this->activationToolkit->createActivationAttempt(
            $this->runtimeProbe->createReport(),
            'local-runtime-adapter',
            null,
            [
                'runtime_launcher_entry' => 'scripts/system_api_dbus_runtime_launcher.php',
                'runtime_host_entry' => 'scripts/system_api_dbus_runtime_host.php',
                'runtime_adapter_entry' => 'scripts/system_api_dbus_runtime_adapter.php',
                'runtime_bootstrap_spec' => 'embedded:DescribeActivationBootstrap',
            ]
        ));
    }

    public function executeActivation(): string
    {
        $report = $this->activationToolkit->createActivationExecution(
            $this->runtimeProbe->createReport(),
            'local-runtime-adapter',
            null,
            [
                'runtime_launcher_entry' => 'scripts/system_api_dbus_runtime_launcher.php',
                'runtime_host_entry' => 'scripts/system_api_dbus_runtime_host.php',
                'runtime_adapter_entry' => 'scripts/system_api_dbus_runtime_adapter.php',
                'runtime_bootstrap_spec' => 'embedded:DescribeActivationBootstrap',
            ]
        );

        if (!in_array($report['status'] ?? null, ['execution-pending-runtime-adapter', 'execution-pending-native-runtime-service'], true)) {
            return $this->encodeJson($report);
        }

        return $this->encodeJson($this->finalizeActivationExecution(
            $report,
            $this->executeTargetCommand($report['target_command'] ?? null)
        ));
    }

    public function invoke(string $method, array $arguments = []): string
    {
        return match ($method) {
            'Call' => $this->call($this->resolveRequestJson($arguments)),
            'GetCapabilities' => $this->getCapabilities($this->optionalStringArgument($arguments, 'request_id')),
            'DiagnoseRuntime' => $this->diagnoseRuntime(),
            'DescribeRuntime' => $this->describeRuntime(),
            'DescribeSpecification' => $this->describeSpecification(),
            'DescribeActivationPreparation' => $this->describeActivationPreparation(),
            'AttemptActivation' => $this->attemptActivation(),
            'ExecuteActivation' => $this->executeActivation(),
            'DescribeNativeRuntimeService' => $this->describeNativeRuntimeService(),
            'StartNativeRuntimeService' => $this->startNativeRuntimeService(),
            'ServeNativeRuntimeService' => $this->serveNativeRuntimeService(),
            'DescribeNativeRuntimeCoupling' => $this->describeNativeRuntimeCoupling(),
            'ObserveNativeRuntimeServiceState' => $this->observeNativeRuntimeServiceState(),
            'MaterializeNativeBusOwnership' => $this->materializeNativeBusOwnership(),
            'ServeDbus' => $this->serveDbus(),
            default => throw new ValidationError(sprintf('Metodo de runtime adapter no soportado: %s', $method)),
        };
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function serveStdioAdapter($input, $output): void
    {
        while (($line = fgets($input)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            fwrite($output, $this->handleEnvelopeLine($line));
            fflush($output);
        }
    }

    public function handleEnvelopeLine(string $line): string
    {
        try {
            $envelope = $this->decodeEnvelope($line);
            $method = $this->requireMethod($envelope);
            $arguments = $this->resolveArguments($envelope);

            $response = $this->decodeResponseJson($this->invoke($method, $arguments));
        } catch (ValidationError $exception) {
            $response = [
                'api_version' => '1.0',
                'request_id' => 'runtime-adapter-invalid-request',
                'result' => null,
                'error' => [
                    'code' => 'INVALID_ARGUMENT',
                    'message' => $exception->getMessage(),
                    'retryable' => false,
                ],
            ];
        }

        return $this->encodeNdjson($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeEnvelope(string $line): array
    {
        try {
            $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('Envelope JSON invalido: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('El envelope del runtime adapter debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function requireMethod(array $envelope): string
    {
        $method = $envelope['method'] ?? null;
        if (!is_string($method) || trim($method) === '') {
            throw new ValidationError('method debe ser un string no vacio');
        }

        return trim($method);
    }

    /**
     * @param array<string, mixed> $envelope
     * @return array<string, mixed>
     */
    private function resolveArguments(array $envelope): array
    {
        $arguments = $envelope['arguments'] ?? [];
        if (!is_array($arguments)) {
            throw new ValidationError('arguments debe ser un objeto JSON');
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function resolveRequestJson(array $arguments): string
    {
        $requestJson = $arguments['request_json'] ?? null;
        $request = $arguments['request'] ?? null;

        if (is_string($requestJson) && trim($requestJson) !== '') {
            return trim($requestJson);
        }

        if (is_array($request)) {
            $encoded = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new ValidationError('No se pudo serializar request dentro del runtime adapter');
            }

            return $encoded;
        }

        throw new ValidationError('Call requiere arguments.request_json o arguments.request');
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function optionalStringArgument(array $arguments, string $field): ?string
    {
        $value = $arguments[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('arguments.%s debe ser un string no vacio cuando se usa', $field));
        }

        return trim($value);
    }

    /**
     * @param mixed $targetCommand
     * @return array<string, mixed>
     */
    private function executeTargetCommand(mixed $targetCommand): array
    {
        if (!is_array($targetCommand)) {
            throw new ValidationError('target_command debe ser un objeto para ejecutar la activacion');
        }

        $entrypoint = $targetCommand['entrypoint'] ?? null;
        $command = $targetCommand['command'] ?? null;

        if (!is_string($entrypoint) || trim($entrypoint) === '') {
            throw new ValidationError('target_command.entrypoint debe ser un string no vacio');
        }

        if (!is_string($command) || trim($command) === '') {
            throw new ValidationError('target_command.command debe ser un string no vacio');
        }

        $resolvedEntrypoint = trim($entrypoint);
        $commandRole = is_string($targetCommand['command_role'] ?? null)
            ? trim((string) $targetCommand['command_role'])
            : '';
        $processCommand = str_ends_with(strtolower($resolvedEntrypoint), '.php')
            ? [PHP_BINARY, $resolvedEntrypoint, trim($command)]
            : [$resolvedEntrypoint, trim($command)];
        $environment = $this->captureEnvironment();
        if (
            $commandRole === 'native-runtime-service-bootstrap'
            && !isset($environment['W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE'])
        ) {
            $environment['W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE'] = 'launch-process';
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $processCommand,
            $descriptorSpec,
            $pipes,
            dirname(__DIR__, 2),
            $environment
        );

        if (!is_resource($process)) {
            throw new ValidationError(sprintf('No se pudo ejecutar el target command %s %s', $resolvedEntrypoint, $command));
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $payload = null;

        if (is_string($stdout) && trim($stdout) !== '') {
            try {
                $decoded = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            } catch (\JsonException) {
                $payload = null;
            }
        }

        return [
            'invoked' => true,
            'entrypoint' => $resolvedEntrypoint,
            'command' => trim($command),
            'exit_code' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
            'payload' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string> $environment
     * @return array<string, mixed>
     */
    private function launchNativeRuntimeServiceProcess(array $report, array $runtimeDiagnosis, array $environment): array
    {
        $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
        $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
        $pidFilePath = is_string($artifacts['pid_file_path'] ?? null) && trim((string) $artifacts['pid_file_path']) !== ''
            ? trim((string) $artifacts['pid_file_path'])
            : null;
        $launched = $this->launchDetachedNativeRuntimeServiceProcess($environment);
        $pid = $pidFilePath !== null ? $this->waitForNativeRuntimePid($pidFilePath) : null;
        if (!$launched || $pid === null) {
            $terminatedReport = $this->captureTerminalObservationFromLaunchedRuntime($report, $runtimeDiagnosis, $environment);
            if ($terminatedReport !== null) {
                return $terminatedReport;
            }

            $report['status'] = 'native-runtime-service-launch-failed';
            $report['exit_code'] = 1;
            $report['ownership_state'] = 'startup-recorded-no-process';
            $report['blocking_reason'] = 'native-runtime-service-launch-failed';
            $report['next_action'] = 'inspect-native-runtime-service-report';
            $report['launch_mode'] = 'background-process';
            $report['launched'] = false;

            return $report;
        }

        $report['status'] = 'native-runtime-service-launch-recorded';
        $report['ownership_state'] = 'pending-bus-name-ownership';
        $report['blocking_reason'] = 'native-runtime-bus-ownership-pending';
        $report['next_action'] = 'observe-native-runtime-service-state';
        $report['launch_mode'] = 'background-process';
        $report['launched'] = true;
        $report['pid'] = $pid;
        $report['service_definition'] = $this->markNativeRuntimeServiceDefinitionAsLaunched(
            is_array($report['service_definition'] ?? null) ? $report['service_definition'] : []
        );

        return $this->reconcileLaunchedNativeRuntimeServiceReport($report, $runtimeDiagnosis, $environment);
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @return array<string, mixed>
     */
    private function markNativeRuntimeServiceDefinitionAsLaunched(array $serviceDefinition): array
    {
        $serviceDefinition['startup_mode'] = 'launch-process';
        $serviceDefinition['blocking_reason'] = 'native-runtime-bus-ownership-pending';
        $serviceDefinition['next_action'] = 'observe-native-runtime-service-state';

        return $serviceDefinition;
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string> $environment
     * @return array<string, mixed>
     */
    private function reconcileLaunchedNativeRuntimeServiceReport(array $report, array $runtimeDiagnosis, array $environment): array
    {
        $pid = is_int($report['pid'] ?? null) ? $report['pid'] : null;
        if (!is_int($pid) || $pid <= 0) {
            return $report;
        }

        $deadline = microtime(true) + ($this->resolveNativeRuntimeLaunchObservationTimeoutMilliseconds($environment) / 1000);
        do {
            $observation = $this->runtimeBackend->createNativeRuntimeServiceObservationReport($runtimeDiagnosis, $environment);
            $observedPid = is_int($observation['pid'] ?? null) ? $observation['pid'] : null;
            $ownershipState = is_string($observation['ownership_state'] ?? null)
                ? trim((string) $observation['ownership_state'])
                : null;

            if ($observedPid === $pid && $ownershipState === 'bus-name-owned') {
                return $this->mergeNativeRuntimeObservationIntoStartupReport($report, $observation);
            }

            if ($observedPid === $pid && ($observation['observed'] ?? false) === true) {
                $report['service_observation'] = $observation;
            }

            usleep(50000);
        } while (microtime(true) < $deadline);

        return $report;
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $observation
     * @return array<string, mixed>
     */
    private function mergeNativeRuntimeObservationIntoStartupReport(array $report, array $observation): array
    {
        $report['status'] = 'native-runtime-bus-owned';
        $report['ownership_state'] = 'bus-name-owned';
        $report['blocking_reason'] = null;
        $report['next_action'] = 'runtime-service-active';
        $report['service_observation'] = $observation;

        if (is_array($report['service_definition'] ?? null)) {
            $report['service_definition']['startup_mode'] = 'launch-process';
            $report['service_definition']['blocking_reason'] = null;
            $report['service_definition']['next_action'] = 'runtime-service-active';
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function hasPersistedTerminalNativeRuntimeArtifacts(array $report): bool
    {
        $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
        $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
        $statusFilePath = is_string($artifacts['status_file_path'] ?? null) && trim((string) $artifacts['status_file_path']) !== ''
            ? trim((string) $artifacts['status_file_path'])
            : null;

        if ($statusFilePath === null || !is_file($statusFilePath)) {
            return false;
        }

        $rawStatus = file_get_contents($statusFilePath);
        if (!is_string($rawStatus) || trim($rawStatus) === '') {
            return false;
        }

        try {
            $decodedStatus = json_decode($rawStatus, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        if (!is_array($decodedStatus)) {
            return false;
        }

        $status = is_string($decodedStatus['status'] ?? null)
            ? trim((string) $decodedStatus['status'])
            : null;

        return in_array($status, ['native-runtime-service-loop-exited', 'native-runtime-wait-loop-failed'], true);
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string> $environment
     * @return array<string, mixed>|null
     */
    private function captureTerminalObservationFromLaunchedRuntime(
        array $report,
        array $runtimeDiagnosis,
        array $environment
    ): ?array {
        $observation = $this->runtimeBackend->createNativeRuntimeServiceObservationReport($runtimeDiagnosis, $environment);
        if (($observation['startup_acknowledged'] ?? false) !== true) {
            return null;
        }

        if (($observation['status'] ?? null) !== 'native-runtime-service-not-running') {
            return null;
        }

        $report['status'] = 'native-runtime-service-loop-exited';
        $report['exit_code'] = 3;
        $report['ownership_state'] = $observation['ownership_state'] ?? 'startup-recorded-no-process';
        $report['blocking_reason'] = $observation['blocking_reason'] ?? 'native-runtime-service-not-running';
        $report['next_action'] = $observation['next_action'] ?? 'launch-native-runtime-service';
        $report['launch_mode'] = 'background-process';
        $report['launched'] = false;
        $report['service_observation'] = $observation;
        $report['terminal_observation_present'] = true;

        return $report;
    }

    /**
     * @param array<string, string> $environment
     */
    private function launchDetachedNativeRuntimeServiceProcess(array $environment): bool
    {
        $scriptPath = $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_adapter.php';
        if (!is_file($scriptPath)) {
            return false;
        }

        return PHP_OS_FAMILY === 'Windows'
            ? $this->launchDetachedNativeRuntimeServiceProcessOnWindows($scriptPath, $environment)
            : $this->launchDetachedNativeRuntimeServiceProcessOnUnix($scriptPath, $environment);
    }

    /**
     * @param array<string, string> $environment
     */
    private function launchDetachedNativeRuntimeServiceProcessOnUnix(string $scriptPath, array $environment): bool
    {
        $phpArguments = [
            escapeshellarg(PHP_BINARY),
            ...$this->resolveNativeRuntimeDetachedPhpArguments($environment, static fn (string $argument): string => escapeshellarg($argument)),
            escapeshellarg($scriptPath),
            escapeshellarg('serve-native-runtime-service'),
        ];
        $command = sprintf(
            'cd %s && nohup %s >/dev/null 2>&1 &',
            escapeshellarg($this->rootDir),
            implode(' ', $phpArguments)
        );

        shell_exec('sh -c ' . escapeshellarg($command));

        return true;
    }

    /**
     * @param array<string, string> $environment
     */
    private function launchDetachedNativeRuntimeServiceProcessOnWindows(string $scriptPath, array $environment): bool
    {
        $phpCommand = array_merge(
            [$this->quoteCmdLiteral(PHP_BINARY)],
            $this->resolveNativeRuntimeDetachedPhpArguments($environment, fn (string $argument): string => $this->quoteCmdLiteral($argument)),
            [
                $this->quoteCmdLiteral($scriptPath),
                'serve-native-runtime-service',
            ]
        );
        $command = $this->resolveWindowsCmdBinary()
            . ' /c "cd /d '
            . $this->quoteCmdLiteral($this->rootDir)
            . ' && start "" /B '
            . implode(' ', $phpCommand)
            . ' >NUL 2>NUL"';

        shell_exec($command);

        return true;
    }

    /**
     * @param array<string, string> $environment
     * @param callable(string): string $quoteArgument
     * @return list<string>
     */
    private function resolveNativeRuntimeDetachedPhpArguments(array $environment, callable $quoteArgument): array
    {
        $extensionPath = $this->resolveNativeRuntimeExtensionPath($environment);
        if ($extensionPath === null) {
            return [];
        }

        return [
            $quoteArgument('-d'),
            $quoteArgument('extension=' . $extensionPath),
        ];
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeExtensionPath(array $environment): ?string
    {
        foreach ([
            'W4_SYSTEM_API_NATIVE_RUNTIME_EXTENSION_PATH',
            'W4_SYSTEM_API_REMOTE_DBUS_MODULE',
        ] as $key) {
            $value = $environment[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function quoteCmdLiteral(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeStartupMode(array $environment): string
    {
        $mode = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE'] ?? 'bootstrap-contract';

        return is_string($mode) && trim($mode) === 'launch-process'
            ? 'launch-process'
            : 'bootstrap-contract';
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeKeepAliveSeconds(array $environment): int
    {
        $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS'] ?? null;
        if (!is_string($value) || preg_match('/^\d+$/', trim($value)) !== 1) {
            return 0;
        }

        $seconds = (int) trim($value);

        return $seconds > 0 ? min($seconds, 30) : 0;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeLaunchObservationTimeoutMilliseconds(array $environment): int
    {
        $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_LAUNCH_OBSERVATION_TIMEOUT_MS'] ?? null;
        if (!is_string($value) || preg_match('/^\d+$/', trim($value)) !== 1) {
            return 1500;
        }

        $milliseconds = (int) trim($value);

        return $milliseconds > 0 ? min($milliseconds, 5000) : 0;
    }

    private function resolveWindowsCmdBinary(): string
    {
        $roots = [];
        $systemRoot = getenv('SystemRoot');
        if (is_string($systemRoot) && trim($systemRoot) !== '') {
            $roots[] = trim($systemRoot);
        }

        $windir = getenv('WINDIR');
        if (is_string($windir) && trim($windir) !== '') {
            $roots[] = trim($windir);
        }

        $roots[] = 'C:\\WINDOWS';
        $roots[] = 'C:\\Windows';

        foreach (array_unique($roots) as $root) {
            $candidate = rtrim($root, "\\/") . DIRECTORY_SEPARATOR . 'System32' . DIRECTORY_SEPARATOR . 'cmd.exe';
            if (is_file($candidate)) {
                return $this->quoteCmdLiteral($candidate);
            }
        }

        return 'cmd.exe';
    }

    private function waitForNativeRuntimePid(string $pidFilePath): ?int
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            if (is_file($pidFilePath)) {
                $raw = file_get_contents($pidFilePath);
                $trimmed = is_string($raw) ? trim($raw) : '';
                if ($trimmed !== '' && preg_match('/^\d+$/', $trimmed) === 1) {
                    $pid = (int) $trimmed;
                    if ($pid > 0) {
                        return $pid;
                    }
                }
            }

            usleep(50000);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $commandResult
     * @return array<string, mixed>
     */
    private function finalizeActivationExecution(array $report, array $commandResult): array
    {
        $exitCode = is_int($commandResult['exit_code'] ?? null) ? $commandResult['exit_code'] : 1;
        $commandRole = is_string($report['target_command']['command_role'] ?? null)
            ? $report['target_command']['command_role']
            : 'runtime-adapter-activation';

        $report['executed'] = true;
        $report['exit_code'] = $exitCode;
        $report['status'] = match ($exitCode) {
            0 => 'execution-completed',
            3 => 'execution-attempted-pending-runtime-adapter',
            default => 'execution-attempted-failed',
        };
        $report['blocking_reason'] = match ($exitCode) {
            0 => null,
            3 => $commandRole === 'native-runtime-service-bootstrap'
                ? 'native-runtime-service-returned-pending'
                : 'runtime-adapter-returned-pending',
            default => 'runtime-adapter-command-failed',
        };
        $report['command_result'] = $commandResult;
        $report['next_action'] = match ($exitCode) {
            0 => $commandRole === 'native-runtime-service-bootstrap'
                ? 'observe-native-runtime-service-state'
                : 'observe-runtime-adapter-state',
            3 => $commandRole === 'native-runtime-service-bootstrap'
                ? 'inspect-native-runtime-service-report'
                : 'inspect-runtime-adapter-report',
            default => 'inspect-runtime-adapter-error',
        };

        return $this->activationToolkit->reconcileNativeRuntimeLaunchExecution($report, $commandResult);
    }


    /**
     * @return array<string, string>
     */
    private function captureEnvironment(): array
    {
        $environment = getenv();
        if (!is_array($environment)) {
            return [];
        }

        $captured = [];
        foreach ($environment as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $captured[$key] = $value;
            }
        }

        return $captured;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponseJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('La respuesta JSON del runtime adapter es invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('La respuesta JSON del runtime adapter debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeJson(array $payload): string
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar la respuesta del runtime adapter D-Bus');
        }

        return $encoded . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeNdjson(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar la respuesta NDJSON del runtime adapter D-Bus');
        }

        return $encoded . PHP_EOL;
    }

}
