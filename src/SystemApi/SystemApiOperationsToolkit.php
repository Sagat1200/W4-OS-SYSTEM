<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiOperationsToolkit
{
    private string $storeDir;

    public function __construct(string $rootDir)
    {
        $this->storeDir = rtrim($rootDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'system-api'
            . DIRECTORY_SEPARATOR . 'operations';
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function record(string $method, string $resourceKind, string $resourceId, array $operation): array
    {
        $operationId = $operation['operation_id'] ?? null;
        if (!is_string($operationId) || trim($operationId) === '') {
            throw new ValidationError('Operacion invalida: operation_id ausente');
        }

        $state = $this->normalizeState($operation['state'] ?? null);
        $observedAt = gmdate('c');
        $storedOperation = [
            'kind' => 'system-api-operation',
            'operation_id' => trim($operationId),
            'method' => $method,
            'resource' => [
                'kind' => $resourceKind,
                'id' => $resourceId,
            ],
            'state' => $state,
            'status' => is_string($operation['status'] ?? null) ? $operation['status'] : ($state === 'succeeded' ? 'ok' : 'failed'),
            'cancel_requested' => false,
            'cancelable' => false,
            'progress' => [
                'percent' => $this->resolveProgressPercent($state),
                'current_step' => is_string($operation['next_action'] ?? null) ? $operation['next_action'] : 'completed',
            ],
            'result' => $operation,
            'error' => $state === 'failed'
                ? [
                    'code' => 'OPERATION_FAILED',
                    'message' => $this->resolveFailureMessage($operation),
                ]
                : null,
            'created_at' => $observedAt,
            'observed_at' => $observedAt,
            'completed_at' => in_array($state, ['succeeded', 'failed', 'partial', 'unknown', 'cancelled'], true)
                ? $observedAt
                : null,
        ];

        $this->writeJsonFile($this->resolveOperationPath($storedOperation['operation_id']), $storedOperation);

        return $storedOperation;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $operationId): array
    {
        $path = $this->resolveOperationPath($operationId);
        $raw = file_get_contents($path);
        if (!is_string($raw)) {
            throw new ValidationError(sprintf('Operacion no encontrada: %s', $operationId));
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('Operacion invalida: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('Operacion invalida: %s', $operationId));
        }

        return $decoded;
    }

    private function resolveOperationPath(string $operationId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($operationId));
        if (!is_string($sanitized) || $sanitized === '') {
            throw new ValidationError('operation_id invalido');
        }

        return $this->storeDir . DIRECTORY_SEPARATOR . $sanitized . '.json';
    }

    private function normalizeState(mixed $state): string
    {
        if (!is_string($state)) {
            return 'unknown';
        }

        $normalized = trim($state);
        if (in_array($normalized, ['queued', 'running', 'succeeded', 'failed', 'partial', 'unknown', 'cancelled', 'needs_attention'], true)) {
            return $normalized;
        }

        return 'unknown';
    }

    private function resolveProgressPercent(string $state): int
    {
        return match ($state) {
            'queued' => 5,
            'running', 'needs_attention' => 50,
            'succeeded', 'failed', 'partial', 'unknown', 'cancelled' => 100,
            default => 0,
        };
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function resolveFailureMessage(array $operation): string
    {
        $stderr = $operation['stderr'] ?? null;
        if (is_string($stderr) && trim($stderr) !== '') {
            return trim($stderr);
        }

        $nextAction = $operation['next_action'] ?? null;
        if (is_string($nextAction) && trim($nextAction) !== '') {
            return sprintf('La operacion requiere %s', trim($nextAction));
        }

        return 'La operacion termino en estado failed';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonFile(string $path, array $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo preparar el store de operaciones: %s', $directory));
        }

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError(sprintf('No se pudo serializar la operacion %s', basename($path)));
        }

        if (file_put_contents($path, $encoded . PHP_EOL) === false) {
            throw new ValidationError(sprintf('No se pudo persistir la operacion %s', basename($path)));
        }
    }
}
