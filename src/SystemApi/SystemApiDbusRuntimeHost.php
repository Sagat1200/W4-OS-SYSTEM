<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeHost
{
    private SystemApiDbusRuntimeAdapter $runtimeAdapter;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
        $this->runtimeAdapter = new SystemApiDbusRuntimeAdapter($rootDir);
        $this->activationToolkit = new SystemApiDbusRuntimeActivationToolkit();
        $this->runtimeProbe = new SystemApiDbusRuntimeProbeToolkit();
    }

    public function describeHost(): string
    {
        return $this->encodeJson([
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-host',
            'status' => 'local-runtime-host-ready',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'transport' => 'stdio-ndjson-host',
            'commands' => [
                'DescribeHost',
                'DescribeSpecification',
                'DescribeRuntime',
                'DiagnoseRuntime',
                'GetCapabilities',
                'Call',
                'serve-stdio-host',
                'serve-dbus',
            ],
            'delegates_to' => [
                'runtime_adapter' => 'scripts/system_api_dbus_runtime_adapter.php',
                'call' => 'SystemApiDbusRuntimeAdapter::call',
                'get_capabilities' => 'SystemApiDbusRuntimeAdapter::getCapabilities',
            ],
            'adapter_specification' => $this->activationToolkit->createAdapterSpecification(),
        ]);
    }

    public function describeSpecification(): string
    {
        return $this->runtimeAdapter->describeSpecification();
    }

    public function describeRuntime(): string
    {
        return $this->runtimeAdapter->describeRuntime();
    }

    public function diagnoseRuntime(): string
    {
        return $this->runtimeAdapter->diagnoseRuntime();
    }

    public function getCapabilities(?string $requestId = null): string
    {
        return $this->runtimeAdapter->getCapabilities($requestId);
    }

    public function call(string $requestJson): string
    {
        return $this->runtimeAdapter->call($requestJson);
    }

    public function serveDbus(): string
    {
        $report = $this->decodeResponseJson($this->runtimeAdapter->serveDbus());
        $report['bridge_kind'] = 'local-runtime-host';

        return $this->encodeJson($report);
    }

    public function invoke(string $method, array $arguments = []): string
    {
        return match ($method) {
            'DescribeHost' => $this->describeHost(),
            'DescribeSpecification' => $this->describeSpecification(),
            'DescribeRuntime' => $this->describeRuntime(),
            'DiagnoseRuntime' => $this->diagnoseRuntime(),
            'GetCapabilities' => $this->getCapabilities($this->optionalStringArgument($arguments, 'request_id')),
            'Call' => $this->call($this->resolveRequestJson($arguments)),
            'ServeDbus' => $this->serveDbus(),
            default => throw new ValidationError(sprintf('Metodo de runtime host no soportado: %s', $method)),
        };
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function serveStdioHost($input, $output): void
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

            if ($method === 'DescribeHost') {
                $response = $this->decodeResponseJson($this->describeHost());
            } elseif ($method === 'ServeDbus') {
                $response = $this->decodeResponseJson($this->serveDbus());
            } else {
                return $this->runtimeAdapter->handleEnvelopeLine($line);
            }
        } catch (ValidationError $exception) {
            $response = [
                'api_version' => '1.0',
                'request_id' => 'runtime-host-invalid-request',
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
            throw new ValidationError('El envelope del runtime host debe decodificar a un objeto');
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
                throw new ValidationError('No se pudo serializar request dentro del runtime host');
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
     * @return array<string, mixed>
     */
    private function decodeResponseJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('La respuesta JSON del runtime host es invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('La respuesta JSON del runtime host debe decodificar a un objeto');
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
            throw new ValidationError('No se pudo serializar la respuesta del runtime host D-Bus');
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
            throw new ValidationError('No se pudo serializar la respuesta NDJSON del runtime host D-Bus');
        }

        return $encoded . PHP_EOL;
    }
}
