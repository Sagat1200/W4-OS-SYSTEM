<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeAdapter
{
    private SystemApiDbusBridge $bridge;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;
    private SystemApiDbusRuntimeBackendToolkit $runtimeBackend;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
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

        if (($report['status'] ?? null) !== 'execution-pending-runtime-adapter') {
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
            dirname(__DIR__, 2),
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

        $report['executed'] = true;
        $report['exit_code'] = $exitCode;
        $report['status'] = match ($exitCode) {
            0 => 'execution-completed',
            3 => 'execution-attempted-pending-runtime-adapter',
            default => 'execution-attempted-failed',
        };
        $report['blocking_reason'] = match ($exitCode) {
            0 => null,
            3 => 'runtime-adapter-returned-pending',
            default => 'runtime-adapter-command-failed',
        };
        $report['command_result'] = $commandResult;
        $report['next_action'] = match ($exitCode) {
            0 => 'observe-runtime-adapter-state',
            3 => 'inspect-runtime-adapter-report',
            default => 'inspect-runtime-adapter-error',
        };

        return $report;
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
