<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiDbusRuntimeBackendToolkit
{
    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();
        $selectedBackend = $this->resolveSelectedBackend($env);
        $ready = ($runtimeDiagnosis['ready'] ?? false) === true;

        return match ($selectedBackend) {
            'experimental-embedded-runtime' => $this->createExperimentalEmbeddedRuntimeReport($ready),
            'native-dbus-extension' => $this->createNativeDbusExtensionReport($ready, $env),
            'contract-pending' => $this->createContractPendingReport($ready),
            default => $this->createUnsupportedBackendReport($selectedBackend, $ready),
        };
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createNativeRuntimeServiceDefinition(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();
        $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
        $runtimeBackend = $this->createReport($runtimeDiagnosis, $env);
        $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null)
            ? $runtimeBackend['selected_backend']
            : 'contract-pending';
        $eligible = $selectedBackend === 'native-dbus-extension' && (($runtimeBackend['materialized'] ?? false) === true);
        $recoveryContext = $this->createNativeRuntimeRecoveryContext($ready, $selectedBackend, $env);
        $artifactsBasePath = $this->resolveNativeRuntimeArtifactsBasePath($env);

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-service',
            'status' => $eligible ? 'native-runtime-service-ready-to-start' : 'native-runtime-service-blocked',
            'eligible' => $eligible,
            'selected_backend' => $selectedBackend,
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'startup_command' => 'StartNativeRuntimeService',
            'startup_mode' => 'bootstrap-contract',
            'ownership_goal' => 'org.w4.System1',
            'service_lifecycle' => 'request-name-register-object-wait-loop',
            'artifacts' => [
                'ready_marker_path' => $this->resolvePathValue(
                    $env,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER',
                    $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready'
                ),
                'status_file_path' => $this->resolvePathValue(
                    $env,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE',
                    $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json'
                ),
                'pid_file_path' => $this->resolvePathValue(
                    $env,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE',
                    $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid'
                ),
            ],
            'runtime_backend' => $runtimeBackend,
            'native_backend_candidate' => $recoveryContext['native_backend_candidate'],
            'blocking_reason' => $eligible
                ? 'native-runtime-service-not-started'
                : (is_string($runtimeBackend['blocking_reason'] ?? null)
                    ? $runtimeBackend['blocking_reason']
                    : 'native-runtime-service-unavailable'),
            'next_action' => $eligible ? 'start-native-runtime-service' : $recoveryContext['next_action'],
        ];
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createNativeRuntimeServiceStartupReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $serviceDefinition = $this->createNativeRuntimeServiceDefinition($runtimeDiagnosis, $environment);
        $eligible = ($serviceDefinition['eligible'] ?? false) === true;

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-startup',
            'status' => $eligible ? 'native-runtime-service-bootstrap-recorded' : 'native-runtime-service-blocked',
            'startup_acknowledged' => $eligible,
            'exit_code' => $eligible ? 0 : 3,
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'service_definition' => $serviceDefinition,
            'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
            'ownership_state' => $eligible ? 'startup-recorded-no-process' : 'blocked-before-startup',
            'blocking_reason' => $eligible
                ? 'native-runtime-service-not-running'
                : (is_string($serviceDefinition['blocking_reason'] ?? null)
                    ? $serviceDefinition['blocking_reason']
                    : 'native-runtime-service-unavailable'),
            'next_action' => $eligible
                ? 'launch-native-runtime-service'
                : (is_string($serviceDefinition['next_action'] ?? null)
                    ? $serviceDefinition['next_action']
                    : 'complete-native-runtime-backend'),
        ];
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createNativeRuntimeServiceObservationReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $serviceDefinition = $this->createNativeRuntimeServiceDefinition($runtimeDiagnosis, $environment);
        $eligible = ($serviceDefinition['eligible'] ?? false) === true;
        $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
        $readyMarker = $this->observeTextArtifact($this->optionalArtifactPath($artifacts, 'ready_marker_path'));
        $statusFile = $this->observeJsonArtifact($this->optionalArtifactPath($artifacts, 'status_file_path'));
        $pidFile = $this->observePidArtifact($this->optionalArtifactPath($artifacts, 'pid_file_path'));
        $startupReport = is_array($statusFile['payload'] ?? null) ? $statusFile['payload'] : null;
        $startupAcknowledged = ($startupReport['startup_acknowledged'] ?? false) === true;
        $pid = is_int($pidFile['pid'] ?? null) ? $pidFile['pid'] : null;
        $pidIndicatesRunningProcess = is_int($pid) && $pid > 0;
        $artifactsObserved = ($readyMarker['exists'] ?? false) === true
            && ($statusFile['exists'] ?? false) === true
            && ($pidFile['exists'] ?? false) === true;
        $observed = $eligible && $startupAcknowledged && $artifactsObserved && $pidIndicatesRunningProcess;
        $reportedOwnershipState = is_string($startupReport['ownership_state'] ?? null)
            ? $startupReport['ownership_state']
            : null;
        $ownershipState = $reportedOwnershipState;
        if ($ownershipState === null) {
            $ownershipState = $observed
                ? 'pending-bus-name-ownership'
                : ($startupAcknowledged ? 'startup-recorded-no-process' : 'native-runtime-service-not-started');
        }
        if ($pidIndicatesRunningProcess && $ownershipState === 'startup-recorded-no-process') {
            $ownershipState = 'pending-bus-name-ownership';
        }
        $status = !$eligible
            ? 'native-runtime-service-blocked'
            : ($observed
                ? ($ownershipState === 'bus-name-owned'
                    ? 'native-runtime-service-observed-active'
                    : 'native-runtime-service-observed-pending-ownership')
                : (($readyMarker['exists'] ?? false) === true || ($statusFile['exists'] ?? false) === true || ($pidFile['exists'] ?? false) === true
                    ? ($startupAcknowledged && !$pidIndicatesRunningProcess
                        ? 'native-runtime-service-not-running'
                        : 'native-runtime-service-observation-incomplete')
                    : 'native-runtime-service-not-observed'));
        $blockingReason = !$eligible
            ? (is_string($serviceDefinition['blocking_reason'] ?? null)
                ? $serviceDefinition['blocking_reason']
                : 'native-runtime-service-unavailable')
            : ($status === 'native-runtime-service-not-observed'
                ? 'native-runtime-service-not-started'
                : ($status === 'native-runtime-service-not-running'
                    ? 'native-runtime-service-not-running'
                    : ($status === 'native-runtime-service-observation-incomplete'
                    ? 'native-runtime-service-artifacts-incomplete'
                    : ($ownershipState === 'bus-name-owned' ? null : 'native-runtime-bus-ownership-pending'))));
        $nextAction = !$eligible
            ? (is_string($serviceDefinition['next_action'] ?? null)
                ? $serviceDefinition['next_action']
                : 'complete-native-runtime-backend')
            : ($status === 'native-runtime-service-not-observed'
                ? 'start-native-runtime-service'
                : ($status === 'native-runtime-service-not-running'
                    ? 'launch-native-runtime-service'
                    : ($status === 'native-runtime-service-observation-incomplete'
                    ? 'inspect-native-runtime-service-artifacts'
                    : ($ownershipState === 'bus-name-owned'
                        ? 'runtime-service-active'
                        : 'materialize-native-bus-ownership'))));

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-observation',
            'status' => $status,
            'observed' => $observed,
            'exit_code' => $ownershipState === 'bus-name-owned' ? 0 : 3,
            'startup_acknowledged' => $startupAcknowledged,
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'service_definition' => $serviceDefinition,
            'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
            'ownership_state' => $ownershipState,
            'artifacts' => [
                'ready_marker' => $readyMarker,
                'status_file' => $statusFile,
                'pid_file' => $pidFile,
            ],
            'startup_report' => $startupReport,
            'pid' => $pidFile['pid'] ?? null,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
        ];
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createNativeRuntimeCouplingReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();
        $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
        $serviceDefinition = $this->createNativeRuntimeServiceDefinition($runtimeDiagnosis, $env);
        $selectedBackend = is_string($serviceDefinition['selected_backend'] ?? null)
            ? $serviceDefinition['selected_backend']
            : 'contract-pending';
        $recoveryContext = $this->createNativeRuntimeRecoveryContext($ready, $selectedBackend, $env);
        $nativeBackendCandidate = $recoveryContext['native_backend_candidate'];
        $eligible = ($serviceDefinition['eligible'] ?? false) === true;
        $driverExecution = $eligible ? $this->executeNativeRuntimeCouplingDriver($serviceDefinition, $env) : null;
        $described = is_array($driverExecution);
        $compatible = ($driverExecution['compatible'] ?? false) === true;
        $driverBlockingReason = is_string($driverExecution['blocking_reason'] ?? null)
            && $driverExecution['blocking_reason'] !== ''
                ? $driverExecution['blocking_reason']
                : null;
        $status = !$eligible
            ? 'native-runtime-coupling-blocked'
            : ($compatible
                ? 'native-runtime-coupling-ready'
                : ($described
                    ? 'native-runtime-coupling-incompatible'
                    : 'native-runtime-coupling-failed'));
        $blockingReason = !$eligible
            ? (is_string($serviceDefinition['blocking_reason'] ?? null)
                ? $serviceDefinition['blocking_reason']
                : 'native-runtime-service-unavailable')
            : ($compatible ? null : ($driverBlockingReason ?? 'native-runtime-coupling-not-described'));
        $nextAction = !$eligible
            ? $recoveryContext['next_action']
            : ($compatible ? 'materialize-native-bus-ownership' : 'inspect-native-runtime-coupling');

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-coupling',
            'status' => $status,
            'described' => $described,
            'compatible' => $compatible,
            'exit_code' => !$eligible ? 3 : ($compatible ? 0 : 1),
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'service_definition' => $serviceDefinition,
            'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
            'native_backend_candidate' => $nativeBackendCandidate,
            'coupling_driver' => is_array($driverExecution['driver'] ?? null) ? $driverExecution['driver'] : null,
            'service_lifecycle' => is_array($driverExecution['service_lifecycle'] ?? null)
                ? $driverExecution['service_lifecycle']
                : [],
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
        ];
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createNativeBusOwnershipReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();
        $serviceDefinition = $this->createNativeRuntimeServiceDefinition($runtimeDiagnosis, $env);
        $observation = $this->createNativeRuntimeServiceObservationReport($runtimeDiagnosis, $env);
        $eligible = ($serviceDefinition['eligible'] ?? false) === true;
        $observed = ($observation['observed'] ?? false) === true;
        $attempted = $eligible && $observed;
        $phaseOverridesConfigured = $this->hasExplicitNativeRuntimePhaseOverrides($env);
        $driverExecution = $attempted && !$phaseOverridesConfigured
            ? $this->executeNativeRuntimeOwnershipDriver($serviceDefinition, $env)
            : null;
        $requestNameState = $phaseOverridesConfigured
            ? $this->resolveNativeRuntimePhaseState(
                $env,
                'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE',
                ['not-attempted', 'pending', 'owned', 'failed'],
                $attempted ? 'pending' : 'not-attempted'
            )
            : $this->resolveDriverLifecycleState(
                $driverExecution,
                'request_name_state',
                ['not-attempted', 'pending', 'owned', 'failed'],
                $attempted ? 'pending' : 'not-attempted'
            );
        $registerObjectState = $phaseOverridesConfigured
            ? $this->resolveNativeRuntimePhaseState(
                $env,
                'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE',
                ['not-attempted', 'pending', 'registered', 'failed', 'skipped'],
                !$attempted ? 'not-attempted' : ($requestNameState === 'owned' ? 'pending' : 'skipped')
            )
            : $this->resolveDriverLifecycleState(
                $driverExecution,
                'register_object_state',
                ['not-attempted', 'pending', 'registered', 'failed', 'skipped'],
                !$attempted ? 'not-attempted' : ($requestNameState === 'owned' ? 'pending' : 'skipped')
            );
        $waitLoopState = $phaseOverridesConfigured
            ? $this->resolveNativeRuntimePhaseState(
                $env,
                'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE',
                ['not-attempted', 'pending', 'running', 'failed', 'skipped'],
                !$attempted ? 'not-attempted' : ($requestNameState === 'owned' && $registerObjectState === 'registered' ? 'pending' : 'skipped')
            )
            : $this->resolveDriverLifecycleState(
                $driverExecution,
                'wait_loop_state',
                ['not-attempted', 'pending', 'running', 'failed', 'skipped'],
                !$attempted ? 'not-attempted' : ($requestNameState === 'owned' && $registerObjectState === 'registered' ? 'pending' : 'skipped')
            );
        $hasFailure = in_array('failed', [$requestNameState, $registerObjectState, $waitLoopState], true);
        $busOwned = $requestNameState === 'owned'
            && $registerObjectState === 'registered'
            && $waitLoopState === 'running';
        $executionMode = !$attempted
            ? 'blocked-before-ownership'
            : ($phaseOverridesConfigured
                ? 'phase-overrides'
                : ($driverExecution !== null ? 'native-driver' : 'contract-default'));
        $ownershipState = !$attempted
            ? 'blocked-before-ownership'
            : ($hasFailure
                ? 'native-runtime-ownership-failed'
                : ($busOwned ? 'bus-name-owned' : 'pending-bus-name-ownership'));
        $status = !$eligible
            ? 'native-runtime-service-blocked'
            : (!$observed
                ? 'native-runtime-bus-ownership-blocked'
                : ($hasFailure
                    ? 'native-runtime-bus-ownership-failed'
                    : ($busOwned
                        ? 'native-runtime-bus-owned'
                        : 'native-runtime-bus-ownership-pending')));
        $driverBlockingReason = is_string($driverExecution['blocking_reason'] ?? null)
            && $driverExecution['blocking_reason'] !== ''
                ? $driverExecution['blocking_reason']
                : null;
        $blockingReason = !$eligible
            ? (is_string($serviceDefinition['blocking_reason'] ?? null)
                ? $serviceDefinition['blocking_reason']
                : 'native-runtime-service-unavailable')
            : (!$observed
                ? 'native-runtime-service-not-observed'
                : ($driverBlockingReason
                    ?? ($requestNameState === 'failed'
                        ? 'native-runtime-request-name-failed'
                        : ($registerObjectState === 'failed'
                            ? 'native-runtime-register-object-failed'
                            : ($waitLoopState === 'failed'
                                ? 'native-runtime-wait-loop-failed'
                                : ($busOwned ? null : 'native-runtime-bus-ownership-pending'))))));
        $nextAction = !$eligible
            ? (is_string($serviceDefinition['next_action'] ?? null)
                ? $serviceDefinition['next_action']
                : 'complete-native-runtime-backend')
            : (!$observed
                ? 'observe-native-runtime-service-state'
                : ($busOwned
                    ? 'runtime-service-active'
                    : ($hasFailure
                        ? 'inspect-native-runtime-ownership-report'
                        : 'observe-native-runtime-service-state')));

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-ownership',
            'status' => $status,
            'attempted' => $attempted,
            'exit_code' => !$attempted ? 3 : ($busOwned ? 0 : ($hasFailure ? 1 : 3)),
            'startup_acknowledged' => ($observation['startup_acknowledged'] ?? false) === true,
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'service_definition' => $serviceDefinition,
            'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
            'service_observation' => $observation,
            'execution_mode' => $executionMode,
            'ownership_driver' => is_array($driverExecution['driver'] ?? null) ? $driverExecution['driver'] : null,
            'ownership_state' => $ownershipState,
            'service_lifecycle' => [
                'request_name' => [
                    'required' => true,
                    'state' => $requestNameState,
                ],
                'register_object' => [
                    'required' => true,
                    'state' => $registerObjectState,
                ],
                'wait_loop' => [
                    'required' => true,
                    'state' => $waitLoopState,
                ],
            ],
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    public function persistNativeRuntimeStartupArtifacts(array $report): void
    {
        if (($report['startup_acknowledged'] ?? false) !== true) {
            return;
        }

        $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
        $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
        $readyMarkerPath = $this->optionalArtifactPath($artifacts, 'ready_marker_path');
        $statusFilePath = $this->optionalArtifactPath($artifacts, 'status_file_path');
        $pidFilePath = $this->optionalArtifactPath($artifacts, 'pid_file_path');

        if ($readyMarkerPath !== null) {
            $this->writeArtifactFile($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
        }

        if ($statusFilePath !== null) {
            $statusPayload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($statusPayload !== false) {
                $this->writeArtifactFile($statusFilePath, $statusPayload . PHP_EOL);
            }
        }

        if ($pidFilePath !== null) {
            $this->writeArtifactFile($pidFilePath, "0\n");
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    public function persistNativeRuntimeOwnershipArtifacts(array $report): void
    {
        if (($report['attempted'] ?? false) !== true) {
            return;
        }

        $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
        $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
        $readyMarkerPath = $this->optionalArtifactPath($artifacts, 'ready_marker_path');
        $statusFilePath = $this->optionalArtifactPath($artifacts, 'status_file_path');
        $pidFilePath = $this->optionalArtifactPath($artifacts, 'pid_file_path');

        if ($readyMarkerPath !== null) {
            $readyMarkerContents = ($report['ownership_state'] ?? null) === 'bus-name-owned'
                ? "native-runtime-bus-owned\n"
                : "native-runtime-service-started\n";
            $this->writeArtifactFile($readyMarkerPath, $readyMarkerContents);
        }

        if ($statusFilePath !== null) {
            $statusPayload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($statusPayload !== false) {
                $this->writeArtifactFile($statusFilePath, $statusPayload . PHP_EOL);
            }
        }

        if ($pidFilePath !== null && !is_file($pidFilePath)) {
            $this->writeArtifactFile($pidFilePath, "0\n");
        }
    }

    /**
     * @return array<string, string>
     */
    private function captureEnvironment(): array
    {
        $environment = getenv();
        if (!is_array($environment)) {
            return [];
        }

        $captured = [];
        foreach ($environment as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $captured[$key] = $value;
            }
        }

        return $captured;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveSelectedBackend(array $environment): string
    {
        $selectedBackend = $environment['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'contract-pending';

        return is_string($selectedBackend) && trim($selectedBackend) !== ''
            ? trim($selectedBackend)
            : 'contract-pending';
    }

    /**
     * @return array<string, mixed>
     */
    private function createContractPendingReport(bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'contract-pending',
            'status' => 'pending-runtime-adapter',
            'materialized' => false,
            'transport' => 'contract-only',
            'ownership_model' => 'not-materialized',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? 'runtime-backend-not-configured' : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createExperimentalEmbeddedRuntimeReport(bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'experimental-embedded-runtime',
            'status' => $ready
                ? 'experimental-runtime-backend-materialized'
                : 'pending-runtime-adapter',
            'materialized' => $ready,
            'transport' => 'stdio-activation-contract',
            'ownership_model' => 'process-local-activation-contract',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? null : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createNativeDbusExtensionReport(bool $ready, array $environment): array
    {
        $className = $this->resolveNativeDbusClassName($environment);
        $classResolutionSource = $this->resolveNativeDbusClassResolutionSource($environment, $className);
        $extensionLoaded = $this->resolveNativeDbusExtensionLoaded($environment);
        $hasServiceClass = $className !== null;
        $hasBusSystemConstant = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && defined($resolvedClassName . '::BUS_SYSTEM'),
            $className
        );
        $hasRequestNameMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'requestName'),
            $className
        );
        $hasRegisterObjectMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'registerObject'),
            $className
        );
        $hasWaitLoopMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'waitLoop'),
            $className
        );
        $missingApi = [];
        if (!$hasServiceClass) {
            $missingApi[] = 'dbus-service-class';
        }
        if (!$hasBusSystemConstant) {
            $missingApi[] = 'Dbus::BUS_SYSTEM';
        }
        if (!$hasRequestNameMethod) {
            $missingApi[] = 'requestName';
        }
        if (!$hasRegisterObjectMethod) {
            $missingApi[] = 'registerObject';
        }
        if (!$hasWaitLoopMethod) {
            $missingApi[] = 'waitLoop';
        }

        $compatibleForService = $hasServiceClass
            && $hasBusSystemConstant
            && $hasRequestNameMethod
            && $hasRegisterObjectMethod
            && $hasWaitLoopMethod;
        $materialized = $ready && $compatibleForService;
        $diagnosticBlockingReason = $compatibleForService
            ? null
            : ($hasServiceClass
                ? 'dbus-extension-api-incomplete'
                : 'dbus-extension-service-backend-unavailable');
        $diagnosticNextAction = $compatibleForService
            ? 'start-native-runtime-service'
            : ($hasServiceClass
                ? 'inspect-native-dbus-extension-api'
                : ($extensionLoaded ? 'inspect-native-dbus-service-class' : 'install-native-dbus-extension'));

        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'native-dbus-extension',
            'status' => $materialized
                ? 'native-runtime-backend-materialized'
                : 'pending-runtime-adapter',
            'materialized' => $materialized,
            'transport' => 'dbus-system-bus',
            'ownership_model' => 'native-php-dbus-extension',
            'supports_bus_ownership' => true,
            'native_runtime_api' => [
                'extension_name' => 'dbus',
                'extension_loaded' => $extensionLoaded,
                'class_name' => $className,
                'class_candidates' => ['Dbus', 'DBus'],
                'class_resolution_source' => $classResolutionSource,
                'class_available' => $hasServiceClass,
                'has_bus_system_constant' => $hasBusSystemConstant,
                'has_request_name_method' => $hasRequestNameMethod,
                'has_register_object_method' => $hasRegisterObjectMethod,
                'has_wait_loop_method' => $hasWaitLoopMethod,
                'compatible_for_service' => $compatibleForService,
                'missing_api' => $missingApi,
                'diagnostic_blocking_reason' => $diagnosticBlockingReason,
                'diagnostic_next_action' => $diagnosticNextAction,
                'service_contract' => [
                    'bus_name' => 'org.w4.System1',
                    'object_path' => '/org/w4/System1',
                    'interface' => 'org.w4.System1.Manager',
                    'required_methods' => [
                        'requestName',
                        'registerObject',
                        'waitLoop',
                    ],
                ],
            ],
            'blocking_reason' => !$ready
                ? 'missing-runtime-capabilities'
                : ($compatibleForService
                    ? null
                    : ($hasServiceClass
                        ? 'dbus-extension-api-incomplete'
                        : 'dbus-extension-service-backend-unavailable')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createUnsupportedBackendReport(string $selectedBackend, bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => $selectedBackend,
            'status' => $ready ? 'unsupported-runtime-backend' : 'pending-runtime-adapter',
            'materialized' => false,
            'transport' => 'unknown',
            'ownership_model' => 'unsupported',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready
                ? 'unsupported-runtime-backend'
                : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @param array<string, string> $environment
     * @return array{native_backend_candidate: array<string, mixed>, next_action: string}
     */
    private function createNativeRuntimeRecoveryContext(bool $ready, string $selectedBackend, array $environment): array
    {
        $nativeBackendCandidate = $this->createNativeDbusExtensionReport($ready, $environment);

        return [
            'native_backend_candidate' => $nativeBackendCandidate,
            'next_action' => $this->resolveNativeRuntimeRecoveryNextAction(
                $ready,
                $selectedBackend,
                $nativeBackendCandidate
            ),
        ];
    }

    /**
     * @param array<string, mixed> $nativeBackendCandidate
     */
    private function resolveNativeRuntimeRecoveryNextAction(
        bool $ready,
        string $selectedBackend,
        array $nativeBackendCandidate
    ): string {
        $nativeBackendCandidateCompatible = (($nativeBackendCandidate['native_runtime_api']['compatible_for_service'] ?? false) === true);
        if ($selectedBackend !== 'native-dbus-extension' && $nativeBackendCandidateCompatible) {
            return 'select-native-dbus-extension-backend';
        }

        $diagnosticNextAction = $nativeBackendCandidate['native_runtime_api']['diagnostic_next_action'] ?? null;
        if (is_string($diagnosticNextAction) && $diagnosticNextAction !== '') {
            return $diagnosticNextAction;
        }

        return $ready ? 'complete-native-runtime-backend' : 'materialize-runtime-adapter';
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeDbusClassName(array $environment): ?string
    {
        $overrideClassName = $environment['W4_SYSTEM_API_NATIVE_DBUS_CLASS'] ?? null;
        if (is_string($overrideClassName) && trim($overrideClassName) !== '') {
            return trim($overrideClassName);
        }

        foreach (['Dbus', 'DBus'] as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeDbusClassResolutionSource(array $environment, ?string $className): string
    {
        $overrideClassName = $environment['W4_SYSTEM_API_NATIVE_DBUS_CLASS'] ?? null;
        if (is_string($overrideClassName) && trim($overrideClassName) !== '') {
            return 'env-override';
        }

        return $className !== null ? 'runtime-detection' : 'unresolved';
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeDbusExtensionLoaded(array $environment): bool
    {
        $override = $this->resolveBoolOverride($environment, 'W4_SYSTEM_API_NATIVE_DBUS_EXTENSION_LOADED');
        if ($override !== null) {
            return $override;
        }

        return extension_loaded('dbus');
    }

    /**
     * @param array<string, string> $environment
     * @param callable(?string): bool $fallback
     */
    private function resolveNativeDbusFeatureAvailability(
        array $environment,
        string $overrideKey,
        callable $fallback,
        ?string $className
    ): bool {
        $override = $this->resolveBoolOverride($environment, $overrideKey);

        return $override ?? $fallback($className);
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveBoolOverride(array $environment, string $key): ?bool
    {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolvePathValue(array $environment, string $key, string $default): string
    {
        $value = $environment[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    /**
     * @param array<string, string> $environment
     */
    private function hasExplicitNativeRuntimePhaseOverrides(array $environment): bool
    {
        foreach ([
            'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE',
            'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE',
            'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE',
        ] as $key) {
            $value = $environment[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $environment
     * @param list<string> $allowedStates
     */
    private function resolveNativeRuntimePhaseState(
        array $environment,
        string $key,
        array $allowedStates,
        string $default
    ): string {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowedStates, true) ? $normalized : $default;
    }

    /**
     * @param array<string, mixed>|null $driverExecution
     * @param list<string> $allowedStates
     */
    private function resolveDriverLifecycleState(?array $driverExecution, string $field, array $allowedStates, string $default): string
    {
        $value = $driverExecution[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowedStates, true) ? $normalized : $default;
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, string> $environment
     * @return array<string, mixed>|null
     */
    private function executeNativeRuntimeOwnershipDriver(array $serviceDefinition, array $environment): ?array
    {
        $driverClassName = $this->resolveNativeRuntimeDriverClassName($environment)
            ?? NativeDbusExtensionOwnershipDriver::class;
        $bootstrapPath = $this->resolveNativeRuntimeDriverBootstrapPath($environment);
        if ($driverClassName === null && $bootstrapPath === null) {
            return null;
        }

        if ($bootstrapPath !== null && is_file($bootstrapPath)) {
            require_once $bootstrapPath;
        }

        if ($driverClassName === null) {
            return [
                'request_name_state' => 'failed',
                'register_object_state' => 'not-attempted',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-runtime-driver-class-not-configured',
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => null,
                ],
            ];
        }

        if (!class_exists($driverClassName)) {
            return [
                'request_name_state' => 'failed',
                'register_object_state' => 'not-attempted',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-runtime-driver-class-unavailable',
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => false,
                ],
            ];
        }

        try {
            $method = new \ReflectionMethod($driverClassName, 'materializeOwnership');
            $arguments = [$serviceDefinition];
            if ($method->getNumberOfParameters() >= 2) {
                $arguments[] = $environment;
            }
            $result = $method->isStatic()
                ? $method->invokeArgs(null, $arguments)
                : $method->invokeArgs(new $driverClassName(), $arguments);
        } catch (\ReflectionException) {
            return [
                'request_name_state' => 'failed',
                'register_object_state' => 'not-attempted',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-runtime-driver-method-unavailable',
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'request_name_state' => 'failed',
                'register_object_state' => 'not-attempted',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-runtime-driver-execution-failed',
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                    'error_message' => $exception->getMessage(),
                ],
            ];
        }

        if (!is_array($result)) {
            return [
                'request_name_state' => 'failed',
                'register_object_state' => 'not-attempted',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-runtime-driver-invalid-result',
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                ],
            ];
        }

        $driver = is_array($result['driver'] ?? null) ? $result['driver'] : [];
        $driver['mode'] = 'native-driver';
        $driver['bootstrap_path'] = $bootstrapPath;
        $driver['class_name'] = $driverClassName;
        $driver['available'] = true;
        $result['driver'] = $driver;

        return $result;
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, string> $environment
     * @return array<string, mixed>|null
     */
    private function executeNativeRuntimeCouplingDriver(array $serviceDefinition, array $environment): ?array
    {
        $driverClassName = $this->resolveNativeRuntimeDriverClassName($environment)
            ?? NativeDbusExtensionOwnershipDriver::class;
        $bootstrapPath = $this->resolveNativeRuntimeDriverBootstrapPath($environment);

        if ($bootstrapPath !== null && is_file($bootstrapPath)) {
            require_once $bootstrapPath;
        }

        if (!class_exists($driverClassName)) {
            return [
                'compatible' => false,
                'blocking_reason' => 'native-runtime-driver-class-unavailable',
                'service_lifecycle' => [],
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => false,
                ],
            ];
        }

        try {
            $method = new \ReflectionMethod($driverClassName, 'describeCoupling');
            $arguments = [$serviceDefinition];
            if ($method->getNumberOfParameters() >= 2) {
                $arguments[] = $environment;
            }
            $result = $method->isStatic()
                ? $method->invokeArgs(null, $arguments)
                : $method->invokeArgs(new $driverClassName(), $arguments);
        } catch (\ReflectionException) {
            return [
                'compatible' => false,
                'blocking_reason' => 'native-runtime-driver-method-unavailable',
                'service_lifecycle' => [],
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'compatible' => false,
                'blocking_reason' => 'native-runtime-driver-execution-failed',
                'service_lifecycle' => [],
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                    'error_message' => $exception->getMessage(),
                ],
            ];
        }

        if (!is_array($result)) {
            return [
                'compatible' => false,
                'blocking_reason' => 'native-runtime-driver-invalid-result',
                'service_lifecycle' => [],
                'driver' => [
                    'mode' => 'native-driver',
                    'bootstrap_path' => $bootstrapPath,
                    'class_name' => $driverClassName,
                    'available' => true,
                ],
            ];
        }

        $driver = is_array($result['driver'] ?? null) ? $result['driver'] : [];
        $driver['mode'] = 'native-driver';
        $driver['bootstrap_path'] = $bootstrapPath;
        $driver['class_name'] = $driverClassName;
        $driver['available'] = true;
        $result['driver'] = $driver;

        return $result;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeDriverClassName(array $environment): ?string
    {
        $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeDriverBootstrapPath(array $environment): ?string
    {
        $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeRuntimeArtifactsBasePath(array $environment): string
    {
        foreach (['W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR', 'TEMP', 'TMP'] as $key) {
            $value = $environment[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return rtrim(trim($value), "\\/");
            }
        }

        $currentDirectory = getcwd();
        if (is_string($currentDirectory) && trim($currentDirectory) !== '') {
            return rtrim(trim($currentDirectory), "\\/") . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'native-runtime';
        }

        $tempDirectory = sys_get_temp_dir();
        if (is_string($tempDirectory) && trim($tempDirectory) !== '') {
            return rtrim(trim($tempDirectory), "\\/");
        }

        return 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'native-runtime';
    }

    /**
     * @param array<string, mixed> $artifacts
     */
    private function optionalArtifactPath(array $artifacts, string $field): ?string
    {
        $value = $artifacts[$field] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function observeTextArtifact(?string $path): array
    {
        $contents = $this->readArtifactFile($path);

        return [
            'path' => $path,
            'exists' => $path !== null && is_file($path),
            'contents' => $contents === null ? null : trim($contents),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function observeJsonArtifact(?string $path): array
    {
        $contents = $this->readArtifactFile($path);
        $payload = null;
        $parseError = null;

        if ($contents !== null && trim($contents) !== '') {
            try {
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            } catch (\JsonException $exception) {
                $parseError = $exception->getMessage();
            }
        }

        return [
            'path' => $path,
            'exists' => $path !== null && is_file($path),
            'payload' => $payload,
            'parse_error' => $parseError,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function observePidArtifact(?string $path): array
    {
        $contents = $this->readArtifactFile($path);
        $trimmed = $contents === null ? null : trim($contents);
        $pid = is_string($trimmed) && preg_match('/^-?\d+$/', $trimmed) === 1
            ? (int) $trimmed
            : null;

        return [
            'path' => $path,
            'exists' => $path !== null && is_file($path),
            'pid' => $pid,
            'raw_value' => $trimmed,
        ];
    }

    private function readArtifactFile(?string $path): ?string
    {
        if ($path === null || !is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    private function writeArtifactFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if ($directory !== '' && !is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio de artefacto %s', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el artefacto %s', $path));
        }
    }
}
