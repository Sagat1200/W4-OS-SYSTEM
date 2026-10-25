<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiOperationsToolkit
{
    private string $storeDir;
    private string $ledgerDir;

    public function __construct(string $rootDir)
    {
        $this->storeDir = rtrim($rootDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'system-api'
            . DIRECTORY_SEPARATOR . 'operations';
        $this->ledgerDir = rtrim($rootDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'system-api'
            . DIRECTORY_SEPARATOR . 'operations-ledger';
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
        if (!is_file($path)) {
            throw new ValidationError(sprintf('Operacion no encontrada: %s', $operationId));
        }

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

    /**
     * @return array<string, mixed>|null
     */
    public function reuseIdempotentOperation(string $method, string $idempotencyKey, string $digest): ?array
    {
        $ledgerPath = $this->resolveLedgerPath($method, $idempotencyKey);
        if (!is_file($ledgerPath)) {
            return null;
        }

        $raw = file_get_contents($ledgerPath);
        if (!is_string($raw)) {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('Ledger invalido: %s', $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('Ledger invalido: %s', $ledgerPath));
        }

        $storedDigest = $decoded['digest'] ?? null;
        $storedOperationId = $decoded['operation_id'] ?? null;
        if (!is_string($storedDigest) || trim($storedDigest) === '' || !is_string($storedOperationId) || trim($storedOperationId) === '') {
            throw new ValidationError(sprintf('Ledger invalido: %s', $ledgerPath));
        }

        if (trim($storedDigest) !== $digest) {
            throw new ValidationError(sprintf('Conflicto: idempotency_key ya fue usada para %s con otro digest', $method));
        }

        return $this->get(trim($storedOperationId));
    }

    public function rememberIdempotentOperation(
        string $method,
        string $idempotencyKey,
        string $digest,
        string $operationId
    ): void {
        $ledgerPath = $this->resolveLedgerPath($method, $idempotencyKey);
        $payload = [
            'kind' => 'system-api-idempotency-ledger-entry',
            'method' => $method,
            'idempotency_key' => $idempotencyKey,
            'digest' => $digest,
            'operation_id' => $operationId,
            'observed_at' => gmdate('c'),
        ];

        $this->writeJsonFile($ledgerPath, $payload);
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function refresh(string $operationId, array $operation): array
    {
        $storedOperation = $this->get($operationId);
        $state = $this->normalizeState($operation['state'] ?? null);
        $observedAt = gmdate('c');

        $storedOperation['state'] = $state;
        $storedOperation['status'] = is_string($operation['status'] ?? null)
            ? $operation['status']
            : ($state === 'succeeded' ? 'ok' : 'failed');
        $storedOperation['progress'] = [
            'percent' => $this->resolveProgressPercent($state),
            'current_step' => is_string($operation['next_action'] ?? null) ? $operation['next_action'] : 'completed',
        ];
        $storedOperation['result'] = $operation;
        $storedOperation['error'] = $state === 'failed'
            ? [
                'code' => 'OPERATION_FAILED',
                'message' => $this->resolveFailureMessage($operation),
            ]
            : null;
        $storedOperation['observed_at'] = $observedAt;
        $storedOperation['completed_at'] = in_array($state, ['succeeded', 'failed', 'partial', 'unknown', 'cancelled'], true)
            ? $observedAt
            : null;

        $this->writeJsonFile($this->resolveOperationPath($operationId), $storedOperation);

        return $storedOperation;
    }

    public function acquireExclusiveLock(string $scope, string $operationId): void
    {
        $lockPath = $this->resolveExclusiveLockPath($scope);
        if (is_file($lockPath)) {
            $raw = file_get_contents($lockPath);
            if (is_string($raw) && trim($raw) !== '') {
                try {
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new ValidationError(sprintf('Conflicto: lock exclusivo invalido para %s (%s)', $scope, $exception->getMessage()));
                }

                if (is_array($decoded)) {
                    $lockedOperationId = $decoded['operation_id'] ?? null;
                    if (is_string($lockedOperationId) && trim($lockedOperationId) !== '' && trim($lockedOperationId) !== trim($operationId)) {
                        throw new ValidationError(sprintf(
                            'Conflicto: %s ya tiene una mutacion activa (operation_id=%s)',
                            $scope,
                            trim($lockedOperationId)
                        ));
                    }
                }
            }
        }

        $this->writeJsonFile($lockPath, [
            'kind' => 'system-api-exclusive-lock',
            'scope' => $scope,
            'operation_id' => trim($operationId),
            'observed_at' => gmdate('c'),
        ]);
    }

    public function releaseExclusiveLock(string $scope, string $operationId): void
    {
        $lockPath = $this->resolveExclusiveLockPath($scope);
        if (!is_file($lockPath)) {
            return;
        }

        $raw = file_get_contents($lockPath);
        if (!is_string($raw) || trim($raw) === '') {
            @unlink($lockPath);

            return;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            @unlink($lockPath);

            return;
        }

        $lockedOperationId = is_array($decoded) ? ($decoded['operation_id'] ?? null) : null;
        if (!is_string($lockedOperationId) || trim($lockedOperationId) === '' || trim($lockedOperationId) === trim($operationId)) {
            @unlink($lockPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $operationId): array
    {
        $operation = $this->get($operationId);
        $state = is_string($operation['state'] ?? null) ? trim((string) $operation['state']) : 'unknown';
        $cancelable = ($operation['cancelable'] ?? false) === true;
        $cancelRequested = $cancelable && !in_array($state, ['succeeded', 'failed', 'partial', 'unknown', 'cancelled'], true);

        $operation['cancel_requested'] = $cancelRequested;
        $operation['observed_at'] = gmdate('c');

        if ($cancelRequested) {
            $this->writeJsonFile($this->resolveOperationPath($operationId), $operation);
        }

        return [
            'kind' => 'system-api-operation-cancel',
            'operation_id' => $operationId,
            'cancel_requested' => $cancelRequested,
            'cancelable' => $cancelable,
            'state' => $state,
            'status' => $cancelRequested ? 'cancel-requested' : 'not-cancelable',
            'message' => $cancelRequested
                ? 'Se registro la solicitud de cancelacion'
                : 'La operacion no es cancelable de forma segura',
        ];
    }

    public function buildIdempotencyDigest(string $method, array $payload): string
    {
        $encoded = json_encode(
            [
                'method' => $method,
                'payload' => $payload,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($encoded === false) {
            throw new ValidationError(sprintf('No se pudo serializar el digest idempotente para %s', $method));
        }

        return sha1($encoded);
    }

    private function resolveOperationPath(string $operationId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($operationId));
        if (!is_string($sanitized) || $sanitized === '') {
            throw new ValidationError('operation_id invalido');
        }

        return $this->storeDir . DIRECTORY_SEPARATOR . $sanitized . '.json';
    }

    private function resolveLedgerPath(string $method, string $idempotencyKey): string
    {
        $methodToken = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($method));
        $keyToken = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($idempotencyKey));
        if (!is_string($methodToken) || $methodToken === '' || !is_string($keyToken) || $keyToken === '') {
            throw new ValidationError('idempotency_key invalida');
        }

        return $this->ledgerDir . DIRECTORY_SEPARATOR . $methodToken . '--' . $keyToken . '.json';
    }

    private function resolveExclusiveLockPath(string $scope): string
    {
        $scopeToken = preg_replace('/[^A-Za-z0-9._-]/', '-', trim($scope));
        if (!is_string($scopeToken) || $scopeToken === '') {
            throw new ValidationError('scope de lock invalido');
        }

        return $this->ledgerDir . DIRECTORY_SEPARATOR . 'lock--' . $scopeToken . '.json';
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
