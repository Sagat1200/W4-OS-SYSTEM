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
        $report = $this->activationToolkit->createPendingActivationReport(
            $this->runtimeProbe->createReport(),
            'local-runtime-launcher'
        );
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

        return in_array($method, ['DescribeLauncher', 'DescribeLaunchPlan', 'DescribeActivationBootstrap', 'DescribeActivationPreparation', 'AttemptActivation', 'ServeDbus'], true);
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
