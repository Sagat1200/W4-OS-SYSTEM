<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeLauncher
{
    private string $rootDir;
    private SystemApiDbusRuntimeHost $runtimeHost;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->runtimeHost = new SystemApiDbusRuntimeHost($rootDir);
        $this->activationToolkit = new SystemApiDbusRuntimeActivationToolkit();
        $this->runtimeProbe = new SystemApiDbusRuntimeProbeToolkit();
    }

    public function describeLauncher(): string
    {
        return $this->encodeJson([
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-launcher',
            'status' => 'launcher-ready',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'commands' => [
                'DescribeLauncher',
                'DescribeLaunchPlan',
                'DescribeActivationBootstrap',
                'DescribeActivationPreparation',
                'AttemptActivation',
                'ExecuteActivation',
                'DescribeHost',
                'DescribeSpecification',
                'DescribeRuntime',
                'DiagnoseRuntime',
                'GetCapabilities',
                'Call',
                'serve-stdio-launcher',
                'serve-dbus',
            ],
            'handoff_mode' => 'child-process-stdio-relay',
            'launch_plan' => $this->createLaunchPlan(),
        ]);
    }

    public function describeLaunchPlan(): string
    {
        return $this->encodeJson($this->createLaunchPlan());
    }

    public function describeActivationBootstrap(): string
    {
        return $this->encodeJson($this->activationToolkit->createActivationBootstrap());
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
            'local-runtime-launcher',
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
            'local-runtime-launcher',
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

    public function describeHost(): string
    {
        return $this->runtimeHost->describeHost();
    }

    public function describeSpecification(): string
    {
        return $this->runtimeHost->describeSpecification();
    }

    public function describeRuntime(): string
    {
        return $this->runtimeHost->describeRuntime();
    }

    public function diagnoseRuntime(): string
    {
        return $this->runtimeHost->diagnoseRuntime();
    }

    public function getCapabilities(?string $requestId = null): string
    {
        return $this->runtimeHost->getCapabilities($requestId);
    }

    public function call(string $requestJson): string
    {
        return $this->runtimeHost->call($requestJson);
    }

    public function serveDbus(): string
    {
        $runtimeDiagnosis = $this->runtimeProbe->createReport();

        if (($runtimeDiagnosis['ready'] ?? false) === true) {
            $report = $this->decodeResponseJson($this->executeActivation());
            $report['bridge_kind'] = 'local-runtime-launcher';
            $report['requested_command'] = 'serve-dbus';
        } else {
            $report = $this->activationToolkit->createPendingActivationReport(
                $runtimeDiagnosis,
                'local-runtime-launcher'
            );
            $report['exit_code'] = 3;
        }

        $report['launch_plan'] = $this->createLaunchPlan();

        return $this->encodeJson($report);
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function serveStdioLauncher($input, $output): void
    {
        $childProcess = $this->launchRuntimeHostProcess();
        $hostInput = $childProcess['stdin'];
        $hostOutput = $childProcess['stdout'];
        $hostError = $childProcess['stderr'];

        try {
            while (($line = fgets($input)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if ($this->shouldHandleEnvelopeLocally($line)) {
                    $responseLine = $this->handleEnvelopeLine($line);
                } else {
                    $responseLine = $this->relayEnvelopeToRuntimeHost($line, $hostInput, $hostOutput, $hostError);
                }

                fwrite($output, $responseLine);
                fflush($output);
            }
        } finally {
            $this->closeRuntimeHostProcess($childProcess);
        }
    }

    public function handleEnvelopeLine(string $line): string
    {
        try {
            $envelope = $this->decodeEnvelope($line);
            $method = $this->requireMethod($envelope);

            $response = match ($method) {
                'DescribeLauncher' => $this->decodeResponseJson($this->describeLauncher()),
                'DescribeLaunchPlan' => $this->decodeResponseJson($this->describeLaunchPlan()),
                'DescribeActivationBootstrap' => $this->decodeResponseJson($this->describeActivationBootstrap()),
                'DescribeActivationPreparation' => $this->decodeResponseJson($this->describeActivationPreparation()),
                'AttemptActivation' => $this->decodeResponseJson($this->attemptActivation()),
                'ExecuteActivation' => $this->decodeResponseJson($this->executeActivation()),
                'ServeDbus' => $this->decodeResponseJson($this->serveDbus()),
                default => $this->decodeResponseJson($this->runtimeHost->invoke($method, $this->resolveArguments($envelope))),
            };
        } catch (ValidationError $exception) {
            $response = [
                'api_version' => '1.0',
                'request_id' => 'runtime-launcher-invalid-request',
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
    private function createLaunchPlan(): array
    {
        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-launch-plan',
            'status' => 'pending-runtime-adapter',
            'launcher_transport' => 'stdio-ndjson-launcher',
            'target_runtime' => [
                'entrypoint' => 'scripts/system_api_dbus_runtime_host.php',
                'command' => 'serve-stdio-host',
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
            ],
            'activation_command' => 'serve-dbus',
            'handoff' => [
                'kind' => 'runtime-host',
                'strategy' => 'launch-host-process-and-own-bus',
                'mode' => 'child-process-stdio-relay',
            ],
            'activation_bootstrap' => $this->activationToolkit->createActivationBootstrap(),
            'adapter_specification' => $this->activationToolkit->createAdapterSpecification(),
        ];
    }

    /**
     * @return array{process: resource, stdin: resource, stdout: resource, stderr: resource}
     */
    private function launchRuntimeHostProcess(): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $command = [
            PHP_BINARY,
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_host.php',
            'serve-stdio-host',
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        if (!is_resource($process)) {
            throw new ValidationError('No se pudo iniciar el runtime host desde el launcher');
        }

        if (!isset($pipes[0], $pipes[1], $pipes[2]) || !is_resource($pipes[0]) || !is_resource($pipes[1]) || !is_resource($pipes[2])) {
            proc_close($process);
            throw new ValidationError('El launcher no pudo abrir los pipes del runtime host');
        }

        stream_set_blocking($pipes[2], false);

        return [
            'process' => $process,
            'stdin' => $pipes[0],
            'stdout' => $pipes[1],
            'stderr' => $pipes[2],
        ];
    }

    /**
     * @param resource $hostInput
     * @param resource $hostOutput
     * @param resource $hostError
     */
    private function relayEnvelopeToRuntimeHost(string $line, $hostInput, $hostOutput, $hostError): string
    {
        $written = fwrite($hostInput, $line . PHP_EOL);
        if ($written === false) {
            throw new ValidationError('El launcher no pudo enviar el envelope al runtime host');
        }

        fflush($hostInput);

        $responseLine = fgets($hostOutput);
        if (!is_string($responseLine) || trim($responseLine) === '') {
            $errorOutput = stream_get_contents($hostError);
            $detail = is_string($errorOutput) && trim($errorOutput) !== ''
                ? sprintf(': %s', trim($errorOutput))
                : '';

            throw new ValidationError(sprintf('El runtime host no devolvio respuesta al launcher%s', $detail));
        }

        return $responseLine;
    }

    private function shouldHandleEnvelopeLocally(string $line): bool
    {
        try {
            $envelope = $this->decodeEnvelope($line);
            $method = $this->requireMethod($envelope);
        } catch (ValidationError) {
            return true;
        }

        return in_array($method, ['DescribeLauncher', 'DescribeLaunchPlan', 'DescribeActivationBootstrap', 'DescribeActivationPreparation', 'AttemptActivation', 'ExecuteActivation', 'ServeDbus'], true);
    }

    /**
     * @param array{process: resource, stdin: resource, stdout: resource, stderr: resource} $childProcess
     */
    private function closeRuntimeHostProcess(array $childProcess): void
    {
        fclose($childProcess['stdin']);
        fclose($childProcess['stdout']);
        fclose($childProcess['stderr']);
        proc_close($childProcess['process']);
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
            throw new ValidationError('El envelope del runtime launcher debe decodificar a un objeto');
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
        $processCommand = str_ends_with(strtolower($resolvedEntrypoint), '.php')
            ? [PHP_BINARY, $resolvedEntrypoint, trim($command)]
            : [$resolvedEntrypoint, trim($command)];

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $processCommand,
            $descriptorSpec,
            $pipes,
            $this->rootDir,
            $this->captureEnvironment()
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

        return $report;
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
            throw new ValidationError(sprintf('La respuesta JSON del runtime launcher es invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('La respuesta JSON del runtime launcher debe decodificar a un objeto');
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
            throw new ValidationError('No se pudo serializar la respuesta del runtime launcher D-Bus');
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
            throw new ValidationError('No se pudo serializar la respuesta NDJSON del runtime launcher D-Bus');
        }

        return $encoded . PHP_EOL;
    }
}
