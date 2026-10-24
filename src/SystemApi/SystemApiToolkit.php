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
    private SystemApiDbusRuntimeProbeToolkit $dbusRuntimeProbe;
    private SystemApiDbusRuntimeActivationToolkit $dbusRuntimeActivation;
    private SystemApiDbusRuntimeBackendToolkit $dbusRuntimeBackend;
    private SystemApiPackagesToolkit $packages;
    private SystemApiServicesToolkit $services;

    public function __construct(string $rootDir)
    {
        $this->w4ctl = new W4ctlApplication($rootDir);
        $this->dbusRuntimeProbe = new SystemApiDbusRuntimeProbeToolkit();
        $this->dbusRuntimeActivation = new SystemApiDbusRuntimeActivationToolkit();
        $this->dbusRuntimeBackend = new SystemApiDbusRuntimeBackendToolkit();
        $this->packages = new SystemApiPackagesToolkit();
        $this->services = new SystemApiServicesToolkit();
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
        $runtimeBackend = is_array($activationPreparation['runtime_backend'] ?? null)
            ? $activationPreparation['runtime_backend']
            : [];
        $executionPlan = is_array($activationPreparation['execution_plan'] ?? null)
            ? $activationPreparation['execution_plan']
            : [];
        $runtimeBackendPlan = is_array($executionPlan['runtime_backend'] ?? null)
            ? $executionPlan['runtime_backend']
            : [];
        $activationNextAction = $activationPreparation['next_action'] ?? 'materialize-runtime-adapter';
        $activationRecoveryPlan = is_array($activationPreparation['recovery_plan'] ?? null)
            ? $activationPreparation['recovery_plan']
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
                    'runtime_status' => $activationPreparation['status'] ?? 'pending-runtime-adapter',
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
                    'params' => ['unit'],
                ],
                [
                    'name' => 'services.stop',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit'],
                ],
                [
                    'name' => 'services.restart',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit'],
                ],
                [
                    'name' => 'services.enable',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit'],
                ],
                [
                    'name' => 'services.disable',
                    'resource_kind' => 'service-operation',
                    'mutates_state' => true,
                    'params' => ['unit'],
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
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.start',
                resourceKind: 'service-operation',
                resourceId: $this->requireString($params, 'unit'),
                data: $this->services->start($this->requireString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.stop') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.stop',
                resourceKind: 'service-operation',
                resourceId: $this->requireString($params, 'unit'),
                data: $this->services->stop($this->requireString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.restart') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.restart',
                resourceKind: 'service-operation',
                resourceId: $this->requireString($params, 'unit'),
                data: $this->services->restart($this->requireString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.enable') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.enable',
                resourceKind: 'service-operation',
                resourceId: $this->requireString($params, 'unit'),
                data: $this->services->enable($this->requireString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'services.disable') {
            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'services.disable',
                resourceKind: 'service-operation',
                resourceId: $this->requireString($params, 'unit'),
                data: $this->services->disable($this->requireString($params, 'unit')),
                provider: 'local-system-api-services-contract'
            );
        }

        if ($method === 'system.dbus-runtime') {
            $runtimeDiagnosis = $this->dbusRuntimeProbe->createReport();
            $activationPreparation = $this->dbusRuntimeActivation->createActivationPreparation($runtimeDiagnosis);

            return $this->createSuccessResponse(
                requestId: $requestId,
                method: 'system.dbus-runtime',
                resourceKind: 'system-api-dbus-runtime-diagnosis',
                resourceId: 'org.w4.System1',
                data: $this->createRuntimeContractPayload($runtimeDiagnosis, $activationPreparation),
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
                'operation_id' => null,
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

        return 'VALIDATION_ERROR';
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, mixed> $activationPreparation
     * @return array<string, mixed>
     */
    private function createRuntimeContractPayload(array $runtimeDiagnosis, array $activationPreparation): array
    {
        $payload = $runtimeDiagnosis;
        $payload['next_action'] = $activationPreparation['next_action'] ?? 'materialize-runtime-adapter';
        $payload['recovery_plan'] = is_array($activationPreparation['recovery_plan'] ?? null)
            ? $activationPreparation['recovery_plan']
            : ['materialize-runtime-adapter'];
        $payload['runtime_backend'] = is_array($activationPreparation['runtime_backend'] ?? null)
            ? $activationPreparation['runtime_backend']
            : [];
        $payload['native_runtime_service'] = is_array($activationPreparation['native_runtime_service'] ?? null)
            ? $activationPreparation['native_runtime_service']
            : null;
        $payload['activation_preparation'] = $activationPreparation;

        return $payload;
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
