<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeAdapter
{
    private SystemApiDbusBridge $bridge;
    private SystemApiDbusRuntimeActivationToolkit $activationToolkit;

    public function __construct(string $rootDir)
    {
        $this->bridge = new SystemApiDbusBridge($rootDir);
        $this->activationToolkit = new SystemApiDbusRuntimeActivationToolkit();
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
        return $this->bridge->describeRuntime();
    }

    public function serveDbus(): string
    {
        return $this->bridge->serveDbus();
    }

    public function describeSpecification(): string
    {
        return $this->encodeJson($this->activationToolkit->createAdapterSpecification());
    }

    public function invoke(string $method, array $arguments = []): string
    {
        return match ($method) {
            'Call' => $this->call($this->resolveRequestJson($arguments)),
            'GetCapabilities' => $this->getCapabilities($this->optionalStringArgument($arguments, 'request_id')),
            'DiagnoseRuntime' => $this->diagnoseRuntime(),
            'DescribeRuntime' => $this->describeRuntime(),
            'DescribeSpecification' => $this->describeSpecification(),
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
