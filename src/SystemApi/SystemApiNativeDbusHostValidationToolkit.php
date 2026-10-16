<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiNativeDbusHostValidationToolkit
{
    private SystemApiDbusRuntimeAdapter $runtimeAdapter;

    public function __construct(
        private readonly string $rootDir
    ) {
        $this->runtimeAdapter = new SystemApiDbusRuntimeAdapter($rootDir);
    }

    /**
     * @param array<string, string> $environmentOverrides
     * @return array<string, mixed>
     */
    public function runValidation(
        string $evidenceDir,
        bool $attemptOwnership = true,
        array $environmentOverrides = []
    ): array {
        $normalizedEvidenceDir = $this->normalizePath($evidenceDir);
        $artifactsDir = $this->normalizePath(
            $environmentOverrides['W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR']
                ?? ($normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'runtime-artifacts')
        );
        $reportPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-runtime-validation-report.json';
        $stepsDir = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'steps';

        $defaultOverrides = [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $artifactsDir,
        ];
        $effectiveOverrides = array_merge($defaultOverrides, $environmentOverrides);

        $startedAt = gmdate('c');
        $steps = $this->withEnvironmentOverrides($effectiveOverrides, function () use ($attemptOwnership): array {
            $diagnosis = $this->decodeJson($this->runtimeAdapter->diagnoseRuntime());
            $serviceDefinition = $this->decodeJson($this->runtimeAdapter->describeNativeRuntimeService());
            $startup = $this->decodeJson($this->runtimeAdapter->startNativeRuntimeService());
            $observationAfterStartup = $this->decodeJson($this->runtimeAdapter->observeNativeRuntimeServiceState());
            $coupling = $this->decodeJson($this->runtimeAdapter->describeNativeRuntimeCoupling());
            $ownership = $attemptOwnership
                ? $this->decodeJson($this->runtimeAdapter->materializeNativeBusOwnership())
                : null;
            $observationAfterOwnership = $attemptOwnership
                ? $this->decodeJson($this->runtimeAdapter->observeNativeRuntimeServiceState())
                : null;

            return [
                'diagnosis' => $diagnosis,
                'service_definition' => $serviceDefinition,
                'startup' => $startup,
                'observation_after_startup' => $observationAfterStartup,
                'coupling' => $coupling,
                'ownership' => $ownership,
                'observation_after_ownership' => $observationAfterOwnership,
            ];
        });

        $this->ensureDirectory($stepsDir);
        $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '01-diagnosis.json', $steps['diagnosis']);
        $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '02-service-definition.json', $steps['service_definition']);
        $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '03-startup.json', $steps['startup']);
        $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '04-observation-after-startup.json', $steps['observation_after_startup']);
        $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '05-coupling.json', $steps['coupling']);
        if (is_array($steps['ownership'])) {
            $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '06-ownership.json', $steps['ownership']);
        }
        if (is_array($steps['observation_after_ownership'])) {
            $this->writeJsonFile($stepsDir . DIRECTORY_SEPARATOR . '07-observation-after-ownership.json', $steps['observation_after_ownership']);
        }

        $finalSnapshot = is_array($steps['ownership']) ? $steps['ownership'] : $steps['coupling'];
        $finalObservation = is_array($steps['observation_after_ownership'])
            ? $steps['observation_after_ownership']
            : $steps['observation_after_startup'];
        $ownershipValidated = is_array($steps['ownership'])
            && (($steps['ownership']['ownership_state'] ?? null) === 'bus-name-owned')
            && (($steps['ownership']['exit_code'] ?? 3) === 0);
        $couplingReady = (($steps['coupling']['compatible'] ?? false) === true)
            && (($steps['coupling']['status'] ?? null) === 'native-runtime-coupling-ready');
        $startupReady = ($steps['startup']['startup_acknowledged'] ?? false) === true;
        $hasFailure = in_array(
            1,
            array_filter([
                $steps['coupling']['exit_code'] ?? null,
                is_array($steps['ownership']) ? ($steps['ownership']['exit_code'] ?? null) : null,
            ], static fn (mixed $value): bool => is_int($value)),
            true
        );

        $status = $ownershipValidated
            ? 'ownership-validated'
            : ($hasFailure
                ? 'validation-failed'
                : ($couplingReady
                    ? ($attemptOwnership ? 'ownership-pending' : 'ready-for-real-ownership')
                    : ($startupReady ? 'runtime-started-pending-ownership' : 'blocked')));
        $exitCode = $ownershipValidated || (!$attemptOwnership && $couplingReady)
            ? 0
            : ($hasFailure ? 1 : 3);

        $report = [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-validation-report',
            'status' => $status,
            'exit_code' => $exitCode,
            'attempted_ownership' => $attemptOwnership,
            'root_dir' => $this->rootDir,
            'evidence_dir' => $normalizedEvidenceDir,
            'report_path' => $reportPath,
            'artifacts_dir' => $artifactsDir,
            'runtime_backend' => $effectiveOverrides['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'native-dbus-extension',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'started_at' => $startedAt,
            'completed_at' => gmdate('c'),
            'ownership_validated' => $ownershipValidated,
            'coupling_ready' => $couplingReady,
            'startup_acknowledged' => $startupReady,
            'blocking_reason' => $this->resolvePrimaryValue(
                $finalSnapshot,
                $finalObservation,
                $steps['startup'],
                $steps['service_definition']
            ),
            'next_action' => $this->resolvePrimaryNextAction(
                $finalSnapshot,
                $finalObservation,
                $steps['startup'],
                $steps['service_definition']
            ),
            'recommended_next_actions' => $this->collectRecommendedNextActions(
                $steps['service_definition'],
                $steps['startup'],
                $steps['observation_after_startup'],
                $steps['coupling'],
                $steps['ownership'],
                $steps['observation_after_ownership']
            ),
            'environment_overrides' => $effectiveOverrides,
            'steps' => $steps,
            'step_files' => [
                'diagnosis' => $stepsDir . DIRECTORY_SEPARATOR . '01-diagnosis.json',
                'service_definition' => $stepsDir . DIRECTORY_SEPARATOR . '02-service-definition.json',
                'startup' => $stepsDir . DIRECTORY_SEPARATOR . '03-startup.json',
                'observation_after_startup' => $stepsDir . DIRECTORY_SEPARATOR . '04-observation-after-startup.json',
                'coupling' => $stepsDir . DIRECTORY_SEPARATOR . '05-coupling.json',
                'ownership' => is_array($steps['ownership'])
                    ? $stepsDir . DIRECTORY_SEPARATOR . '06-ownership.json'
                    : null,
                'observation_after_ownership' => is_array($steps['observation_after_ownership'])
                    ? $stepsDir . DIRECTORY_SEPARATOR . '07-observation-after-ownership.json'
                    : null,
            ],
        ];

        $this->writeJsonFile($reportPath, $report);

        return $report;
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderSummaryText(array $report): string
    {
        $lines = [
            sprintf('status: %s', $report['status'] ?? 'unknown'),
            sprintf('exit_code: %s', (string) ($report['exit_code'] ?? '3')),
            sprintf('ownership_validated: %s', (($report['ownership_validated'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('coupling_ready: %s', (($report['coupling_ready'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('startup_acknowledged: %s', (($report['startup_acknowledged'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('next_action: %s', (string) ($report['next_action'] ?? 'n/a')),
            sprintf('blocking_reason: %s', (string) ($report['blocking_reason'] ?? 'n/a')),
            sprintf('report_path: %s', (string) ($report['report_path'] ?? 'n/a')),
        ];

        $recommended = $report['recommended_next_actions'] ?? [];
        if (is_array($recommended) && $recommended !== []) {
            $lines[] = 'recommended_next_actions: ' . implode(', ', array_map(
                static fn (mixed $value): string => is_string($value) ? $value : '',
                $recommended
            ));
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function normalizePath(string $path): string
    {
        return rtrim(trim($path), "\\/");
    }

    /**
     * @param callable(): array<string, mixed> $callback
     * @param array<string, string> $overrides
     * @return array<string, mixed>
     */
    private function withEnvironmentOverrides(array $overrides, callable $callback): array
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $current = getenv($key);
            $original[$key] = $current === false ? null : $current;
            putenv(sprintf('%s=%s', $key, $value));
        }

        try {
            return $callback();
        } finally {
            foreach ($original as $key => $value) {
                if ($value === null) {
                    putenv($key);
                    continue;
                }

                putenv(sprintf('%s=%s', $key, $value));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $payload): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('El payload JSON de validacion es invalido: %s', $exception->getMessage()));
        }

        return $decoded;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio %s', $path));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonFile(string $path, array $payload): void
    {
        $this->ensureDirectory(dirname($path));
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || file_put_contents($path, $encoded . PHP_EOL) === false) {
            throw new ValidationError(sprintf('No se pudo escribir %s', $path));
        }
    }

    /**
     * @param array<string, mixed> ...$snapshots
     */
    private function resolvePrimaryValue(array ...$snapshots): ?string
    {
        foreach ($snapshots as $snapshot) {
            $value = $snapshot['blocking_reason'] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> ...$snapshots
     */
    private function resolvePrimaryNextAction(array ...$snapshots): ?string
    {
        foreach ($snapshots as $snapshot) {
            $value = $snapshot['next_action'] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null ...$snapshots
     * @return list<string>
     */
    private function collectRecommendedNextActions(?array ...$snapshots): array
    {
        $actions = [];
        foreach ($snapshots as $snapshot) {
            if (!is_array($snapshot)) {
                continue;
            }

            $nextAction = $snapshot['next_action'] ?? null;
            if (is_string($nextAction) && trim($nextAction) !== '') {
                $actions[] = trim($nextAction);
            }
        }

        return array_values(array_unique($actions));
    }
}
