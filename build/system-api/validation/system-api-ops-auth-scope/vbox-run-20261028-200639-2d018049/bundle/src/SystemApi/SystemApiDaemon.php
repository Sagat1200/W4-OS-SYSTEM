<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDaemon
{
    private SystemApiManager $manager;

    public function __construct(string $rootDir)
    {
        $this->manager = new SystemApiManager($rootDir);
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function serveStdio($input, $output): void
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
            $command = $this->requireEnvelopeCommand($envelope);

            $response = match ($command) {
                'get-capabilities' => $this->decodeResponseJson(
                    $this->manager->getCapabilities($this->optionalEnvelopeString($envelope, 'request_id'))
                ),
                'call' => $this->decodeResponseJson(
                    $this->manager->call($this->resolveRequestJson($envelope))
                ),
                default => $this->createProtocolError(
                    'NOT_SUPPORTED',
                    sprintf('Comando de daemon no soportado: %s', $command)
                ),
            };
        } catch (ValidationError $exception) {
            $response = $this->createProtocolError('INVALID_ARGUMENT', $exception->getMessage());
        }

        return $this->encodeEnvelope($response);
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
            throw new ValidationError('El envelope del daemon debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function requireEnvelopeCommand(array $envelope): string
    {
        $command = $envelope['command'] ?? null;
        if (!is_string($command) || trim($command) === '') {
            throw new ValidationError('command debe ser un string no vacio');
        }

        return trim($command);
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
                throw new ValidationError('No se pudo serializar request dentro del envelope');
            }

            return $encoded;
        }

        throw new ValidationError('El comando call requiere request o request_json');
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
            throw new ValidationError(sprintf('La respuesta JSON del manager es invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError('La respuesta JSON del manager debe decodificar a un objeto');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function createProtocolError(string $code, string $message): array
    {
        return [
            'api_version' => '1.0',
            'request_id' => 'daemon-invalid-request',
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
    private function encodeEnvelope(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar la respuesta del daemon');
        }

        return $encoded . PHP_EOL;
    }
}
