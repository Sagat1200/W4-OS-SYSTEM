<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;
use W4\OS\W4ctl\W4ctlApplication;

final class SystemApiToolkit
{
    private const API_VERSION = '1.0';
    private const PROPOSED_BUS_NAME = 'org.w4.System1';
    private const PROPOSED_OBJECT_PATH = '/org/w4/System1';
    private const PROPOSED_INTERFACE = 'org.w4.System1.Manager';

    private W4ctlApplication $w4ctl;
    private string $rootDir;
    private SystemApiDbusRuntimeProbeToolkit $dbusRuntimeProbe;
    private SystemApiDbusRuntimeActivationToolkit $dbusRuntimeActivation;
    private SystemApiDbusRuntimeBackendToolkit $dbusRuntimeBackend;
    private SystemApiPackagesToolkit $packages;
    private SystemApiServicesToolkit $services;
    private SystemApiOperationsToolkit $operations;

    public function __construct(string $rootDir)
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
        $this->w4ctl = new W4ctlApplication($rootDir);
        $this->dbusRuntimeProbe = new SystemApiDbusRuntimeProbeToolkit();
        $this->dbusRuntimeActivation = new SystemApiDbusRuntimeActivationToolkit();
        $this->dbusRuntimeBackend = new SystemApiDbusRuntimeBackendToolkit();
        $this->packages = new SystemApiPackagesToolkit($this->rootDir);
        $this->services = new SystemApiServicesToolkit();
        $this->operations = new SystemApiOperationsToolkit($rootDir);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function respond(array $request): array
    {
        $requestId = $this->extractRequestId($request);

        try {
            return $this->execute($request, $requestId);
        } catch (ValidationError $exception) {
            return $this->createErrorResponse(
                $requestId,
                $this->resolveErrorCode($exception),
                $exception->getMessage()
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createCapabilitiesResponse(?string $requestId = null): array
    {
        $resolvedRequestId = $requestId !== null && trim($requestId) !== ''
            ? trim($requestId)
            : 'system-api-capabilities';
        $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
        $activationPreparation = $this->dbusRuntimeActivation->createActivationPreparation($runtimeDiagnosis);
        $runtimePresentation = $this->createRuntimePresentation($runtimeDiagnosis, $activationPreparation);
        $resolvedActivationPreparation = $runtimePresentation['activation_preparation'];
        $runtimeBackend = is_array($runtimePresentation['runtime_backend'] ?? null)
            ? $runtimePresentation['runtime_backend']
            : [];
        $executionPlan = is_array($resolvedActivationPreparation['execution_plan'] ?? null)
            ? $resolvedActivationPreparation['execution_plan']
            : [];
        $runtimeBackendPlan = is_array($executionPlan['runtime_backend'] ?? null)
            ? $executionPlan['runtime_backend']
            : [];
        $activationNextAction = $runtimePresentation['next_action'] ?? 'materialize-runtime-adapter';
        $activationRecoveryPlan = is_array($runtimePresentation['recovery_plan'] ?? null)
            ? $runtimePresentation['recovery_plan']
            : ['materialize-runtime-adapter'];
        $selectedRuntimeBackend = $runtimeBackend['selected_backend'] ?? 'contract-pending';
        $runtimeBackendStatus = $runtimeBackend['status'] ?? 'pending-runtime-adapter';

        $payload = [
            'kind' => 'system-api-capabilities',
            'transport' => [
                'default' => 'local-cli-json',
                'tcp_listener_enabled_by_default' => false,
            ],
            'binding' => [
                'status' => 'local-manager-ready',
                'dbus' => [
                    'bus' => 'system',
                    'name' => self::PROPOSED_BUS_NAME,
                    'object_path' => self::PROPOSED_OBJECT_PATH,
                    'interface' => self::PROPOSED_INTERFACE,
                    'methods' => [
                        'Call',
                        'GetCapabilities',
                    ],
                ],
                'local_manager' => [
                    'script' => 'scripts/system_api_manager.php',
                    'commands' => [
                        'call',
                        'get-capabilities',
                    ],
                ],
                'local_dbus_bridge' => [
                    'script' => 'scripts/system_api_dbus_bridge.php',
                    'commands' => [
                        'Call',
                        'GetCapabilities',
                        'DiagnoseRuntime',
                        'DescribeRuntime',
                        'serve-stdio-bridge',
                        'serve-dbus',
                    ],
                    'adapter_status' => 'pending-runtime-adapter',
                    'activation_command' => 'serve-dbus',
                    'activation_method' => 'system.dbus-activation',
                    'native_service_method' => 'system.dbus-native-service',
                    'native_startup_method' => 'system.dbus-native-startup',
                    'native_observation_method' => 'system.dbus-native-observation',
                    'native_coupling_method' => 'system.dbus-native-coupling',
                    'native_ownership_method' => 'system.dbus-native-ownership',
                    'activation_next_action' => $activationNextAction,
                    'activation_recovery_plan' => $activationRecoveryPlan,
                ],
                'local_runtime_adapter' => [
                    'script' => 'scripts/system_api_dbus_runtime_adapter.php',
                    'commands' => [
                        'Call',
                        'GetCapabilities',
                        'DiagnoseRuntime',
                        'DescribeRuntime',
                        'DescribeSpecification',
                        'DescribeActivationPreparation',
                        'AttemptActivation',
                        'ExecuteActivation',
                        'serve-stdio-adapter',
                        'serve-dbus',
                    ],
                    'status' => 'spec-ready',
                    'supported_runtime_backends' => [
                        'contract-pending',
                        'experimental-embedded-runtime',
                        'native-dbus-extension',
                    ],
                    'runtime_status' => $resolvedActivationPreparation['status'] ?? 'pending-runtime-adapter',
                    'selected_runtime_backend' => $selectedRuntimeBackend,
                    'runtime_backend_status' => $runtimeBackendStatus,
                    'activation_next_action' => $activationNextAction,
                    'activation_recovery_plan' => $activationRecoveryPlan,
                ],
                'local_runtime_host' => [
                    'script' => 'scripts/system_api_dbus_runtime_host.php',
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
                    'status' => 'host-ready',
                    'transport' => 'stdio-ndjson-host',
                    'runtime_status' => $runtimeDiagnosis['status'] ?? 'pending-runtime-adapter',
                    'runtime_next_action' => $activationNextAction,
                    'runtime_recovery_plan' => $activationRecoveryPlan,
                ],
                'local_runtime_launcher' => [
                    'script' => 'scripts/system_api_dbus_runtime_launcher.php',
                    'commands' => [
                        'DescribeLauncher',
                        'DescribeLaunchPlan',
                        'DescribeActivationBootstrap',
                        'DescribeActivationPreparation',
                        'AttemptActivation',
                        'ExecuteActivation',
                        'DescribeHost',
                        'DescribeSpecification',
                        'DescribeRuntime',
                        'DiagnoseRuntime',
                        'GetCapabilities',
                        'Call',
                        'serve-stdio-launcher',
                        'serve-dbus',
                    ],
                    'status' => 'launcher-ready',
                    'transport' => 'stdio-ndjson-launcher',
                    'handoff_mode' => 'child-process-stdio-relay',
                    'selected_runtime_backend' => $selectedRuntimeBackend,
                    'runtime_backend_status' => $runtimeBackendStatus,
                    'activation_next_action' => $activationNextAction,
                    'activation_recovery_plan' => $activationRecoveryPlan,
                    'runtime_backend_command' => $runtimeBackendPlan['command'] ?? 'serve-dbus',
                    'runtime_backend_command_role' => $runtimeBackendPlan['command_role'] ?? 'runtime-adapter-activation',
                ],
                'local_daemon' => [
                    'script' => 'scripts/system_api_daemon.php',
                    'commands' => [
                        'serve-stdio',
                    ],
                    'protocol' => 'ndjson',
                ],
            ],
            'methods' => [
                [
                    'name' => 'system.capabilities',
                    'resource_kind' => 'system-api-capabilities',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'profiles.list',
                    'resource_kind' => 'profile-collection',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'packages.search',
                    'resource_kind' => 'package-search-result',
                    'mutates_state' => false,
                    'params' => ['query'],
                ],
                [
                    'name' => 'packages.info',
                    'resource_kind' => 'package',
                    'mutates_state' => false,
                    'params' => ['name', 'architecture?'],
                ],
                [
                    'name' => 'packages.list_installed',
                    'resource_kind' => 'package-installed-collection',
                    'mutates_state' => false,
                    'params' => ['limit?'],
                ],
                [
                    'name' => 'packages.plan',
                    'resource_kind' => 'package-plan',
                    'mutates_state' => false,
                    'params' => ['action', 'selection', 'conffile_policy', 'output_path?'],
                ],
                [
                    'name' => 'packages.apply',
                    'resource_kind' => 'package-operation',
                    'mutates_state' => true,
                    'params' => ['plan_id', 'plan_hash', 'idempotency_key', 'expected_catalog_revision?', 'plan?', 'plan_path?'],
                ],
                [
                    'name' => 'updates.check',
                    'resource_kind' => 'updates-check',
                    'mutates_state' => false,
                    'params' => ['refresh?', 'limit?'],
                ],
                [
                    'name' => 'updates.list',
                    'resource_kind' => 'updates-list',
                    'mutates_state' => false,
                    'params' => ['limit?'],
                ],
                [
                    'name' => 'updates.apply',
                    'resource_kind' => 'update-operation',
                    'mutates_state' => true,
                    'params' => ['operation_id', 'plan_hash', 'idempotency_key', 'expected_catalog_revision?', 'plan?', 'plan_path?'],
                ],
                [
                    'name' => 'updates.reconcile',
                    'resource_kind' => 'update-operation',
                    'mutates_state' => true,
                    'params' => ['operation_id', 'observed_stage?', 'health_result_path?', 'details?', 'observed_error?'],
                ],
                [
                    'name' => 'operations.get',
                    'resource_kind' => 'system-api-operation',
                    'mutates_state' => false,
                    'params' => ['operation_id'],
                ],
                [
                    'name' => 'operations.cancel',
                    'resource_kind' => 'system-api-operation-cancel',
                    'mutates_state' => true,
                    'params' => ['operation_id'],
                ],
                [
                    'name' => 'services.list',
                    'resource_kind' => 'service-collection',
                    'mutates_state' => false,
                    'params' => ['unit?'],
                ],
                [
                    'name' => 'services.status',
                    'resource_kind' => 'service-status',
                    'mutates_state' => false,
                    'params' => ['unit'],
                ],
                [
                    'name' => 'services.start',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit', 'expected_revision?', 'idempotency_key?'],
                ],
                [
                    'name' => 'services.stop',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit', 'expected_revision?', 'idempotency_key?'],
                ],
                [
                    'name' => 'services.restart',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit', 'expected_revision?', 'idempotency_key?'],
                ],
                [
                    'name' => 'services.enable',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit', 'expected_revision?', 'idempotency_key?'],
                ],
                [
                    'name' => 'services.disable',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit', 'expected_revision?', 'idempotency_key?'],
                ],
                [
                    'name' => 'system.dbus-runtime',
                    'resource_kind' => 'system-api-dbus-runtime-diagnosis',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'system.dbus-activation',
                    'resource_kind' => 'system-api-dbus-runtime-activation',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'system.dbus-native-service',
                    'resource_kind' => 'system-api-dbus-native-runtime-service',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'system.dbus-native-startup',
                    'resource_kind' => 'system-api-dbus-native-runtime-startup',
                    'mutates_state' => true,
                ],
                [
                    'name' => 'system.dbus-native-observation',
                    'resource_kind' => 'system-api-dbus-native-runtime-observation',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'system.dbus-native-coupling',
                    'resource_kind' => 'system-api-dbus-native-runtime-coupling',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'system.dbus-native-ownership',
                    'resource_kind' => 'system-api-dbus-native-runtime-ownership',
                    'mutates_state' => true,
                ],
                [
                    'name' => 'profiles.get',
                    'resource_kind' => 'edition-profile',
                    'mutates_state' => false,
                    'params' => ['profile_id'],
                ],
                [
                    'name' => 'builds.validate',
                    'resource_kind' => 'build-validation',
                    'mutates_state' => false,
                    'params' => ['profile_id', 'channel?', 'format?'],
                ],
                [
                    'name' => 'live.inspect',
                    'resource_kind' => 'live-inspection',
                    'mutates_state' => false,
                    'params' => ['profile_id', 'live_output_dir?', 'live_manifest?'],
                ],
                [
                    'name' => 'artifacts.manifest',
                    'resource_kind' => 'artifact-manifest',
                    'mutates_state' => false,
                    'params' => ['artifact', 'profile_id?', 'manifest?'],
                ],
                [
                    'name' => 'updates.plan',
                    'resource_kind' => 'update-plan',
                    'mutates_state' => false,
                    'params' => ['request_path', 'operation_id?', 'output_path?'],
                ],
            ],
        ];

        return $this->createSuccessResponse(
            requestId: $resolvedRequestId,
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: 'system-api',
            data: $payload
        );
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function execute(array $request, string $requestId): array
    {
        $apiVersion = $request['api_version'] ?? null;
        if (!is_string($apiVersion) || trim($apiVersion) !== self::API_VERSION) {
            throw new ValidationError('api_version debe ser 1.0');
        }

        $method = $request['method'] ?? null;
        if (!is_string($method) || trim($method) === '') {
            throw new ValidationError('method debe ser un string no vacio');
        }

        $method = trim($method);
        $params = $request['params'] ?? [];
        if (!is_array($params)) {
            throw new ValidationError('params debe ser un objeto JSON');
        }

        if ($method === 'system.capabilities') {
            return $this->createCapabilitiesResponse($requestId);
        }

        if ($method === 'packages.search') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'packages.search',
                resourceKind: 'package-search-result',
                resourceId: $this->requireString($params, 'query'),
                data: $this->packages->search($this->requireString($params, 'query')),
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'packages.info') {
            $name = $this->requireString($params, 'name');

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'packages.info',
                resourceKind: 'package',
                resourceId: $name,
                data: $this->packages->info($name, $this->optionalString($params, 'architecture')),
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'packages.list_installed') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'packages.list_installed',
                resourceKind: 'package-installed-collection',
                resourceId: 'installed-packages',
                data: $this->packages->listInstalled($this->optionalPositiveInt($params, 'limit')),
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'packages.plan') {
            $plan = $this->packages->plan(
                $this->requireString($params, 'action'),
                $this->requireObjectList($params, 'selection'),
                $this->requireString($params, 'conffile_policy'),
                $this->optionalString($params, 'output_path')
            );

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'packages.plan',
                resourceKind: 'package-plan',
                resourceId: is_string($plan['plan_id'] ?? null) ? $plan['plan_id'] : null,
                data: $plan,
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'packages.apply') {
            $planId = $this->requireString($params, 'plan_id');
            $planHash = $this->requireString($params, 'plan_hash');
            $idempotencyKey = $this->requireString($params, 'idempotency_key');
            $expectedCatalogRevision = $this->optionalString($params, 'expected_catalog_revision');
            $digest = $this->operations->buildIdempotencyDigest('packages.apply', [
                'plan_id' => $planId,
                'plan_hash' => $planHash,
                'expected_catalog_revision' => $expectedCatalogRevision,
            ]);
            $reusedOperation = $this->operations->reuseIdempotentOperation('packages.apply', $idempotencyKey, $digest);
            if ($reusedOperation !== null) {
                $reusedResult = is_array($reusedOperation['result'] ?? null) ? $reusedOperation['result'] : $reusedOperation;

                return $this->createSuccessResponse(
                    requestId: $requestId,
                    method: 'packages.apply',
                    resourceKind: 'package-operation',
                    resourceId: is_string($reusedOperation['operation_id'] ?? null) ? $reusedOperation['operation_id'] : null,
                    data: $reusedResult,
                    provider: 'local-system-api-packages-contract'
                );
            }

            $operation = $this->packages->apply(
                $planId,
                $planHash,
                $idempotencyKey,
                $expectedCatalogRevision,
                $this->optionalObject($params, 'plan'),
                $this->optionalString($params, 'plan_path')
            );
            $storedOperation = $this->operations->record(
                'packages.apply',
                'package-operation',
                (string) ($operation['plan_id'] ?? 'package-operation'),
                $operation
            );
            $this->operations->rememberIdempotentOperation(
                'packages.apply',
                $idempotencyKey,
                $digest,
                (string) ($storedOperation['operation_id'] ?? $operation['operation_id'] ?? '')
            );

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'packages.apply',
                resourceKind: 'package-operation',
                resourceId: is_string($storedOperation['operation_id'] ?? null) ? $storedOperation['operation_id'] : null,
                data: $operation,
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'updates.check') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'updates.check',
                resourceKind: 'updates-check',
                resourceId: 'update-catalog',
                data: $this->packages->checkUpdates($this->optionalBool($params, 'refresh') ?? false, $this->optionalPositiveInt($params, 'limit')),
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'updates.list') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'updates.list',
                resourceKind: 'updates-list',
                resourceId: 'update-catalog',
                data: $this->packages->listUpdates($this->optionalPositiveInt($params, 'limit')),
                provider: 'local-system-api-packages-contract'
            );
        }

        if ($method === 'updates.apply') {
            $operationId = $this->requireString($params, 'operation_id');
            $planHash = $this->requireString($params, 'plan_hash');
            $idempotencyKey = $this->requireString($params, 'idempotency_key');
            $expectedCatalogRevision = $this->optionalString($params, 'expected_catalog_revision');
            $digest = $this->operations->buildIdempotencyDigest('updates.apply', [
                'operation_id' => $operationId,
                'plan_hash' => $planHash,
                'expected_catalog_revision' => $expectedCatalogRevision,
            ]);
            $reusedOperation = $this->operations->reuseIdempotentOperation('updates.apply', $idempotencyKey, $digest);
            if ($reusedOperation !== null) {
                $reusedResult = is_array($reusedOperation['result'] ?? null) ? $reusedOperation['result'] : $reusedOperation;

                return $this->createSuccessResponse(
                    requestId: $requestId,
                    method: 'updates.apply',
                    resourceKind: 'update-operation',
                    resourceId: is_string($reusedOperation['operation_id'] ?? null) ? $reusedOperation['operation_id'] : null,
                    data: $reusedResult,
                    provider: 'local-system-api-updates-contract'
                );
            }

            $this->operations->acquireExclusiveLock('system-updates', $operationId);
            try {
                $operation = $this->packages->applyUpdates(
                    $operationId,
                    $planHash,
                    $idempotencyKey,
                    $expectedCatalogRevision,
                    $this->optionalObject($params, 'plan'),
                    $this->optionalString($params, 'plan_path')
                );
                $storedOperation = $this->operations->record(
                    'updates.apply',
                    'update-operation',
                    (string) ($operation['operation_id'] ?? 'update-operation'),
                    $operation
                );
                $this->operations->rememberIdempotentOperation(
                    'updates.apply',
                    $idempotencyKey,
                    $digest,
                    (string) ($storedOperation['operation_id'] ?? $operation['operation_id'] ?? '')
                );
            } finally {
                $this->operations->releaseExclusiveLock('system-updates', $operationId);
            }

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'updates.apply',
                resourceKind: 'update-operation',
                resourceId: is_string($storedOperation['operation_id'] ?? null) ? $storedOperation['operation_id'] : null,
                data: $operation,
                provider: 'local-system-api-updates-contract'
            );
        }

        if ($method === 'updates.reconcile') {
            $operationId = $this->requireString($params, 'operation_id');
            $operation = $this->packages->reconcileUpdates(
                $operationId,
                $this->optionalString($params, 'observed_stage'),
                $this->optionalString($params, 'health_result_path'),
                $this->optionalObject($params, 'details') ?? [],
                $this->optionalObject($params, 'observed_error')
            );
            $storedOperation = $this->operations->refresh($operationId, $operation);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'updates.reconcile',
                resourceKind: 'update-operation',
                resourceId: is_string($storedOperation['operation_id'] ?? null) ? $storedOperation['operation_id'] : $operationId,
                data: $operation,
                provider: 'local-system-api-updates-contract'
            );
        }

        if ($method === 'operations.get') {
            $operationId = $this->requireString($params, 'operation_id');
            $operation = $this->operations->get($operationId);
            $operation = $this->refreshOperationFromDurableStores($operation);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'operations.get',
                resourceKind: 'system-api-operation',
                resourceId: $operationId,
                data: $operation,
                provider: 'local-system-api-operations-contract'
            );
        }

        if ($method === 'operations.cancel') {
            $operationId = $this->requireString($params, 'operation_id');

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'operations.cancel',
                resourceKind: 'system-api-operation-cancel',
                resourceId: $operationId,
                data: $this->operations->cancel($operationId),
                provider: 'local-system-api-operations-contract'
            );
        }

        if ($method === 'services.list') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.list',
                resourceKind: 'service-collection',
                resourceId: 'system-services',
                data: $this->services->list($this->optionalString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.status') {
            $unit = $this->requireString($params, 'unit');

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.status',
                resourceKind: 'service-status',
                resourceId: $unit,
                data: $this->services->status($unit),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.start') {
            return $this->handleServiceOperation($requestId, 'services.start', $params, fn (string $unit, ?string $expectedRevision): array => $this->services->start($unit, $expectedRevision));
        }

        if ($method === 'services.stop') {
            return $this->handleServiceOperation($requestId, 'services.stop', $params, fn (string $unit, ?string $expectedRevision): array => $this->services->stop($unit, $expectedRevision));
        }

        if ($method === 'services.restart') {
            return $this->handleServiceOperation($requestId, 'services.restart', $params, fn (string $unit, ?string $expectedRevision): array => $this->services->restart($unit, $expectedRevision));
        }

        if ($method === 'services.enable') {
            return $this->handleServiceOperation($requestId, 'services.enable', $params, fn (string $unit, ?string $expectedRevision): array => $this->services->enable($unit, $expectedRevision));
        }

        if ($method === 'services.disable') {
            return $this->handleServiceOperation($requestId, 'services.disable', $params, fn (string $unit, ?string $expectedRevision): array => $this->services->disable($unit, $expectedRevision));
        }

        if ($method === 'system.dbus-runtime') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $activationPreparation = $this->dbusRuntimeActivation->createActivationPreparation($runtimeDiagnosis);
            $runtimePresentation = $this->createRuntimePresentation($runtimeDiagnosis, $activationPreparation);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-runtime',
                resourceKind: 'system-api-dbus-runtime-diagnosis',
                resourceId: 'org.w4.System1',
                data: $this->createRuntimeContractPayload($runtimeDiagnosis, $runtimePresentation),
                provider: 'local-system-api-runtime-contract'
            );
        }

        if ($method === 'system.dbus-activation') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $activationReport = $this->dbusRuntimeActivation->createPendingActivationReport(
                $runtimeDiagnosis,
                'local-system-api'
            );

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-activation',
                resourceKind: 'system-api-dbus-runtime-activation',
                resourceId: 'org.w4.System1',
                data: $activationReport,
                provider: 'local-system-api-activation-contract'
            );
        }

        if ($method === 'system.dbus-native-service') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $serviceDefinition = $this->dbusRuntimeBackend->createNativeRuntimeServiceDefinition($runtimeDiagnosis);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-native-service',
                resourceKind: 'system-api-dbus-native-runtime-service',
                resourceId: 'org.w4.System1',
                data: $this->createNativeServiceContractPayload($runtimeDiagnosis, $serviceDefinition),
                provider: 'local-system-api-native-service-contract'
            );
        }

        if ($method === 'system.dbus-native-startup') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $startupReport = $this->dbusRuntimeBackend->createNativeRuntimeServiceStartupReport($runtimeDiagnosis);
            $this->dbusRuntimeBackend->persistNativeRuntimeStartupArtifacts($startupReport);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-native-startup',
                resourceKind: 'system-api-dbus-native-runtime-startup',
                resourceId: 'org.w4.System1',
                data: $this->createNativeStartupContractPayload($runtimeDiagnosis, $startupReport),
                provider: 'local-system-api-native-startup-contract'
            );
        }

        if ($method === 'system.dbus-native-observation') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $observationReport = $this->dbusRuntimeBackend->createNativeRuntimeServiceObservationReport($runtimeDiagnosis);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-native-observation',
                resourceKind: 'system-api-dbus-native-runtime-observation',
                resourceId: 'org.w4.System1',
                data: $this->createNativeObservationContractPayload($runtimeDiagnosis, $observationReport),
                provider: 'local-system-api-native-observation-contract'
            );
        }

        if ($method === 'system.dbus-native-coupling') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $couplingReport = $this->dbusRuntimeBackend->createNativeRuntimeCouplingReport($runtimeDiagnosis);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-native-coupling',
                resourceKind: 'system-api-dbus-native-runtime-coupling',
                resourceId: 'org.w4.System1',
                data: $this->createNativeCouplingContractPayload($runtimeDiagnosis, $couplingReport),
                provider: 'local-system-api-native-coupling-contract'
            );
        }

        if ($method === 'system.dbus-native-ownership') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $ownershipReport = $this->dbusRuntimeBackend->createNativeBusOwnershipReport($runtimeDiagnosis);
            $this->dbusRuntimeBackend->persistNativeRuntimeOwnershipArtifacts($ownershipReport);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-native-ownership',
                resourceKind: 'system-api-dbus-native-runtime-ownership',
                resourceId: 'org.w4.System1',
                data: $this->createNativeOwnershipContractPayload($runtimeDiagnosis, $ownershipReport),
                provider: 'local-system-api-native-ownership-contract'
            );
        }

        if ($method === 'updates.plan') {
            return $this->handleUpdatesPlan($requestId, $params);
        }

        [$command, $options, $resourceKind, $resourceId] = $this->translateMethod($method, $params);
        $payload = $this->w4ctl->handleCommand($command, $options);

        return $this->createSuccessResponse(
            requestId: $requestId,
            method: $method,
            resourceKind: $resourceKind,
            resourceId: $this->resolveResourceId($resourceId, $payload),
            data: $payload
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array{0:string,1:array<string, string>,2:string,3:?string}
     */
    private function translateMethod(string $method, array $params): array
    {
        return match ($method) {
            'profiles.list' => ['profile:list', [], 'profile-collection', 'profiles'],
            'profiles.get' => [
                'profile:show',
                ['profile' => $this->requireString($params, 'profile_id')],
                'edition-profile',
                $this->requireString($params, 'profile_id'),
            ],
            'builds.validate' => [
                'build:validate',
                $this->filterOptions([
                    'profile' => $this->requireString($params, 'profile_id'),
                    'channel' => $this->optionalString($params, 'channel') ?? 'testing',
                    'format' => $this->optionalString($params, 'format') ?? 'iso',
                ]),
                'build-validation',
                $this->requireString($params, 'profile_id'),
            ],
            'live.inspect' => [
                'live:inspect',
                $this->filterOptions([
                    'profile' => $this->requireString($params, 'profile_id'),
                    'live-output-dir' => $this->optionalString($params, 'live_output_dir'),
                    'live-manifest' => $this->optionalString($params, 'live_manifest'),
                ]),
                'live-inspection',
                $this->requireString($params, 'profile_id'),
            ],
            'artifacts.manifest' => [
                'artifact:manifest',
                $this->filterOptions([
                    'artifact' => $this->requireString($params, 'artifact'),
                    'profile' => $this->optionalString($params, 'profile_id'),
                    'manifest' => $this->optionalString($params, 'manifest'),
                ]),
                'artifact-manifest',
                $this->optionalString($params, 'profile_id') ?? $this->requireString($params, 'artifact'),
            ],
            'updates.plan' => [
                'update:plan',
                $this->filterOptions([
                    'request' => $this->requireString($params, 'request_path'),
                    'operation-id' => $this->optionalString($params, 'operation_id'),
                    'output' => $this->optionalString($params, 'output_path'),
                ]),
                'update-plan',
                $this->optionalString($params, 'operation_id'),
            ],
            default => throw new ValidationError(sprintf('Metodo no soportado: %s', $method)),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function handleUpdatesPlan(string $requestId, array $params): array
    {
        $payload = $this->w4ctl->handleCommand('update:plan', $this->filterOptions([
            'request' => $this->requireString($params, 'request_path'),
            'operation-id' => $this->optionalString($params, 'operation_id'),
            'output' => $this->optionalString($params, 'output_path'),
        ]));

        $outputPath = $payload['output_path'] ?? null;
        if (is_string($outputPath) && trim($outputPath) !== '') {
            $absoluteOutputPath = $this->resolveWorkspacePath($outputPath);
            $raw = file_get_contents($absoluteOutputPath);
            if (!is_string($raw)) {
                throw new ValidationError(sprintf('No se pudo leer update-plan generado: %s', $absoluteOutputPath));
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new ValidationError(sprintf('Update-plan invalido: %s', $absoluteOutputPath));
            }

            if (is_string($decoded['catalog_revision'] ?? null)) {
                $payload['catalog_revision'] = trim((string) $decoded['catalog_revision']);
            }
            $payload['plan_hash'] = $this->packages->computeUpdatePlanHash($decoded);
        }

        return $this->createSuccessResponse(
            requestId: $requestId,
            method: 'updates.plan',
            resourceKind: 'update-plan',
            resourceId: is_string($payload['operation_id'] ?? null) ? $payload['operation_id'] : $this->optionalString($params, 'operation_id'),
            data: $payload
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function createSuccessResponse(
        string $requestId,
        string $method,
        string $resourceKind,
        ?string $resourceId,
        array $data,
        string $provider = 'local-w4ctl-facade'
    ): array {
        $observedAt = gmdate('c');

        return [
            'api_version' => self::API_VERSION,
            'request_id' => $requestId,
            'result' => [
                'operation_id' => is_string($data['operation_id'] ?? null) ? $data['operation_id'] : null,
                'method' => $method,
                'resource' => [
                    'id' => $resourceId,
                    'kind' => $resourceKind,
                    'revision' => $this->buildRevision($data),
                    'observed_at' => $observedAt,
                    'provider' => $provider,
                ],
                'capabilities' => [
                    'read-state',
                    'local-cli-json',
                    'w4ctl-contract-reuse',
                ],
                'data' => $data,
                'links' => [
                    'self' => '/system-api/' . str_replace('.', '/', $method),
                ],
            ],
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createErrorResponse(string $requestId, string $code, string $message): array
    {
        return [
            'api_version' => self::API_VERSION,
            'request_id' => $requestId,
            'result' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'retryable' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $request
     */
    private function extractRequestId(array $request): string
    {
        $requestId = $request['request_id'] ?? null;
        if (!is_string($requestId) || trim($requestId) === '') {
            return 'invalid-request';
        }

        return trim($requestId);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function requireString(array $params, string $field): string
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('params.%s debe ser un string no vacio', $field));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function optionalString(array $params, string $field): ?string
    {
        $value = $params[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('params.%s debe ser un string no vacio cuando se usa', $field));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function optionalPositiveInt(array $params, string $field): ?int
    {
        $value = $params[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_int($value) || $value <= 0) {
            throw new ValidationError(sprintf('params.%s debe ser un entero positivo cuando se usa', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function optionalBool(array $params, string $field): ?bool
    {
        $value = $params[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_bool($value)) {
            throw new ValidationError(sprintf('params.%s debe ser un booleano cuando se usa', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $params
     * @param callable(string, ?string): array<string, mixed> $executor
     * @return array<string, mixed>
     */
    private function handleServiceOperation(string $requestId, string $method, array $params, callable $executor): array
    {
        $unit = $this->requireString($params, 'unit');
        $expectedRevision = $this->optionalString($params, 'expected_revision');
        $idempotencyKey = $this->optionalString($params, 'idempotency_key');

        if ($idempotencyKey !== null) {
            $digest = $this->operations->buildIdempotencyDigest($method, [
                'unit' => $unit,
                'expected_revision' => $expectedRevision,
            ]);
            $reusedOperation = $this->operations->reuseIdempotentOperation($method, $idempotencyKey, $digest);
            if ($reusedOperation !== null) {
                $reusedResult = is_array($reusedOperation['result'] ?? null) ? $reusedOperation['result'] : $reusedOperation;

                return $this->createSuccessResponse(
                    requestId: $requestId,
                    method: $method,
                    resourceKind: 'service-operation',
                    resourceId: is_string($reusedOperation['operation_id'] ?? null) ? $reusedOperation['operation_id'] : $unit,
                    data: $reusedResult,
                    provider: 'local-system-api-services-contract'
                );
            }
        }

        $operation = $executor($unit, $expectedRevision);
        $storedOperation = $this->operations->record($method, 'service-operation', $unit, $operation);

        if ($idempotencyKey !== null) {
            $this->operations->rememberIdempotentOperation(
                $method,
                $idempotencyKey,
                $this->operations->buildIdempotencyDigest($method, [
                    'unit' => $unit,
                    'expected_revision' => $expectedRevision,
                ]),
                (string) ($storedOperation['operation_id'] ?? $operation['operation_id'] ?? '')
            );
        }

        return $this->createSuccessResponse(
            requestId: $requestId,
            method: $method,
            resourceKind: 'service-operation',
            resourceId: is_string($storedOperation['operation_id'] ?? null) ? $storedOperation['operation_id'] : $unit,
            data: $operation,
            provider: 'local-system-api-services-contract'
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function requireObjectList(array $params, string $field): array
    {
        $value = $params[$field] ?? null;
        if (!is_array($value) || $value === [] || array_is_list($value) === false) {
            throw new ValidationError(sprintf('params.%s debe ser una lista no vacia', $field));
        }

        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                throw new ValidationError(sprintf('params.%s[%d] debe ser un objeto', $field, $index));
            }
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    private function optionalObject(array $params, string $field): ?array
    {
        $value = $params[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new ValidationError(sprintf('params.%s debe ser un objeto JSON cuando se usa', $field));
        }

        return $value;
    }

    /**
     * @param array<string, ?string> $options
     * @return array<string, string>
     */
    private function filterOptions(array $options): array
    {
        $filtered = [];
        foreach ($options as $key => $value) {
            if ($value !== null && $value !== '') {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function resolveWorkspacePath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path));
        if ($normalized === '') {
            throw new ValidationError('Ruta invalida para artifact del System API');
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $normalized) === 1 || str_starts_with($normalized, DIRECTORY_SEPARATOR)) {
            return $normalized;
        }

        return $this->rootDir . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildRevision(array $data): string
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = 'unserializable';
        }

        return sha1($encoded);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveResourceId(?string $resourceId, array $payload): ?string
    {
        if ($resourceId !== null && $resourceId !== '') {
            return $resourceId;
        }

        $payloadResourceId = $payload['operation_id'] ?? $payload['profile_id'] ?? null;
        if (!is_string($payloadResourceId) || $payloadResourceId === '') {
            return null;
        }

        return $payloadResourceId;
    }

    private function resolveErrorCode(ValidationError $exception): string
    {
        if (str_starts_with($exception->getMessage(), 'Metodo no soportado:')) {
            return 'NOT_SUPPORTED';
        }

        if (str_starts_with($exception->getMessage(), 'Conflicto:')) {
            return 'CONFLICT';
        }

        if (str_starts_with($exception->getMessage(), 'Backend no disponible:')) {
            return 'BACKEND_UNAVAILABLE';
        }

        if (str_starts_with($exception->getMessage(), 'Operacion no encontrada:')) {
            return 'NOT_FOUND';
        }

        return 'VALIDATION_ERROR';
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    private function refreshOperationFromDurableStores(array $operation): array
    {
        $method = $operation['method'] ?? null;
        $operationId = $operation['operation_id'] ?? null;
        if (!is_string($method) || !is_string($operationId) || trim($operationId) === '') {
            return $operation;
        }

        if (!in_array($method, ['updates.apply', 'updates.reconcile'], true)) {
            return $operation;
        }

        $refreshedResult = $this->packages->refreshDurableUpdateOperation($operationId, $operation);
        if ($refreshedResult === null) {
            return $operation;
        }

        return $this->operations->refresh($operationId, $refreshedResult);
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $runtimePresentation
     * @return array<string, mixed>
     */
    private function createRuntimeContractPayload(array $runtimeDiagnosis, array $runtimePresentation): array
    {
        $payload = $runtimeDiagnosis;
        $activationPreparation = is_array($runtimePresentation['activation_preparation'] ?? null)
            ? $runtimePresentation['activation_preparation']
            : [];
        $payload['next_action'] = $runtimePresentation['next_action'] ?? ($activationPreparation['next_action'] ?? 'materialize-runtime-adapter');
        $payload['recovery_plan'] = is_array($runtimePresentation['recovery_plan'] ?? null)
            ? $runtimePresentation['recovery_plan']
            : ['materialize-runtime-adapter'];
        $payload['runtime_backend'] = is_array($runtimePresentation['runtime_backend'] ?? null)
            ? $runtimePresentation['runtime_backend']
            : [];
        $payload['native_runtime_service'] = is_array($runtimePresentation['native_runtime_service'] ?? null)
            ? $runtimePresentation['native_runtime_service']
            : null;
        $payload['activation_preparation'] = $activationPreparation;

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $activationPreparation
     * @return array<string, mixed>
     */
    private function createRuntimePresentation(array $runtimeDiagnosis, array $activationPreparation): array
    {
        $runtimeBackend = is_array($activationPreparation['runtime_backend'] ?? null)
            ? $activationPreparation['runtime_backend']
            : [];
        $nativeRuntimeService = is_array($activationPreparation['native_runtime_service'] ?? null)
            ? $activationPreparation['native_runtime_service']
            : null;
        $nextAction = is_string($activationPreparation['next_action'] ?? null)
            ? $activationPreparation['next_action']
            : 'materialize-runtime-adapter';
        $recoveryPlan = is_array($activationPreparation['recovery_plan'] ?? null)
            ? $activationPreparation['recovery_plan']
            : [$nextAction];

        if ($this->shouldProjectObservedNativeRuntime($runtimeBackend, $nativeRuntimeService)) {
            $observation = $this->dbusRuntimeBackend->createNativeRuntimeServiceObservationReport($runtimeDiagnosis);
            if ((($observation['startup_acknowledged'] ?? false) === true) || (($observation['observed'] ?? false) === true)) {
                $nativeRuntimeService = $this->createObservedNativeRuntimeServicePresentation(
                    $nativeRuntimeService,
                    $observation
                );
                $nextAction = is_string($observation['next_action'] ?? null) && trim((string) $observation['next_action']) !== ''
                    ? trim((string) $observation['next_action'])
                    : $nextAction;
                $recoveryPlan = $this->buildRuntimeRecoveryPlan($nextAction, $nativeRuntimeService, $recoveryPlan);
                $activationPreparation['native_runtime_service'] = $nativeRuntimeService;
                $activationPreparation['next_action'] = $nextAction;
                $activationPreparation['recovery_plan'] = $recoveryPlan;
            }
        }

        return [
            'activation_preparation' => $activationPreparation,
            'runtime_backend' => $runtimeBackend,
            'native_runtime_service' => $nativeRuntimeService,
            'next_action' => $nextAction,
            'recovery_plan' => $recoveryPlan,
        ];
    }

    /**
     * @param array<string, mixed> $runtimeBackend
     * @param array<string, mixed>|null $nativeRuntimeService
     */
    private function shouldProjectObservedNativeRuntime(array $runtimeBackend, ?array $nativeRuntimeService): bool
    {
        return is_array($nativeRuntimeService)
            && (($runtimeBackend['selected_backend'] ?? null) === 'native-dbus-extension')
            && (($runtimeBackend['materialized'] ?? false) === true);
    }

    /**
     * @param array<string, mixed> $nativeRuntimeService
     * @param array<string, mixed> $observation
     * @return array<string, mixed>
     */
    private function createObservedNativeRuntimeServicePresentation(array $nativeRuntimeService, array $observation): array
    {
        $observedServiceDefinition = is_array($observation['service_definition'] ?? null)
            ? $observation['service_definition']
            : [];
        $startupReport = is_array($observation['startup_report'] ?? null)
            ? $observation['startup_report']
            : [];
        $startupReportServiceDefinition = is_array($startupReport['service_definition'] ?? null)
            ? $startupReport['service_definition']
            : [];

        $resolvedService = array_replace_recursive(
            $nativeRuntimeService,
            $observedServiceDefinition,
            $startupReportServiceDefinition
        );

        if (($observation['startup_acknowledged'] ?? false) === true) {
            $resolvedService['startup_mode'] = is_string($resolvedService['startup_mode'] ?? null)
                && trim((string) $resolvedService['startup_mode']) !== ''
                ? trim((string) $resolvedService['startup_mode'])
                : 'launch-process';
        }

        if (is_string($observation['blocking_reason'] ?? null) && trim((string) $observation['blocking_reason']) !== '') {
            $resolvedService['blocking_reason'] = trim((string) $observation['blocking_reason']);
        }

        if (is_string($observation['next_action'] ?? null) && trim((string) $observation['next_action']) !== '') {
            $resolvedService['next_action'] = trim((string) $observation['next_action']);
        }

        return $resolvedService;
    }

    /**
     * @param array<string, mixed>|null $nativeRuntimeService
     * @param list<string> $fallbackPlan
     * @return list<string>
     */
    private function buildRuntimeRecoveryPlan(string $nextAction, ?array $nativeRuntimeService, array $fallbackPlan = []): array
    {
        $plan = [];

        foreach ([$nextAction, $nativeRuntimeService['next_action'] ?? null, ...$fallbackPlan] as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '' || in_array(trim($candidate), $plan, true)) {
                continue;
            }

            $plan[] = trim($candidate);
        }

        return $plan === [] ? ['materialize-runtime-adapter'] : $plan;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $couplingReport
     * @return array<string, mixed>
     */
    private function createNativeCouplingContractPayload(array $runtimeDiagnosis, array $couplingReport): array
    {
        $payload = $couplingReport;
        $payload['runtime_diagnosis'] = $runtimeDiagnosis;

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $serviceDefinition
     * @return array<string, mixed>
     */
    private function createNativeServiceContractPayload(array $runtimeDiagnosis, array $serviceDefinition): array
    {
        $payload = $serviceDefinition;
        $payload['runtime_diagnosis'] = $runtimeDiagnosis;

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $startupReport
     * @return array<string, mixed>
     */
    private function createNativeStartupContractPayload(array $runtimeDiagnosis, array $startupReport): array
    {
        $payload = $startupReport;
        $payload['runtime_diagnosis'] = $runtimeDiagnosis;

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $observationReport
     * @return array<string, mixed>
     */
    private function createNativeObservationContractPayload(array $runtimeDiagnosis, array $observationReport): array
    {
        $payload = $observationReport;
        $payload['runtime_diagnosis'] = $runtimeDiagnosis;

        return $payload;
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $ownershipReport
     * @return array<string, mixed>
     */
    private function createNativeOwnershipContractPayload(array $runtimeDiagnosis, array $ownershipReport): array
    {
        $payload = $ownershipReport;
        $payload['runtime_diagnosis'] = $runtimeDiagnosis;

        return $payload;
    }
}
