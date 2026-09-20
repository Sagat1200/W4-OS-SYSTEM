<?php

declare(strict_types=1);

namespace W4\OS\Update;

use JsonException;
use W4\OS\Support\ValidationError;

final class UpdateToolkit
{
    private const OPERATION_STAGES = [
        'planned',
        'downloading',
        'ready',
        'prepared',
        'applying_offline',
        'pending_health',
        'confirmed',
        'failed',
    ];

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
        foreach ($stages as $stage) {
            if (!in_array($stage, self::OPERATION_STAGES, true)) {
                throw new ValidationError(sprintf('%s: execution.stages contiene un estado no soportado: %s', basename($sourcePath), $stage));
            }
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

        /** @var array<string, mixed> $staging */
        $staging = $this->requireAssocArray($plan, 'staging', $plan['staging'] ?? null, $sourcePath);
        $this->requireNonEmptyString($staging, 'staging.directory', $staging['directory'] ?? null, $sourcePath);
        if (!is_int($staging['estimated_space_kib'] ?? null) || (int) $staging['estimated_space_kib'] <= 0) {
            throw new ValidationError(sprintf('%s: staging.estimated_space_kib debe ser un entero positivo', basename($sourcePath)));
        }
    }

    /**
     * @param array<string, mixed> $operation
     */
    public function validateOperation(array $operation, string $sourcePath): void
    {
        if (($operation['update_operation_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: update_operation_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($operation['kind'] ?? null) !== 'update-operation') {
            throw new ValidationError(sprintf('%s: kind debe ser update-operation', basename($sourcePath)));
        }

        foreach (['operation_id', 'profile_id', 'source_version', 'target_version', 'stage'] as $field) {
            $this->requireNonEmptyString($operation, $field, $operation[$field] ?? null, $sourcePath);
        }

        $stage = (string) $operation['stage'];
        if (!in_array($stage, self::OPERATION_STAGES, true)) {
            throw new ValidationError(sprintf('%s: stage no soportado: %s', basename($sourcePath), $stage));
        }

        $this->requireAssocArray($operation, 'snapshot', $operation['snapshot'] ?? null, $sourcePath);
        $this->requireAssocArray($operation, 'boot_manifest', $operation['boot_manifest'] ?? null, $sourcePath);
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
        $totalPackageActions = count($install) + count($upgrade) + count($remove);
        $estimatedSpaceKib = max(262144, $totalPackageActions * 131072);

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
                'apply_mode' => 'live-apt-maintenance-window',
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
            'staging' => [
                'directory' => '/var/lib/w4-update/staging/' . $operationId,
                'estimated_space_kib' => $estimatedSpaceKib,
                'cache_policy' => 'protected-until-confirmed',
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
                'snapshot-manifest.json',
                'staging-manifest.json',
                'health-check-results.json',
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
     * @return array{plan: array<string, mixed>, operation: array<string, mixed>, healthReport: array<string, mixed>, storeDir: string}
     */
    public function loadOperationStore(string $storeDir): array
    {
        $planPath = $storeDir . DIRECTORY_SEPARATOR . 'update-plan.json';
        $operationPath = $storeDir . DIRECTORY_SEPARATOR . 'operation.json';
        $healthReportPath = $storeDir . DIRECTORY_SEPARATOR . 'health-report.json';

        $plan = $this->readJsonFile($planPath);
        $this->validateUpdatePlan($plan, $planPath);

        $operation = $this->readJsonFile($operationPath);
        $this->validateOperation($operation, $operationPath);

        $healthReport = $this->readJsonFile($healthReportPath);

        return [
            'plan' => $plan,
            'operation' => $operation,
            'healthReport' => $healthReport,
            'storeDir' => $storeDir,
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed>|null $lastError
     * @return array<string, mixed>
     */
    public function persistOperationTransition(
        string $storeDir,
        string $nextStage,
        string $component,
        array $details = [],
        ?array $lastError = null
    ): array {
        $store = $this->loadOperationStore($storeDir);
        $plan = $store['plan'];
        $operation = $store['operation'];

        $updatedOperation = $this->transitionOperation($operation, $nextStage, $lastError);

        $operationPath = $storeDir . DIRECTORY_SEPARATOR . 'operation.json';
        $eventsPath = $storeDir . DIRECTORY_SEPARATOR . 'events.ndjson';
        $healthReportPath = $storeDir . DIRECTORY_SEPARATOR . 'health-report.json';

        $this->writeJsonFile($operationPath, $updatedOperation);
        $this->writeJsonFile($healthReportPath, $this->buildHealthReport($plan, $updatedOperation, $lastError));
        $this->appendEvent(
            $eventsPath,
            [
                'event_schema_version' => 1,
                'event_type' => 'operation-transitioned',
                'operation_id' => $updatedOperation['operation_id'],
                'stage' => $updatedOperation['stage'],
                'component' => $component,
                'outcome' => $updatedOperation['stage'] === 'failed' ? 'error' : 'ok',
                'details' => $details,
                'last_error' => $lastError,
            ]
        );

        return $updatedOperation;
    }

    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed>|null $observedError
     * @return array<string, mixed>
     */
    public function reconcileOperationStore(
        string $storeDir,
        string $observedStage,
        string $component,
        array $details = [],
        ?array $observedError = null
    ): array {
        $store = $this->loadOperationStore($storeDir);
        $plan = $store['plan'];
        $operation = $store['operation'];

        if (!in_array($observedStage, self::OPERATION_STAGES, true)) {
            throw new ValidationError(sprintf('observed_stage no soportado: %s', $observedStage));
        }

        $targetStage = $this->resolveReconciledStage((string) $operation['stage'], $observedStage);
        $lastError = $targetStage === 'failed' ? $observedError : null;

        if ($targetStage === (string) $operation['stage']) {
            $this->appendEvent(
                $storeDir . DIRECTORY_SEPARATOR . 'events.ndjson',
                [
                    'event_schema_version' => 1,
                    'event_type' => 'operation-reconciled',
                    'operation_id' => $operation['operation_id'],
                    'stage' => $operation['stage'],
                    'component' => $component,
                    'outcome' => $targetStage === 'failed' ? 'error' : 'ok',
                    'details' => array_merge(
                        $details,
                        [
                            'observed_stage' => $observedStage,
                            'reconciled' => false,
                        ]
                    ),
                    'last_error' => $lastError,
                ]
            );

            return [
                'reconciled' => false,
                'operation' => $operation,
                'health_report' => $store['healthReport'],
            ];
        }

        $updatedOperation = $this->transitionOperation($operation, $targetStage, $lastError);
        $healthReport = $this->buildHealthReport($plan, $updatedOperation, $lastError);

        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'operation.json', $updatedOperation);
        $this->writeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-report.json', $healthReport);
        $this->appendEvent(
            $storeDir . DIRECTORY_SEPARATOR . 'events.ndjson',
            [
                'event_schema_version' => 1,
                'event_type' => 'operation-reconciled',
                'operation_id' => $updatedOperation['operation_id'],
                'stage' => $updatedOperation['stage'],
                'component' => $component,
                'outcome' => $updatedOperation['stage'] === 'failed' ? 'error' : 'ok',
                'details' => array_merge(
                    $details,
                    [
                        'observed_stage' => $observedStage,
                        'reconciled' => true,
                    ]
                ),
                'last_error' => $lastError,
            ]
        );

        return [
            'reconciled' => true,
            'operation' => $updatedOperation,
            'health_report' => $healthReport,
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    public function createUpdateExecutorManifest(array $plan): array
    {
        return [
            'update_executor_schema_version' => 1,
            'kind' => 'update-executor',
            'operation_id' => $plan['operation_id'],
            'engine' => $plan['execution']['engine'],
            'generated_artifacts' => [
                'run-update-offline.sh',
                'run-health-checks.sh',
                'reconcile-after-reboot.sh',
                'update-executor.json',
                'UPDATE_EXECUTOR_README.txt',
            ],
            'failure_injection' => [
                'environment_variable' => 'W4_UPDATE_FAIL_STAGE',
                'allowed_values' => [
                    'downloading',
                    'ready',
                    'prepared',
                    'applying_offline',
                    'pending_health',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function renderOfflineExecutorScript(array $plan, string $rootDir): string
    {
        $engineRoot = $this->escapeShellDoubleQuoted($rootDir);
        $operationId = $this->escapeShellDoubleQuoted((string) $plan['operation_id']);
        $targetVersion = $this->escapeShellDoubleQuoted((string) $plan['target_version']);
        $snapshotName = $this->escapeShellDoubleQuoted((string) $plan['snapshot']['snapshot_name']);
        $snapshotPath = $this->escapeShellDoubleQuoted((string) $plan['snapshot']['snapshot_path']);
        $bootMode = $this->escapeShellDoubleQuoted((string) $plan['boot_manifest']['boot_mode']);
        $kernelPackage = $this->escapeShellDoubleQuoted((string) $plan['boot_manifest']['kernel_package']);
        $testFilePath = $this->escapeShellDoubleQuoted((string) $plan['health_check_manifest']['test_file_path']);
        $stagingDirectory = $this->escapeShellDoubleQuoted((string) $plan['staging']['directory']);
        $estimatedSpaceKib = (int) $plan['staging']['estimated_space_kib'];
        $installCount = (int) $plan['package_changes']['counts']['install'];
        $upgradeCount = (int) $plan['package_changes']['counts']['upgrade'];
        $removeCount = (int) $plan['package_changes']['counts']['remove'];

        $installList = $this->renderShellArray($plan['package_changes']['install']);
        $upgradeList = $this->renderShellArray($plan['package_changes']['upgrade']);
        $removeList = $this->renderShellArray($plan['package_changes']['remove']);
        $healthChecks = $this->renderShellArray($plan['health_check_manifest']['required']);

        return <<<BASH
#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="\$(cd -- "\$(dirname -- "\${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_ENGINE_ROOT="\$(cd -- "\${SCRIPT_DIR}/../../../.." 2>/dev/null && pwd || true)"
ENGINE_ROOT="\${W4_UPDATE_ENGINE_ROOT:-\${DEFAULT_ENGINE_ROOT}}"
STORE_DIR="\${W4_UPDATE_STORE_DIR:-\${SCRIPT_DIR}/store}"
STAGING_DIR="\${W4_UPDATE_STAGING_DIR:-${stagingDirectory}}"
SNAPSHOT_PARENT="\${W4_UPDATE_SNAPSHOT_PARENT:-/.snapshots}"
ROOT_MOUNT="\${W4_UPDATE_ROOT_MOUNT:-/}"
ROOT_SUBVOLUME="\${W4_UPDATE_ROOT_SUBVOLUME:-@}"
EXECUTE_MODE="\${W4_UPDATE_EXECUTE:-0}"
APPLY_MODE="\${W4_UPDATE_APPLY_MODE:-live-apt}"
FAIL_STAGE="\${W4_UPDATE_FAIL_STAGE:-}"
APT_SOURCE_LINE="\${W4_UPDATE_APT_SOURCE_LINE:-}"
APT_SOURCE_FILE="\${W4_UPDATE_APT_SOURCE_FILE:-}"
APT_SOURCE_TARGET=""
APT_SOURCE_INSTALLED="0"
ADVANCE_SCRIPT="\${ENGINE_ROOT}/scripts/advance_update_operation.php"
ARTIFACT_PATH="\${STORE_DIR}/offline-application.json"
SNAPSHOT_MANIFEST_PATH="\${STORE_DIR}/snapshot-manifest.json"
STAGING_MANIFEST_PATH="\${STORE_DIR}/staging-manifest.json"
HEALTH_CHECK_PATH="\${STORE_DIR}/health-checks.required.txt"

OPERATION_ID="${operationId}"
TARGET_VERSION="${targetVersion}"
SNAPSHOT_NAME="${snapshotName}"
SNAPSHOT_PATH="${snapshotPath}"
BOOT_MODE="${bootMode}"
KERNEL_PACKAGE="${kernelPackage}"
TEST_FILE_PATH="${testFilePath}"
REQUIRED_SPACE_KIB="${estimatedSpaceKib}"
CURRENT_STAGE="planned"
FAILURE_PERSISTED="0"
APT_SOURCE_TARGET="/etc/apt/sources.list.d/w4-update-\${OPERATION_ID}.list"

declare -a INSTALL_PACKAGES=(${installList})
declare -a UPGRADE_PACKAGES=(${upgradeList})
declare -a REMOVE_PACKAGES=(${removeList})
declare -a REQUIRED_HEALTH_CHECKS=(${healthChecks})

log() {
  echo "[w4-update] \$*" >&2
}

die() {
  log "ERROR: \$*"
  exit 1
}

require_command() {
  local command_name="\$1"
  command -v "\${command_name}" >/dev/null 2>&1 || die "falta el comando requerido: \${command_name}"
}

ensure_engine_scripts() {
  [[ -n "\${ENGINE_ROOT}" ]] || die "ENGINE_ROOT no resolvio una ruta valida; defina W4_UPDATE_ENGINE_ROOT"
  [[ -f "\${ADVANCE_SCRIPT}" ]] || die "no se encontro \${ADVANCE_SCRIPT}; defina W4_UPDATE_ENGINE_ROOT o ejecute dentro del repo"
}

cleanup_temporary_apt_source() {
  if [[ "\${APT_SOURCE_INSTALLED}" == "1" && -f "\${APT_SOURCE_TARGET}" ]]; then
    rm -f "\${APT_SOURCE_TARGET}"
  fi
}

configure_temporary_apt_source() {
  local source_content=""

  if [[ -n "\${APT_SOURCE_FILE}" ]]; then
    [[ -f "\${APT_SOURCE_FILE}" ]] || die "no se encontro el archivo de source APT temporal: \${APT_SOURCE_FILE}"
    source_content="\$(<"\${APT_SOURCE_FILE}")"
  elif [[ -n "\${APT_SOURCE_LINE}" ]]; then
    source_content="\${APT_SOURCE_LINE}"
  else
    return
  fi

  [[ -n "\${source_content}" ]] || die "la source APT temporal esta vacia"
  printf '%s\n' "\${source_content}" > "\${APT_SOURCE_TARGET}"
  APT_SOURCE_INSTALLED="1"
  log "Source APT temporal instalada en \${APT_SOURCE_TARGET}"
}

persist_failure() {
  local exit_code="\$1"
  local failed_stage="\${CURRENT_STAGE:-unknown}"

  if [[ "\${FAILURE_PERSISTED}" == "1" ]]; then
    return
  fi

  FAILURE_PERSISTED="1"

  if [[ ! -f "\${STORE_DIR}/operation.json" ]]; then
    log "No se pudo persistir el fallo porque falta \${STORE_DIR}/operation.json"
    return
  fi

  php "\${ADVANCE_SCRIPT}" \
    --store-dir "\${STORE_DIR}" \
    --stage failed \
    --component "update-offline-executor" \
    --error-code "command-failed" \
    --error-message "La etapa \${failed_stage} fallo con exit_code=\${exit_code}" \
    --detail "failed_stage=\${failed_stage}" \
    --detail "exit_code=\${exit_code}" \
    || true
}

handle_error() {
  local exit_code="\$1"
  local line_number="\$2"
  log "La etapa \${CURRENT_STAGE} fallo en la linea \${line_number} con exit_code=\${exit_code}"
  persist_failure "\${exit_code}"
  exit "\${exit_code}"
}

advance_stage() {
  local next_stage="\$1"
  CURRENT_STAGE="\${next_stage}"
  php "\${ADVANCE_SCRIPT}" --store-dir "\${STORE_DIR}" --stage "\${next_stage}" --component "update-offline-executor"
}

fail_stage() {
  local failed_stage="\$1"
  php "\${ADVANCE_SCRIPT}" --store-dir "\${STORE_DIR}" --stage "failed" --component "update-offline-executor" --error-code "injected-failure" --error-message "Fallo inyectado en la etapa \${failed_stage}" --detail "failed_stage=\${failed_stage}"
}

maybe_fail() {
  local stage_name="\$1"
  if [[ -n "\${FAIL_STAGE}" && "\${FAIL_STAGE}" == "\${stage_name}" ]]; then
    log "Inyectando fallo en \${stage_name}"
    fail_stage "\${stage_name}"
    exit 1
  fi
}

run_or_describe() {
  if [[ "\${EXECUTE_MODE}" == "1" ]]; then
    "\$@"
    return
  fi

  log "[check-only] \$(printf '%q ' "\$@")"
}

check_free_space() {
  mkdir -p "\${STAGING_DIR}"
  local available_kib
  available_kib="\$(df -Pk "\${STAGING_DIR}" | awk 'NR==2 {print \$4}')"
  [[ -n "\${available_kib}" ]] || die "no se pudo calcular el espacio libre de \${STAGING_DIR}"

  if (( available_kib < REQUIRED_SPACE_KIB )); then
    die "espacio insuficiente en staging: disponible=\${available_kib} KiB requerido=\${REQUIRED_SPACE_KIB} KiB"
  fi
}

stage_packages() {
  mkdir -p "\${STAGING_DIR}"
  cat > "\${STAGING_MANIFEST_PATH}" <<EOF
{
  "staging_manifest_schema_version": 1,
  "kind": "staging-manifest",
  "operation_id": "${operationId}",
  "staging_directory": "\${STAGING_DIR}",
  "estimated_space_kib": ${estimatedSpaceKib},
  "install_count": ${installCount},
  "upgrade_count": ${upgradeCount},
  "remove_count": ${removeCount}
}
EOF

  if [[ "\${EXECUTE_MODE}" != "1" ]]; then
    if [[ -n "\${APT_SOURCE_LINE}" || -n "\${APT_SOURCE_FILE}" ]]; then
      log "Modo check-only: se detecto una source APT temporal pero no se aplicara"
    fi
    log "Modo check-only: se omite apt-get update y descarga de paquetes"
    return
  fi

  require_command apt-get
  export DEBIAN_FRONTEND=noninteractive
  configure_temporary_apt_source
  apt-get update

  local packages_to_stage=()
  packages_to_stage+=("\${INSTALL_PACKAGES[@]}")
  packages_to_stage+=("\${UPGRADE_PACKAGES[@]}")
  if (( \${#packages_to_stage[@]} > 0 )); then
    apt-get -o Dir::Cache::Archives="\${STAGING_DIR}" -y --download-only install "\${packages_to_stage[@]}"
  fi
}

create_snapshot() {
  mkdir -p "\${STORE_DIR}"

  if [[ "\${EXECUTE_MODE}" == "1" ]]; then
    if command -v snapper >/dev/null 2>&1; then
      snapper --no-dbus create --type single --description "W4 update \${OPERATION_ID}" --userdata "operation_id=\${OPERATION_ID}"
    elif command -v btrfs >/dev/null 2>&1; then
      mkdir -p "\${SNAPSHOT_PARENT}"
      btrfs subvolume snapshot -r "\${ROOT_MOUNT}" "\${SNAPSHOT_PARENT}/\${SNAPSHOT_NAME}"
    else
      die "no se encontro snapper ni btrfs para crear snapshot"
    fi
  else
    if ! command -v snapper >/dev/null 2>&1 && ! command -v btrfs >/dev/null 2>&1; then
      log "Modo check-only: no se detecto snapper ni btrfs; se conserva la advertencia para laboratorio"
    fi
  fi

  cat > "\${SNAPSHOT_MANIFEST_PATH}" <<EOF
{
  "snapshot_manifest_schema_version": 1,
  "kind": "snapshot-manifest",
  "operation_id": "${operationId}",
  "snapshot_name": "${snapshotName}",
  "snapshot_path": "\${SNAPSHOT_PARENT}/\${SNAPSHOT_NAME}",
  "root_mount": "\${ROOT_MOUNT}",
  "root_subvolume": "\${ROOT_SUBVOLUME}"
}
EOF
}

apply_packages() {
  if [[ "\${EXECUTE_MODE}" != "1" ]]; then
    log "Modo check-only: se omite aplicacion real de paquetes"
    return
  fi

  require_command apt-get
  export DEBIAN_FRONTEND=noninteractive

  if [[ "\${APPLY_MODE}" != "live-apt" ]]; then
    die "APPLY_MODE no soportado por esta etapa: \${APPLY_MODE}"
  fi

  local packages_to_install=()
  packages_to_install+=("\${INSTALL_PACKAGES[@]}")
  packages_to_install+=("\${UPGRADE_PACKAGES[@]}")
  if (( \${#packages_to_install[@]} > 0 )); then
    apt-get -o Dir::Cache::Archives="\${STAGING_DIR}" -y install "\${packages_to_install[@]}"
  fi

  if (( \${#REMOVE_PACKAGES[@]} > 0 )); then
    apt-get -y remove "\${REMOVE_PACKAGES[@]}"
  fi
}

write_offline_artifact() {
  mkdir -p "\${STORE_DIR}"
  cat > "\${ARTIFACT_PATH}" <<'EOF'
{
  "offline_application_schema_version": 1,
  "kind": "offline-application",
  "operation_id": "${operationId}",
  "target_version": "${targetVersion}",
  "snapshot_name": "${snapshotName}",
  "boot_mode": "${bootMode}",
  "kernel_package": "${kernelPackage}",
  "test_file_path": "${testFilePath}",
  "staging_directory": "${stagingDirectory}"
}
EOF

  printf '%s\n' "\${REQUIRED_HEALTH_CHECKS[@]}" > "\${HEALTH_CHECK_PATH}"
}

trap 'handle_error $? \$LINENO' ERR
trap cleanup_temporary_apt_source EXIT

ensure_engine_scripts
require_command php

log "Iniciando aplicacion offline para \${OPERATION_ID}"
advance_stage "downloading"
maybe_fail "downloading"
check_free_space
stage_packages

advance_stage "ready"
maybe_fail "ready"

advance_stage "prepared"
maybe_fail "prepared"
create_snapshot

advance_stage "applying_offline"
maybe_fail "applying_offline"
apply_packages
write_offline_artifact

advance_stage "pending_health"
maybe_fail "pending_health"

log "Aplicacion offline completada; la operacion queda en pending_health"
log "Tras reiniciar, ejecutar run-health-checks.sh y luego reconcile-after-reboot.sh"
BASH;
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function renderHealthCheckScript(array $plan): string
    {
        $testFilePath = $this->escapeShellDoubleQuoted((string) $plan['health_check_manifest']['test_file_path']);
        $kernelPackage = $this->escapeShellDoubleQuoted((string) $plan['boot_manifest']['kernel_package']);
        $healthChecks = $this->renderShellArray($plan['health_check_manifest']['required']);

        return <<<BASH
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="\$(cd -- "\$(dirname -- "\${BASH_SOURCE[0]}")" && pwd)"
STORE_DIR="\${W4_UPDATE_STORE_DIR:-\${SCRIPT_DIR}/store}"
RESULT_PATH="\${W4_UPDATE_HEALTH_RESULT_PATH:-\${STORE_DIR}/health-check-results.json}"
TEST_FILE_PATH="\${W4_UPDATE_TEST_FILE_PATH:-${testFilePath}}"
KERNEL_PACKAGE="\${W4_UPDATE_KERNEL_PACKAGE:-${kernelPackage}}"

declare -a REQUIRED_HEALTH_CHECKS=(${healthChecks})
declare -A CHECK_RESULTS=()

run_check() {
  local check_name="\$1"

  case "\${check_name}" in
    boot-entry-present)
      [[ -s /boot/grub/grub.cfg || -s /boot/efi/EFI/BOOT/BOOTX64.EFI ]]
      ;;
    cryptroot-unlock)
      findmnt -n -o SOURCE / | grep -Eq '^/dev/mapper/|cryptroot'
      ;;
    root-subvolume-mounted)
      findmnt -n -o OPTIONS / | grep -q 'subvol='
      ;;
    test-file-present)
      [[ -e "\${TEST_FILE_PATH}" ]]
      ;;
    dpkg-consistent)
      ! dpkg --audit 2>/dev/null | grep -q .
      ;;
    kernel-package-installed)
      dpkg-query -W -f='\${Status}' "\${KERNEL_PACKAGE}" 2>/dev/null | grep -q 'install ok installed'
      ;;
    *)
      return 2
      ;;
  esac
}

overall_status="ok"
mkdir -p "\${STORE_DIR}"

for check_name in "\${REQUIRED_HEALTH_CHECKS[@]}"; do
  if run_check "\${check_name}"; then
    CHECK_RESULTS["\${check_name}"]="ok"
    continue
  fi

  exit_code="\$?"
  if [[ "\${exit_code}" == "2" ]]; then
    CHECK_RESULTS["\${check_name}"]="unsupported"
  else
    CHECK_RESULTS["\${check_name}"]="failed"
  fi

  overall_status="failed"
done

{
  printf '{\n'
  printf '  "health_check_result_schema_version": 1,\n'
  printf '  "kind": "health-check-results",\n'
  printf '  "status": "%s",\n' "\${overall_status}"
  printf '  "results": {\n'

  index=0
  total="\${#REQUIRED_HEALTH_CHECKS[@]}"
  for check_name in "\${REQUIRED_HEALTH_CHECKS[@]}"; do
    index=$((index + 1))
    separator=','
    if (( index == total )); then
      separator=''
    fi
    printf '    "%s": "%s"%s\n' "\${check_name}" "\${CHECK_RESULTS[\${check_name}]}" "\${separator}"
  done

  printf '  }\n'
  printf '}\n'
} > "\${RESULT_PATH}"

if [[ "\${overall_status}" == "ok" ]]; then
  exit 0
fi

exit 1
BASH;
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function renderReconcileScript(array $plan, string $rootDir): string
    {
        $engineRoot = $this->escapeShellDoubleQuoted($rootDir);

        return <<<BASH
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="\$(cd -- "\$(dirname -- "\${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_ENGINE_ROOT="\$(cd -- "\${SCRIPT_DIR}/../../../.." 2>/dev/null && pwd || true)"
ENGINE_ROOT="\${W4_UPDATE_ENGINE_ROOT:-\${DEFAULT_ENGINE_ROOT}}"
STORE_DIR="\${W4_UPDATE_STORE_DIR:-\${SCRIPT_DIR}/store}"
OBSERVED_STAGE="\${W4_UPDATE_OBSERVED_STAGE:-}"
DETAIL="\${W4_UPDATE_RECONCILE_DETAIL:-post-reboot-check}"
ERROR_CODE="\${W4_UPDATE_ERROR_CODE:-health-check-failed}"
ERROR_MESSAGE="\${W4_UPDATE_ERROR_MESSAGE:-La validacion post-arranque fallo}"
AUTO_HEALTHCHECK="\${W4_UPDATE_AUTO_HEALTHCHECK:-1}"
HEALTH_SCRIPT="\${SCRIPT_DIR}/run-health-checks.sh"
HEALTH_RESULT_PATH="\${STORE_DIR}/health-check-results.json"

if [[ -z "\${OBSERVED_STAGE}" && "\${AUTO_HEALTHCHECK}" == "1" ]]; then
  if "\${HEALTH_SCRIPT}"; then
    OBSERVED_STAGE="confirmed"
    DETAIL="\${DETAIL};health=ok"
  else
    OBSERVED_STAGE="failed"
    DETAIL="\${DETAIL};health=failed"
  fi
fi

OBSERVED_STAGE="\${OBSERVED_STAGE:-confirmed}"

COMMAND=(
  php "\${ENGINE_ROOT}/scripts/reconcile_update_operation.php"
  --store-dir "\${STORE_DIR}"
  --observed-stage "\${OBSERVED_STAGE}"
  --component "update-post-boot-check"
  --detail "\${DETAIL}"
  --detail "health_result=\${HEALTH_RESULT_PATH}"
)

if [[ "\${OBSERVED_STAGE}" == "failed" ]]; then
  COMMAND+=(
    --error-code "\${ERROR_CODE}"
    --error-message "\${ERROR_MESSAGE}"
  )
fi

"\${COMMAND[@]}"
BASH;
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
        $event['sequence'] = $this->nextEventSequence($path);
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

    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $operation
     * @param array<string, mixed>|null $lastError
     * @return array<string, mixed>
     */
    private function buildHealthReport(array $plan, array $operation, ?array $lastError = null): array
    {
        $stage = (string) $operation['stage'];

        $status = 'pending';
        if ($stage === 'confirmed') {
            $status = 'ok';
        } elseif ($stage === 'failed') {
            $status = 'failed';
        }

        return [
            'health_report_schema_version' => 1,
            'kind' => 'health-report',
            'operation_id' => $operation['operation_id'],
            'stage' => $stage,
            'status' => $status,
            'required_checks' => $plan['health_check_manifest']['required'],
            'test_file_path' => $plan['health_check_manifest']['test_file_path'],
            'last_error' => $lastError,
            'updated_at' => gmdate('c'),
        ];
    }

    private function resolveReconciledStage(string $currentStage, string $observedStage): string
    {
        if ($observedStage === 'failed') {
            return 'failed';
        }

        if ($observedStage === $currentStage) {
            return $currentStage;
        }

        $reconcilable = [
            'applying_offline' => ['pending_health', 'failed'],
            'pending_health' => ['confirmed', 'failed'],
            'confirmed' => ['confirmed'],
            'failed' => ['failed'],
        ];

        $allowed = $reconcilable[$currentStage] ?? [];
        if (!in_array($observedStage, $allowed, true)) {
            throw new ValidationError(sprintf(
                'No se puede reconciliar %s con observed_stage=%s',
                $currentStage,
                $observedStage
            ));
        }

        return $observedStage;
    }

    private function nextEventSequence(string $path): int
    {
        if (!is_file($path)) {
            return 1;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new ValidationError(sprintf('No se pudo leer el log de eventos: %s', $path));
        }

        return count($lines) + 1;
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

    /**
     * @param mixed $values
     */
    private function renderShellArray(mixed $values): string
    {
        if (!is_array($values)) {
            return '';
        }

        $items = [];
        foreach ($values as $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $items[] = "'" . str_replace("'", "'\"'\"'", $value) . "'";
        }

        return implode(' ', $items);
    }

    private function escapeShellDoubleQuoted(string $value): string
    {
        return str_replace(
            ['\\', '"', '$', '`'],
            ['\\\\', '\\"', '\\$', '\\`'],
            $value
        );
    }
}
