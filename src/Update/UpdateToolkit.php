<?php

declare(strict_types=1);

namespace W4\OS\Update;

use JsonException;
use W4\OS\Support\ValidationError;

final class UpdateToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function readJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ValidationError(sprintf('JSON invalido en %s: %s', $path, $exception->getMessage()));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $request
     */
    public function validateUpdateRequest(array $request, string $sourcePath): void
    {
        if (($request['update_request_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: update_request_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($request['kind'] ?? null) !== 'update-request') {
            throw new ValidationError(sprintf('%s: kind debe ser update-request', basename($sourcePath)));
        }

        foreach (['profile_id', 'source_version', 'target_version'] as $field) {
            $this->requireNonEmptyString($request, $field, $request[$field] ?? null, $sourcePath);
        }

        if ($request['source_version'] === $request['target_version']) {
            throw new ValidationError(sprintf('%s: source_version y target_version deben ser distintos', basename($sourcePath)));
        }

        /** @var array<string, mixed> $repositorySnapshot */
        $repositorySnapshot = $this->requireAssocArray(
            $request,
            'repository_snapshot',
            $request['repository_snapshot'] ?? null,
            $sourcePath
        );
        $this->requireNonEmptyString($repositorySnapshot, 'repository_snapshot.id', $repositorySnapshot['id'] ?? null, $sourcePath);
        $this->requireNonEmptyString($repositorySnapshot, 'repository_snapshot.channel', $repositorySnapshot['channel'] ?? null, $sourcePath);

        /** @var array<string, mixed> $packageChanges */
        $packageChanges = $this->requireAssocArray(
            $request,
            'package_changes',
            $request['package_changes'] ?? null,
            $sourcePath
        );

        $install = $this->ensureStringList($request, 'package_changes.install', $packageChanges['install'] ?? [], $sourcePath);
        $upgrade = $this->ensureStringList($request, 'package_changes.upgrade', $packageChanges['upgrade'] ?? [], $sourcePath);
        $remove = $this->ensureStringList($request, 'package_changes.remove', $packageChanges['remove'] ?? [], $sourcePath);

        if ($install === [] && $upgrade === [] && $remove === []) {
            throw new ValidationError(sprintf('%s: package_changes debe contener al menos una accion', basename($sourcePath)));
        }

        /** @var array<string, mixed> $operation */
        $operation = $this->requireAssocArray(
            $request,
            'operation',
            $request['operation'] ?? null,
            $sourcePath
        );

        if (($operation['mode'] ?? null) !== 'apt-offline-snapshot') {
            throw new ValidationError(sprintf('%s: operation.mode debe ser apt-offline-snapshot', basename($sourcePath)));
        }

        if (($operation['reboot_required'] ?? null) !== true) {
            throw new ValidationError(sprintf('%s: operation.reboot_required debe ser true', basename($sourcePath)));
        }

        $this->requireNonEmptyString($operation, 'operation.snapshot_root', $operation['snapshot_root'] ?? null, $sourcePath);
        $this->requireNonEmptyString($operation, 'operation.test_file_path', $operation['test_file_path'] ?? null, $sourcePath);

        /** @var array<string, mixed> $bootManifest */
        $bootManifest = $this->requireAssocArray(
            $request,
            'boot_manifest',
            $request['boot_manifest'] ?? null,
            $sourcePath
        );
        $this->requireNonEmptyString($bootManifest, 'boot_manifest.kernel_package', $bootManifest['kernel_package'] ?? null, $sourcePath);
        $this->requireNonEmptyString($bootManifest, 'boot_manifest.boot_mode', $bootManifest['boot_mode'] ?? null, $sourcePath);
        $this->requireNonEmptyString($bootManifest, 'boot_manifest.initramfs_strategy', $bootManifest['initramfs_strategy'] ?? null, $sourcePath);

        $healthChecks = $this->ensureStringList($request, 'health_checks', $request['health_checks'] ?? [], $sourcePath);
        if ($healthChecks === []) {
            throw new ValidationError(sprintf('%s: health_checks debe contener al menos una verificacion', basename($sourcePath)));
        }
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function validateUpdatePlan(array $plan, string $sourcePath): void
    {
        if (($plan['update_plan_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: update_plan_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($plan['kind'] ?? null) !== 'update-plan') {
            throw new ValidationError(sprintf('%s: kind debe ser update-plan', basename($sourcePath)));
        }

        foreach (['operation_id', 'profile_id', 'source_version', 'target_version'] as $field) {
            $this->requireNonEmptyString($plan, $field, $plan[$field] ?? null, $sourcePath);
        }

        /** @var array<string, mixed> $execution */
        $execution = $this->requireAssocArray($plan, 'execution', $plan['execution'] ?? null, $sourcePath);
        $stages = $this->ensureStringList($plan, 'execution.stages', $execution['stages'] ?? [], $sourcePath);
        if ($stages === [] || $stages[0] !== 'planned') {
            throw new ValidationError(sprintf('%s: execution.stages debe iniciar en planned', basename($sourcePath)));
        }

        /** @var array<string, mixed> $snapshot */
        $snapshot = $this->requireAssocArray($plan, 'snapshot', $plan['snapshot'] ?? null, $sourcePath);
        foreach (['root_subvolume', 'snapshot_name', 'snapshot_path', 'retention'] as $field) {
            $this->requireNonEmptyString($snapshot, sprintf('snapshot.%s', $field), $snapshot[$field] ?? null, $sourcePath);
        }

        /** @var array<string, mixed> $bootManifest */
        $bootManifest = $this->requireAssocArray($plan, 'boot_manifest', $plan['boot_manifest'] ?? null, $sourcePath);
        foreach (['kernel_package', 'boot_mode', 'initramfs_strategy'] as $field) {
            $this->requireNonEmptyString($bootManifest, sprintf('boot_manifest.%s', $field), $bootManifest[$field] ?? null, $sourcePath);
        }
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function createUpdatePlan(array $request, ?string $operationId = null): array
    {
        $operationId ??= $this->generateOperationId();

        /** @var array<string, mixed> $repositorySnapshot */
        $repositorySnapshot = $request['repository_snapshot'];
        /** @var array<string, mixed> $packageChanges */
        $packageChanges = $request['package_changes'];
        /** @var array<string, mixed> $operation */
        $operation = $request['operation'];
        /** @var array<string, mixed> $bootManifest */
        $bootManifest = $request['boot_manifest'];

        /** @var list<string> $install */
        $install = array_values($packageChanges['install']);
        /** @var list<string> $upgrade */
        $upgrade = array_values($packageChanges['upgrade']);
        /** @var list<string> $remove */
        $remove = array_values($packageChanges['remove']);
        /** @var list<string> $healthChecks */
        $healthChecks = array_values($request['health_checks']);

        $snapshotName = sprintf('pre-update-%s', $operationId);

        return [
            'update_plan_schema_version' => 1,
            'kind' => 'update-plan',
            'operation_id' => $operationId,
            'profile_id' => $request['profile_id'],
            'source_version' => $request['source_version'],
            'target_version' => $request['target_version'],
            'repository_snapshot' => [
                'id' => $repositorySnapshot['id'],
                'channel' => $repositorySnapshot['channel'],
            ],
            'package_changes' => [
                'install' => $install,
                'upgrade' => $upgrade,
                'remove' => $remove,
                'counts' => [
                    'install' => count($install),
                    'upgrade' => count($upgrade),
                    'remove' => count($remove),
                    'total' => count($install) + count($upgrade) + count($remove),
                ],
            ],
            'execution' => [
                'engine' => $operation['mode'],
                'reboot_required' => $operation['reboot_required'],
                'stages' => [
                    'planned',
                    'downloading',
                    'ready',
                    'prepared',
                    'applying_offline',
                    'pending_health',
                    'confirmed',
                ],
                'failure_state' => 'failed',
            ],
            'snapshot' => [
                'root_subvolume' => $operation['snapshot_root'],
                'snapshot_name' => $snapshotName,
                'snapshot_path' => '/.snapshots/' . $snapshotName,
                'retention' => 'protected-until-confirmed',
            ],
            'boot_manifest' => [
                'kernel_package' => $bootManifest['kernel_package'],
                'boot_mode' => $bootManifest['boot_mode'],
                'initramfs_strategy' => $bootManifest['initramfs_strategy'],
            ],
            'health_check_manifest' => [
                'required' => $healthChecks,
                'test_file_path' => $operation['test_file_path'],
                'pending_state' => 'pending_health',
                'success_state' => 'confirmed',
                'failure_state' => 'failed',
            ],
            'durable_state_template' => [
                'update_operation_schema_version' => 1,
                'kind' => 'update-operation',
                'operation_id' => $operationId,
                'stage' => 'planned',
                'last_error' => null,
            ],
            'evidence' => [
                'update-plan.json',
                'operation.json',
                'events.ndjson',
                'health-report.json',
            ],
            'notes' => [
                'El almacenamiento durable debe vivir fuera del estado revertible por snapshot.',
                'El snapshot previo no promete revertir firmware ni datos externos al alcance documentado.',
                'Una sola operacion mutante puede estar activa a la vez.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    public function initializeOperationStore(array $plan, string $storeDir): array
    {
        $this->ensureDirectory($storeDir);

        $operation = [
            'update_operation_schema_version' => 1,
            'kind' => 'update-operation',
            'operation_id' => $plan['operation_id'],
            'profile_id' => $plan['profile_id'],
            'source_version' => $plan['source_version'],
            'target_version' => $plan['target_version'],
            'stage' => 'planned',
            'repository_snapshot' => $plan['repository_snapshot'],
            'snapshot' => $plan['snapshot'],
            'boot_manifest' => $plan['boot_manifest'],
            'last_error' => null,
            'timestamps' => [
                'created_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
            ],
        ];

        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'update-plan.json', $plan);
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'operation.json', $operation);
        $this->writeJsonFile(
            $storeDir . DIRECTORY_SEPARATOR . 'health-report.json',
            [
                'health_report_schema_version' => 1,
                'kind' => 'health-report',
                'operation_id' => $plan['operation_id'],
                'stage' => 'pending_health',
                'status' => 'pending',
                'required_checks' => $plan['health_check_manifest']['required'],
                'test_file_path' => $plan['health_check_manifest']['test_file_path'],
            ]
        );

        $this->appendEvent(
            $storeDir . DIRECTORY_SEPARATOR . 'events.ndjson',
            [
                'event_schema_version' => 1,
                'event_type' => 'operation-created',
                'operation_id' => $plan['operation_id'],
                'stage' => 'planned',
                'component' => 'update-engine',
                'outcome' => 'ok',
                'details' => [
                    'profile_id' => $plan['profile_id'],
                    'target_version' => $plan['target_version'],
                ],
            ]
        );

        return [
            'store_dir' => $storeDir,
            'operation' => $operation,
        ];
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<string, mixed>|null $lastError
     * @return array<string, mixed>
     */
    public function transitionOperation(array $operation, string $nextStage, ?array $lastError = null): array
    {
        $currentStage = $operation['stage'] ?? null;
        if (!is_string($currentStage) || $currentStage === '') {
            throw new ValidationError('operation.stage debe ser un string no vacio');
        }

        $allowedTransitions = [
            'planned' => ['downloading', 'failed'],
            'downloading' => ['ready', 'failed'],
            'ready' => ['prepared', 'failed'],
            'prepared' => ['applying_offline', 'failed'],
            'applying_offline' => ['pending_health', 'failed'],
            'pending_health' => ['confirmed', 'failed'],
            'confirmed' => [],
            'failed' => [],
        ];

        $allowedNextStages = $allowedTransitions[$currentStage] ?? null;
        if ($allowedNextStages === null) {
            throw new ValidationError(sprintf('Estado actual no soportado: %s', $currentStage));
        }

        if (!in_array($nextStage, $allowedNextStages, true)) {
            throw new ValidationError(sprintf(
                'Transicion no valida desde %s hacia %s',
                $currentStage,
                $nextStage
            ));
        }

        $operation['stage'] = $nextStage;
        $operation['last_error'] = $nextStage === 'failed' ? $lastError : null;
        /** @var array<string, mixed> $timestamps */
        $timestamps = is_array($operation['timestamps'] ?? null) ? $operation['timestamps'] : [];
        $timestamps['updated_at'] = gmdate('c');
        $operation['timestamps'] = $timestamps;

        return $operation;
    }

    public function generateOperationId(): string
    {
        return sprintf(
            'w4-update-%s-%s',
            gmdate('YmdHis'),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJsonFile(string $path, array $data): void
    {
        $directory = dirname($path);
        $this->ensureDirectory($directory);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new ValidationError(sprintf('No se pudo serializar el archivo JSON: %s', $path));
        }

        $written = file_put_contents($path, $json . PHP_EOL);
        if ($written === false) {
            throw new ValidationError(sprintf('No se pudo escribir el archivo: %s', $path));
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private function appendEvent(string $path, array $event): void
    {
        $event['timestamp'] = gmdate('c');
        $json = json_encode($event, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new ValidationError(sprintf('No se pudo serializar el evento en %s', $path));
        }

        $written = file_put_contents($path, $json . PHP_EOL, FILE_APPEND);
        if ($written === false) {
            throw new ValidationError(sprintf('No se pudo escribir el log de eventos: %s', $path));
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta: %s', $path));
        }
    }

    /**
     * @param array<string, mixed> $parent
     * @return array<string, mixed>
     */
    private function requireAssocArray(array $parent, string $fieldName, mixed $value, string $sourcePath): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf('%s: %s debe ser un objeto', basename($sourcePath), $fieldName));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function requireNonEmptyString(array $parent, string $fieldName, mixed $value, string $sourcePath): void
    {
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: %s debe ser un string no vacio', basename($sourcePath), $fieldName));
        }
    }

    /**
     * @param array<string, mixed> $parent
     * @param mixed $value
     * @return list<string>
     */
    private function ensureStringList(array $parent, string $fieldName, mixed $value, string $sourcePath): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf('%s: %s debe ser una lista de strings no vacios', basename($sourcePath), $fieldName));
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new ValidationError(sprintf('%s: %s debe ser una lista de strings no vacios', basename($sourcePath), $fieldName));
            }

            $result[] = $item;
        }

        return array_values(array_unique($result));
    }
}
