<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusBridge
{
    private const API_VERSION = '1.0';
    private const BUS_NAME = 'org.w4.System1';
    private const OBJECT_PATH = '/org/w4/System1';
    private const INTERFACE_NAME = 'org.w4.System1.Manager';

    private SystemApiManager $manager;
    private SystemApiDbusRuntimeProbeToolkit $runtimeProbe;

    public function __construct(string $rootDir)
    {
        $this->manager = new SystemApiManager($rootDir);
        $this->runtimeProbe = new SystemApiDbusRuntimeProbeToolkit();
    }

    public function call(string $requestJson): string
    {
        return $this->manager->call($requestJson);
    }

    public function getCapabilities(?string $requestId = null): string
    {
        return $this->manager->getCapabilities($requestId);
    }

    public function diagnoseRuntime(): string
    {
        return $this->encodeJson($this->runtimeProbe->createReport());
    }

    public function describeRuntime(): string
    {
        return $this->encodeJson([
            'api_version' => self::API_VERSION,
            'kind' => 'system-api-dbus-bridge-runtime',
            'status' => 'local-bridge-ready',
            'binding_status' => 'pending-runtime-adapter',
            'bus_name' => self::BUS_NAME,
            'object_path' => self::OBJECT_PATH,
            'interface' => self::INTERFACE_NAME,
            'commands' => [
                'Call',
                'GetCapabilities',
                'DiagnoseRuntime',
                'DescribeRuntime',
                'serve-stdio-bridge',
            ],
            'delegates_to' => [
                'call' => 'scripts/system_api_manager.php call',
                'get_capabilities' => 'scripts/system_api_manager.php get-capabilities',
                'diagnose_runtime' => 'scripts/check_system_api_dbus_runtime.php',
            ],
        ]);
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function serveStdioBridge($input, $output): void
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
            $method = $this->requireEnvelopeMethod($envelope);

            $response = match ($method) {
                'GetCapabilities' => $this->decodeResponseJson(
                    $this->getCapabilities($this->optionalEnvelopeString($envelope, 'request_id'))
                ),
                'Call' => $this->decodeResponseJson(
                    $this->call($this->resolveRequestJson($envelope))
                ),
                'DiagnoseRuntime' => $this->decodeResponseJson($this->diagnoseRuntime()),
                'DescribeRuntime' => $this->decodeResponseJson($this->describeRuntime()),
                default => $this->createProtocolError(
                    'NOT_SUPPORTED',
                    sprintf('Metodo de bridge no soportado: %s', $method)
                ),
            };
        } catch (ValidationError $exception) {
            $response = $this->createProtocolError('INVALID_ARGUMENT', $exception->getMessage());
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
            throw new ValidationError('El envelope del bridge debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function requireEnvelopeMethod(array $envelope): string
    {
        $method = $envelope['method'] ?? null;
        if (!is_string($method) || trim($method) === '') {
            throw new ValidationError('method debe ser un string no vacio');
        }

        return trim($method);
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function resolveRequestJson(array $envelope): string
    {
        $requestJson = $envelope['request_json'] ?? null;
        $request = $envelope['request'] ?? null;

        if (is_string($requestJson) && trim($requestJson) !== '') {
            return trim($requestJson);
        }

        if (is_array($request)) {
            $encoded = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new ValidationError('No se pudo serializar request dentro del envelope del bridge');
            }

            return $encoded;
        }

        throw new ValidationError('El metodo Call requiere request o request_json');
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function optionalEnvelopeString(array $envelope, string $field): ?string
    {
        $value = $envelope[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('%s debe ser un string no vacio cuando se usa', $field));
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
            throw new ValidationError(sprintf('La respuesta JSON del bridge es invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('La respuesta JSON del bridge debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function createProtocolError(string $code, string $message): array
    {
        return [
            'api_version' => self::API_VERSION,
            'request_id' => 'bridge-invalid-request',
            'result' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'retryable' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeJson(array $payload): string
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar la respuesta del bridge D-Bus');
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
            throw new ValidationError('No se pudo serializar la respuesta NDJSON del bridge D-Bus');
        }

        return $encoded . PHP_EOL;
    }
}
