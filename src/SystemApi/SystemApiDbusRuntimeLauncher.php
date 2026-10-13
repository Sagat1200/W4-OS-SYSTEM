<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeLauncher
{
    private SystemApiDbusRuntimeHost $runtimeHost;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
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
                'DescribeHost',
                'DescribeSpecification',
                'DescribeRuntime',
                'DiagnoseRuntime',
                'GetCapabilities',
                'Call',
                'serve-stdio-launcher',
                'serve-dbus',
            ],
            'launch_plan' => $this->createLaunchPlan(),
        ]);
    }

    public function describeLaunchPlan(): string
    {
        return $this->encodeJson($this->createLaunchPlan());
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

            $response = match ($method) {
                'DescribeLauncher' => $this->decodeResponseJson($this->describeLauncher()),
                'DescribeLaunchPlan' => $this->decodeResponseJson($this->describeLaunchPlan()),
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
            ],
            'adapter_specification' => $this->activationToolkit->createAdapterSpecification(),
        ];
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
