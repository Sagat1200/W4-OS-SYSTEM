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
        $summaryPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-runtime-validation-summary.md';
        $preparationShellScriptPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-runtime-validation-prepare.sh';
        $preparationChecklistPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-runtime-validation-preparation.txt';
        $handoffPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-runtime-validation-handoff.md';
        $nativeDbusBuildEvidenceArtifactPath = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json';
        $stepsDir = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'steps';

        $defaultOverrides = [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $artifactsDir,
        ];
        $nativeDbusBuildEvidencePath = $this->resolveNativeDbusBuildEvidencePath($normalizedEvidenceDir, $environmentOverrides);
        $materializedNativeDbusBuildEvidenceArtifactPath = $this->materializeNativeDbusBuildEvidenceArtifact(
            $nativeDbusBuildEvidenceArtifactPath,
            $nativeDbusBuildEvidencePath,
            $environmentOverrides
        );
        if ($materializedNativeDbusBuildEvidenceArtifactPath !== null) {
            $defaultOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_JSON_PATH'] = $materializedNativeDbusBuildEvidenceArtifactPath;
        }
        if ($nativeDbusBuildEvidencePath !== null) {
            $defaultOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH'] = $nativeDbusBuildEvidencePath;
        }
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
        $environment = is_array($steps['diagnosis']['environment'] ?? null) ? $steps['diagnosis']['environment'] : [];
        $osFamily = $this->resolvePreparationTargetOsFamily($environment, $effectiveOverrides);
        $targetRepoRoot = $this->resolvePreparationTargetRepoRoot($effectiveOverrides, $osFamily);
        $hostCompatibility = $this->evaluateHostCompatibility($steps['diagnosis'], $targetRepoRoot);
        $hostCompatible = (($hostCompatibility['compatible'] ?? true) === true);
        $nativeDbusExtensionInstallability = $this->evaluateNativeDbusExtensionInstallability($steps['diagnosis'], $targetRepoRoot);
        $nativeDbusExtensionBlocked = (($nativeDbusExtensionInstallability['blocked'] ?? false) === true);
        if (!$hostCompatible) {
            $status = 'blocked';
            $exitCode = 3;
        }
        if ($hostCompatible && $nativeDbusExtensionBlocked) {
            $status = 'blocked';
            $exitCode = 3;
        }
        $preparationPlan = $this->createPreparationPlan(
            $steps['diagnosis'],
            $steps['service_definition'],
            $steps['startup'],
            $steps['coupling'],
            $steps['ownership'],
            $hostCompatibility,
            $nativeDbusExtensionInstallability,
            $effectiveOverrides,
            $osFamily,
            $targetRepoRoot,
            $attemptOwnership,
            $ownershipValidated,
            $couplingReady
        );
        $blockingReason = !$hostCompatible
            ? (is_string($hostCompatibility['blocking_reason'] ?? null) ? $hostCompatibility['blocking_reason'] : null)
            : ($nativeDbusExtensionBlocked
                ? (is_string($nativeDbusExtensionInstallability['blocking_reason'] ?? null) ? $nativeDbusExtensionInstallability['blocking_reason'] : null)
                : $this->resolvePrimaryValue(
                $finalSnapshot,
                $finalObservation,
                $steps['startup'],
                $steps['service_definition']
            ));
        $nextAction = !$hostCompatible
            ? (is_string($hostCompatibility['next_action'] ?? null) ? $hostCompatibility['next_action'] : null)
            : ($nativeDbusExtensionBlocked
                ? (is_string($nativeDbusExtensionInstallability['next_action'] ?? null) ? $nativeDbusExtensionInstallability['next_action'] : null)
                : $this->resolvePrimaryNextAction(
                $finalSnapshot,
                $finalObservation,
                $steps['startup'],
                $steps['service_definition']
            ));
        $recommendedNextActions = array_values(array_unique(array_filter(array_merge(
            is_array($hostCompatibility['recommended_next_actions'] ?? null)
                ? $hostCompatibility['recommended_next_actions']
                : [],
            is_array($nativeDbusExtensionInstallability['recommended_next_actions'] ?? null)
                ? $nativeDbusExtensionInstallability['recommended_next_actions']
                : [],
            $this->collectRecommendedNextActions(
                $steps['service_definition'],
                $steps['startup'],
                $steps['observation_after_startup'],
                $steps['coupling'],
                $steps['ownership'],
                $steps['observation_after_ownership']
            )
        ), static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));

        $report = [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-native-runtime-validation-report',
            'status' => $status,
            'exit_code' => $exitCode,
            'attempted_ownership' => $attemptOwnership,
            'root_dir' => $this->rootDir,
            'evidence_dir' => $normalizedEvidenceDir,
            'report_path' => $reportPath,
            'summary_path' => $summaryPath,
            'preparation_shell_script_path' => $preparationShellScriptPath,
            'preparation_checklist_path' => $preparationChecklistPath,
            'handoff_path' => $handoffPath,
            'native_dbus_extension_build_evidence_path' => $materializedNativeDbusBuildEvidenceArtifactPath,
            'artifacts_dir' => $artifactsDir,
            'runtime_backend' => $effectiveOverrides['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'native-dbus-extension',
            'preparation_target_os_family' => $osFamily,
            'preparation_target_repo_root' => $targetRepoRoot,
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'started_at' => $startedAt,
            'completed_at' => gmdate('c'),
            'ownership_validated' => $ownershipValidated,
            'coupling_ready' => $couplingReady,
            'startup_acknowledged' => $startupReady,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
            'recommended_next_actions' => $recommendedNextActions,
            'host_compatibility' => $hostCompatibility,
            'native_dbus_extension_installability' => $nativeDbusExtensionInstallability,
            'preparation_plan' => $preparationPlan,
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
        $this->writeTextFile($summaryPath, $this->renderMarkdownSummary($report));
        $this->writePreparationArtifacts(
            $preparationShellScriptPath,
            $preparationChecklistPath,
            $preparationPlan,
            $osFamily
        );
        $this->writeTextFile(
            $handoffPath,
            $this->renderHandoffInstructions($report)
        );

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

        $preparationPlan = $report['preparation_plan'] ?? [];
        if (is_array($preparationPlan) && $preparationPlan !== []) {
            $lines[] = 'preparation_plan_steps: ' . (string) count($preparationPlan);
        }

        $lines[] = sprintf('summary_path: %s', (string) ($report['summary_path'] ?? 'n/a'));
        $lines[] = sprintf('preparation_shell_script_path: %s', (string) ($report['preparation_shell_script_path'] ?? 'n/a'));
        $lines[] = sprintf('preparation_checklist_path: %s', (string) ($report['preparation_checklist_path'] ?? 'n/a'));
        $lines[] = sprintf('handoff_path: %s', (string) ($report['handoff_path'] ?? 'n/a'));

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderMarkdownSummary(array $report): string
    {
        $lines = [
            '# Native D-Bus Runtime Validation',
            '',
            sprintf('- Status: `%s`', (string) ($report['status'] ?? 'unknown')),
            sprintf('- Exit code: `%s`', (string) ($report['exit_code'] ?? '3')),
            sprintf('- Ownership validated: `%s`', (($report['ownership_validated'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('- Coupling ready: `%s`', (($report['coupling_ready'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('- Startup acknowledged: `%s`', (($report['startup_acknowledged'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('- Preparation target OS family: `%s`', (string) ($report['preparation_target_os_family'] ?? 'n/a')),
            sprintf('- Preparation target repo root: `%s`', (string) ($report['preparation_target_repo_root'] ?? 'n/a')),
            sprintf('- Host compatibility: `%s`', (($report['host_compatibility']['compatible'] ?? true) === true) ? 'compatible' : 'blocked'),
            sprintf('- Native D-Bus extension installation: `%s`', (string) ($report['native_dbus_extension_installability']['state'] ?? 'unknown')),
            sprintf('- Blocking reason: `%s`', (string) ($report['blocking_reason'] ?? 'n/a')),
            sprintf('- Next action: `%s`', (string) ($report['next_action'] ?? 'n/a')),
            sprintf('- Report path: `%s`', (string) ($report['report_path'] ?? 'n/a')),
            sprintf('- Native D-Bus build evidence path: `%s`', (string) ($report['native_dbus_extension_build_evidence_path'] ?? 'n/a')),
            sprintf('- Handoff path: `%s`', (string) ($report['handoff_path'] ?? 'n/a')),
            sprintf('- Preparation shell script: `%s`', (string) ($report['preparation_shell_script_path'] ?? 'n/a')),
            sprintf('- Preparation checklist: `%s`', (string) ($report['preparation_checklist_path'] ?? 'n/a')),
            '',
            '## Host Compatibility',
            '',
            sprintf('- Detected OS family: `%s`', (string) ($report['host_compatibility']['os_family'] ?? 'n/a')),
            sprintf('- Detected PHP version: `%s`', (string) ($report['host_compatibility']['php_version'] ?? 'n/a')),
            sprintf('- Required PHP constraint: `%s`', (string) ($report['host_compatibility']['required_php_constraint'] ?? 'n/a')),
            sprintf('- Required PHP version: `%s`', (string) ($report['host_compatibility']['required_php_version'] ?? 'n/a')),
            '',
            '## Native D-Bus Extension Installation',
            '',
            sprintf('- Installation state: `%s`', (string) ($report['native_dbus_extension_installability']['state'] ?? 'unknown')),
            sprintf('- PECL available: `%s`', (($report['native_dbus_extension_installability']['pecl_available'] ?? false) === true) ? 'yes' : 'no'),
            sprintf('- Installation build phase: `%s`', (string) ($report['native_dbus_extension_installability']['build_phase'] ?? 'unknown')),
            sprintf('- Installation blocking reason: `%s`', (string) ($report['native_dbus_extension_installability']['blocking_reason'] ?? 'n/a')),
            sprintf('- Installation next action: `%s`', (string) ($report['native_dbus_extension_installability']['next_action'] ?? 'n/a')),
            sprintf('- Installation reason: `%s`', (string) ($report['native_dbus_extension_installability']['reason'] ?? 'n/a')),
            sprintf('- Installation failure signature: `%s`', (string) ($report['native_dbus_extension_installability']['failure_signature'] ?? 'n/a')),
            sprintf('- Installation log path: `%s`', (string) ($report['native_dbus_extension_installability']['log_path'] ?? 'n/a')),
            '',
            '## Recommended Next Actions',
            '',
        ];

        $recommended = $report['recommended_next_actions'] ?? [];
        if (is_array($recommended) && $recommended !== []) {
            foreach ($recommended as $action) {
                if (is_string($action) && trim($action) !== '') {
                    $lines[] = sprintf('- `%s`', trim($action));
                }
            }
        } else {
            $lines[] = '- None';
        }

        $lines[] = '';
        $lines[] = '## Preparation Plan';
        $lines[] = '';

        $preparationPlan = $report['preparation_plan'] ?? [];
        if (is_array($preparationPlan) && $preparationPlan !== []) {
            foreach ($preparationPlan as $step) {
                if (!is_array($step)) {
                    continue;
                }

                $lines[] = sprintf(
                    '1. `%s` - %s',
                    (string) ($step['action'] ?? 'unknown'),
                    (string) ($step['description'] ?? 'Sin descripcion')
                );

                $commands = $step['commands'] ?? null;
                if (is_array($commands) && $commands !== []) {
                    $lines[] = '';
                    $lines[] = '```bash';
                    foreach ($commands as $command) {
                        if (is_string($command) && trim($command) !== '') {
                            $lines[] = trim($command);
                        }
                    }
                    $lines[] = '```';
                    $lines[] = '';
                }
            }
        } else {
            $lines[] = '1. `validation-complete` - No additional preparation steps are currently required.';
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderHandoffInstructions(array $report): string
    {
        $targetOsFamily = (string) ($report['preparation_target_os_family'] ?? 'Unknown');
        $targetRepoRoot = (string) ($report['preparation_target_repo_root'] ?? '');
        $rerunCommands = $this->buildRerunCommands($targetOsFamily, $targetRepoRoot);
        $verificationCommands = $this->buildVerificationCommands($targetOsFamily);
        $lines = [
            '# Native D-Bus Runtime Handoff',
            '',
            sprintf('- Target OS family: `%s`', $targetOsFamily),
            sprintf('- Target repo root: `%s`', $targetRepoRoot !== '' ? $targetRepoRoot : 'set-this-before-running'),
            sprintf('- Current status: `%s`', (string) ($report['status'] ?? 'unknown')),
            sprintf('- Blocking reason: `%s`', (string) ($report['blocking_reason'] ?? 'n/a')),
            sprintf('- Preparation script: `%s`', (string) ($report['preparation_shell_script_path'] ?? 'n/a')),
            sprintf('- Preparation checklist: `%s`', (string) ($report['preparation_checklist_path'] ?? 'n/a')),
            '',
            '## Target Host Workflow',
            '',
            '1. Prepare the target host with the generated checklist or shell script.',
            '2. Re-run the native validation commands from the repository root on the target host.',
            '3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.',
            '',
            '## Re-run Commands',
            '',
            '```bash',
        ];

        foreach ($rerunCommands as $command) {
            $lines[] = $command;
        }

        $lines[] = '```';
        $lines[] = '';
        $lines[] = '## Post-Install Verification';
        $lines[] = '';
        $lines[] = '```bash';

        foreach ($verificationCommands as $command) {
            $lines[] = $command;
        }

        $lines[] = '```';
        $lines[] = '';

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

    private function writeTextFile(string $path, string $content): void
    {
        $this->ensureDirectory(dirname($path));
        if (file_put_contents($path, $content) === false) {
            throw new ValidationError(sprintf('No se pudo escribir %s', $path));
        }
    }

    /**
     * @param list<array<string, mixed>> $preparationPlan
     */
    private function writePreparationArtifacts(
        string $shellScriptPath,
        string $checklistPath,
        array $preparationPlan,
        string $osFamily
    ): void {
        $checklistLines = [
            'Native D-Bus Runtime Preparation Checklist',
            sprintf('OS family: %s', $osFamily),
            '',
        ];
        $shellLines = [
            '#!/usr/bin/env bash',
            'set -euo pipefail',
            '',
            sprintf('# Native D-Bus runtime preparation helper for %s', $osFamily),
            '# Review each step before executing it on the target host.',
            '',
        ];

        if ($preparationPlan === []) {
            $checklistLines[] = '1. validation-complete - No additional preparation steps are currently required.';
            $shellLines[] = '# No preparation steps are currently required.';
        }

        foreach ($preparationPlan as $index => $step) {
            $action = is_string($step['action'] ?? null) ? $step['action'] : 'unknown';
            $description = is_string($step['description'] ?? null) ? $step['description'] : 'Sin descripcion';
            $commands = is_array($step['commands'] ?? null) ? $step['commands'] : [];

            $checklistLines[] = sprintf('%d. %s - %s', $index + 1, $action, $description);
            $shellLines[] = sprintf('# Step %d: %s', $index + 1, $action);
            $shellLines[] = sprintf('# %s', $description);

            if ($commands === []) {
                $checklistLines[] = '   - No commands provided.';
                $shellLines[] = '# No commands provided for this step.';
                $shellLines[] = '';
                continue;
            }

            foreach ($commands as $command) {
                if (!is_string($command) || trim($command) === '') {
                    continue;
                }

                $trimmedCommand = trim($command);
                $checklistLines[] = sprintf('   - %s', $trimmedCommand);
                $shellLines[] = $this->renderPreparationShellCommand($trimmedCommand, $osFamily);
            }

            $shellLines[] = '';
        }

        $this->writeTextFile($checklistPath, implode(PHP_EOL, $checklistLines) . PHP_EOL);
        $this->writeTextFile($shellScriptPath, implode(PHP_EOL, $shellLines) . PHP_EOL);
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

    /**
     * @param array<string, mixed> $diagnosis
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, mixed> $startup
     * @param array<string, mixed> $coupling
     * @param array<string, mixed>|null $ownership
     * @param array<string, mixed> $hostCompatibility
     * @param array<string, mixed> $nativeDbusExtensionInstallability
     * @param array<string, string> $environmentOverrides
     * @return list<array<string, mixed>>
     */
    private function createPreparationPlan(
        array $diagnosis,
        array $serviceDefinition,
        array $startup,
        array $coupling,
        ?array $ownership,
        array $hostCompatibility,
        array $nativeDbusExtensionInstallability,
        array $environmentOverrides,
        string $osFamily,
        string $targetRepoRoot,
        bool $attemptOwnership,
        bool $ownershipValidated,
        bool $couplingReady
    ): array {
        $plan = [];
        if ((($hostCompatibility['compatible'] ?? true) === false)) {
            $compatibilitySteps = $hostCompatibility['preparation_steps'] ?? [];
            if (is_array($compatibilitySteps)) {
                foreach ($compatibilitySteps as $step) {
                    if (is_array($step)) {
                        $plan[] = $step;
                    }
                }
            }

            $plan[] = [
                'action' => 'rerun-native-runtime-validation',
                'description' => 'Reejecutar el runner despues de homologar el runtime PHP del host Linux para confirmar si el siguiente bloqueo ya es exclusivamente D-Bus.',
                'commands' => $this->buildRerunCommands($osFamily, $targetRepoRoot),
            ];

            return $plan;
        }

        if ((($nativeDbusExtensionInstallability['blocked'] ?? false) === true)) {
            $installabilitySteps = $nativeDbusExtensionInstallability['preparation_steps'] ?? [];
            if (is_array($installabilitySteps)) {
                foreach ($installabilitySteps as $step) {
                    if (is_array($step)) {
                        $plan[] = $step;
                    }
                }
            }

            $plan[] = [
                'action' => 'rerun-native-runtime-validation',
                'description' => 'Reejecutar el runner despues de corregir la instalacion o compilacion de la extension `dbus` para confirmar si el ownership ya puede progresar.',
                'commands' => $this->buildRerunCommands($osFamily, $targetRepoRoot),
            ];

            return $plan;
        }

        $missingCapabilities = $diagnosis['missing_capabilities'] ?? [];

        if (is_array($missingCapabilities)) {
            foreach ($missingCapabilities as $capability) {
                if (!is_string($capability)) {
                    continue;
                }

                $step = $this->createCapabilityPreparationStep($capability, $osFamily);
                if ($step !== null) {
                    $plan[] = $step;
                }
            }
        }

        $diagnosticPreparationStep = $this->createDiagnosticPreparationStep(
            $serviceDefinition,
            $startup,
            $coupling,
            $ownership,
            $osFamily
        );
        if ($diagnosticPreparationStep !== null) {
            $plan[] = $diagnosticPreparationStep;
        }

        if ((($startup['startup_acknowledged'] ?? false) === true) && !$couplingReady) {
            $artifactsDir = (string) ($environmentOverrides['W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR'] ?? '');
            $statusFilePath = (string) ($serviceDefinition['artifacts']['status_file_path'] ?? '');
            $plan[] = [
                'action' => 'inspect-native-runtime-artifacts',
                'description' => 'Revisar los artefactos del runtime nativo y confirmar que startup, status y pid quedaron materializados antes del ownership.',
                'commands' => $this->buildArtifactInspectionCommands($artifactsDir, $statusFilePath, $osFamily),
            ];
        }

        if ($attemptOwnership && !$ownershipValidated && is_array($ownership)) {
            $plan[] = [
                'action' => 'retry-native-bus-ownership',
                'description' => 'Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.',
                'commands' => $this->buildRerunCommands($osFamily, $targetRepoRoot),
            ];
        }

        if ($ownershipValidated || (!$attemptOwnership && $couplingReady)) {
            $plan[] = [
                'action' => 'capture-release-evidence',
                'description' => 'Conservar el reporte y los artefactos como evidencia para el cierre de la validacion V1 del runtime D-Bus nativo.',
                'commands' => [
                    $this->buildValidationCommand('markdown', $osFamily, $targetRepoRoot),
                ],
            ];
        }

        return $plan;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function createCapabilityPreparationStep(string $capability, string $osFamily): ?array
    {
        return match ($capability) {
            'php-dbus-runtime-adapter' => [
                'action' => 'install-native-dbus-extension',
                'description' => 'Instalar y habilitar la extension PECL `dbus` para que PHP pueda pedir el bus name y exportar el objeto D-Bus.',
                'commands' => $this->resolveCapabilityCommands($capability, $osFamily),
            ],
            'dbus-daemon' => [
                'action' => 'install-dbus-daemon',
                'description' => 'Asegurar que `dbus-daemon` este instalado y disponible en el host.',
                'commands' => $this->resolveCapabilityCommands($capability, $osFamily),
            ],
            'system-bus-socket' => [
                'action' => 'materialize-system-bus-socket',
                'description' => 'Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.',
                'commands' => $this->resolveCapabilityCommands($capability, $osFamily),
            ],
            'dbus-client-tooling' => [
                'action' => 'install-dbus-client-tooling',
                'description' => 'Instalar al menos una herramienta de cliente D-Bus (`busctl` o `gdbus`) para contrastar el runtime desde el host.',
                'commands' => $this->resolveCapabilityCommands($capability, $osFamily),
            ],
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function resolveCapabilityCommands(string $capability, string $osFamily): array
    {
        if ($osFamily === 'Linux') {
            return match ($capability) {
                'php-dbus-runtime-adapter' => [
                    'sudo apt-get update',
                    'sudo apt-get install -y php-pear php-dev libdbus-1-dev pkg-config',
                    'sudo pecl install dbus',
                    'php --ri dbus',
                ],
                'dbus-daemon' => [
                    'sudo apt-get update',
                    'sudo apt-get install -y dbus',
                    'dbus-daemon --version',
                ],
                'system-bus-socket' => [
                    'sudo systemctl status dbus',
                    'test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready',
                ],
                'dbus-client-tooling' => [
                    'sudo apt-get update',
                    'sudo apt-get install -y systemd dbus',
                    'busctl --version || gdbus --version',
                ],
                default => [],
            };
        }

        return [
            sprintf('Resolver manualmente la capacidad faltante `%s` para el sistema operativo `%s`.', $capability, $osFamily),
        ];
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, mixed> $startup
     * @param array<string, mixed> $coupling
     * @param array<string, mixed>|null $ownership
     * @return array<string, mixed>|null
     */
    private function createDiagnosticPreparationStep(
        array $serviceDefinition,
        array $startup,
        array $coupling,
        ?array $ownership,
        string $osFamily
    ): ?array {
        $nextAction = $this->resolvePrimaryNextAction(
            is_array($ownership) ? $ownership : [],
            $coupling,
            $startup,
            $serviceDefinition
        );

        return match ($nextAction) {
            'inspect-native-dbus-extension-api' => [
                'action' => 'inspect-native-dbus-extension-api',
                'description' => 'Inspeccionar si la extension `dbus` expone la clase y los metodos minimos requeridos por el backend nativo (`BUS_SYSTEM`, `requestName`, `registerObject`, `waitLoop`).',
                'commands' => $this->buildNativeDbusExtensionApiInspectionCommands($osFamily),
            ],
            'inspect-native-dbus-service-class' => [
                'action' => 'inspect-native-dbus-service-class',
                'description' => 'Verificar si la extension `dbus` realmente publica una clase de servicio utilizable (`Dbus` o `DBus`) en este runtime PHP.',
                'commands' => $this->buildNativeDbusServiceClassInspectionCommands($osFamily),
            ],
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function buildNativeDbusExtensionApiInspectionCommands(string $osFamily): array
    {
        if ($osFamily === 'Linux') {
            return [
                'php --ri dbus',
                'php -r "foreach ([\'Dbus\', \'DBus\'] as $class) { if (!class_exists($class)) { continue; } echo $class, PHP_EOL; echo \'BUS_SYSTEM:\', defined($class . \'::BUS_SYSTEM\') ? \'yes\' : \'no\', PHP_EOL; echo \'requestName:\', method_exists($class, \'requestName\') ? \'yes\' : \'no\', PHP_EOL; echo \'registerObject:\', method_exists($class, \'registerObject\') ? \'yes\' : \'no\', PHP_EOL; echo \'waitLoop:\', method_exists($class, \'waitLoop\') ? \'yes\' : \'no\', PHP_EOL; exit(0); } echo \'dbus-class-missing\', PHP_EOL;"',
            ];
        }

        return [
            'php --ri dbus',
            'Inspeccionar manualmente si la extension `dbus` expone BUS_SYSTEM, requestName, registerObject y waitLoop.',
        ];
    }

    /**
     * @return list<string>
     */
    private function buildNativeDbusServiceClassInspectionCommands(string $osFamily): array
    {
        if ($osFamily === 'Linux') {
            return [
                'php --ri dbus',
                'php -m | grep -i dbus || true',
                'php -r "foreach ([\'Dbus\', \'DBus\'] as $class) { echo $class, \':\', class_exists($class) ? \'yes\' : \'no\', PHP_EOL; }"',
            ];
        }

        return [
            'php --ri dbus',
            'Inspeccionar manualmente si el runtime PHP publica la clase `Dbus` o `DBus`.',
        ];
    }

    /**
     * @param array<string, mixed> $diagnosis
     * @return array<string, mixed>
     */
    private function evaluateHostCompatibility(array $diagnosis, string $targetRepoRoot): array
    {
        $environment = is_array($diagnosis['environment'] ?? null) ? $diagnosis['environment'] : [];
        $osFamily = is_string($environment['os_family'] ?? null) ? trim((string) $environment['os_family']) : 'Unknown';
        $phpVersion = is_string($environment['php_version'] ?? null) ? trim((string) $environment['php_version']) : '';
        $requiredPhpConstraint = $this->resolveRequiredPhpConstraint();
        $requiredPhpVersion = $this->resolveMinimumPhpVersionFromConstraint($requiredPhpConstraint);
        $compatible = true;
        $blockingReason = null;
        $nextAction = null;
        $recommendedNextActions = [];
        $preparationSteps = [];

        if (
            $this->normalizeOsFamily($osFamily) === 'linux'
            && $phpVersion !== ''
            && $requiredPhpVersion !== null
            && version_compare($this->normalizePhpVersion($phpVersion), $requiredPhpVersion, '<')
        ) {
            $compatible = false;
            $blockingReason = 'linux-php-runtime-too-old';
            $nextAction = 'upgrade-runtime-php';
            $recommendedNextActions = [
                'upgrade-runtime-php',
                'migrate-to-modern-linux-host',
            ];
            $preparationSteps[] = $this->createPhpRuntimeCompatibilityStep(
                $phpVersion,
                $requiredPhpConstraint,
                $requiredPhpVersion,
                $targetRepoRoot
            );
        }

        return [
            'compatible' => $compatible,
            'os_family' => $osFamily,
            'php_version' => $phpVersion,
            'required_php_constraint' => $requiredPhpConstraint,
            'required_php_version' => $requiredPhpVersion,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
            'recommended_next_actions' => $recommendedNextActions,
            'preparation_steps' => $preparationSteps,
        ];
    }

    /**
     * @param array<string, mixed> $diagnosis
     * @return array<string, mixed>
     */
    private function evaluateNativeDbusExtensionInstallability(array $diagnosis, string $targetRepoRoot): array
    {
        $installation = is_array($diagnosis['native_dbus_extension_installation'] ?? null)
            ? $diagnosis['native_dbus_extension_installation']
            : [];
        $state = is_string($installation['state'] ?? null) ? trim((string) $installation['state']) : 'unknown';
        $blocked = in_array($state, ['build-failed', 'unsupported-stack'], true);
        $blockingReason = is_string($installation['blocking_reason'] ?? null)
            ? trim((string) $installation['blocking_reason'])
            : null;
        $nextAction = is_string($installation['next_action'] ?? null)
            ? trim((string) $installation['next_action'])
            : null;
        $buildPhase = is_string($installation['build_phase'] ?? null)
            ? trim((string) $installation['build_phase'])
            : 'unknown';
        $reason = is_string($installation['reason'] ?? null) ? trim((string) $installation['reason']) : null;
        $failureSignature = is_string($installation['failure_signature'] ?? null)
            ? trim((string) $installation['failure_signature'])
            : null;
        $logPath = is_string($installation['log_path'] ?? null) ? trim((string) $installation['log_path']) : null;
        $peclAvailable = (($installation['pecl_available'] ?? false) === true);
        $recommendedNextActions = [];
        $preparationSteps = [];

        if ($blocked) {
            $recommendedNextActions = array_values(array_unique(array_filter([
                $nextAction,
                'migrate-to-modern-linux-host',
            ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));
            $preparationSteps[] = $this->createNativeDbusExtensionInstallabilityStep(
                $state,
                $buildPhase,
                $reason,
                $failureSignature,
                $logPath,
                $targetRepoRoot,
                $peclAvailable
            );
        }

        return [
            'blocked' => $blocked,
            'state' => $state,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
            'build_phase' => $buildPhase,
            'reason' => $reason,
            'failure_signature' => $failureSignature,
            'log_path' => $logPath,
            'pecl_available' => $peclAvailable,
            'recommended_next_actions' => $recommendedNextActions,
            'preparation_steps' => $preparationSteps,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createNativeDbusExtensionInstallabilityStep(
        string $state,
        string $buildPhase,
        ?string $reason,
        ?string $failureSignature,
        ?string $logPath,
        string $targetRepoRoot,
        bool $peclAvailable
    ): array {
        $description = $state === 'unsupported-stack'
            ? 'El stack actual no parece compatible con la extension `dbus`. Conviene inspeccionar el entorno y mover el contraste a un Linux moderno homologado para este runtime.'
            : $this->describeNativeDbusBuildFailurePhase($buildPhase, $reason);

        return [
            'action' => $state === 'unsupported-stack'
                ? 'migrate-to-modern-linux-host'
                : $this->resolveNativeDbusBuildFailureAction($buildPhase),
            'description' => $description,
            'commands' => $this->buildNativeDbusExtensionInstallabilityCommands(
                $state,
                $buildPhase,
                $reason,
                $failureSignature,
                $logPath,
                $targetRepoRoot,
                $peclAvailable
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function buildNativeDbusExtensionInstallabilityCommands(
        string $state,
        string $buildPhase,
        ?string $reason,
        ?string $failureSignature,
        ?string $logPath,
        string $targetRepoRoot,
        bool $peclAvailable
    ): array {
        $commands = [
            'php --version',
            'phpize --version || true',
            'lsb_release -a || cat /etc/os-release',
        ];

        if ($peclAvailable) {
            $commands[] = 'pecl version';
            $commands[] = 'pecl channel-info pecl.php.net || true';
            $commands[] = 'pecl channel-update pecl.php.net || true';
            $commands[] = 'pecl remote-info pecl/dbus || true';
            $commands[] = 'pecl remote-info channel://pecl.php.net/dbus-0.1.1';
            $commands[] = 'pecl search dbus || true';
        } else {
            $commands[] = 'echo "PECL no esta disponible en este host."';
        }

        if ($buildPhase === 'pecl-download' && $peclAvailable) {
            if ($reason === 'pecl-release-is-beta') {
                $commands[] = 'pecl install channel://pecl.php.net/dbus-0.1.1 || true';
            }

            if ($reason === 'native-dbus-extension-download-failed') {
                $commands[] = 'pecl list-channels || true';
            }
        }

        if ($buildPhase === 'phpize') {
            $commands[] = 'which phpize || true';
            $commands[] = 'autoconf --version || true';
            $commands[] = 'autoheader --version || true';
            $commands[] = 'aclocal --version || true';
        }

        if ($buildPhase === 'configure') {
            $commands[] = 'pkg-config --modversion dbus-1 || true';
            $commands[] = 'pkg-config --modversion libxml-2.0 || true';
            $commands[] = 'which phpize || true';
            $commands[] = 'which xml2-config || true';
        }

        if ($buildPhase === 'make') {
            $commands[] = 'make --version || true';
            $commands[] = 'gcc --version || true';
        }

        if ($reason !== null && $reason !== '') {
            $commands[] = sprintf(
                "printf '%%s\n' %s",
                escapeshellarg('Ultimo motivo reportado: ' . $reason)
            );
        }

        if ($failureSignature !== null && $failureSignature !== '') {
            $commands[] = sprintf(
                "printf '%%s\n' %s",
                escapeshellarg('Firma detectada del fallo: ' . $failureSignature)
            );
        }

        if ($logPath !== null && $logPath !== '') {
            $commands[] = sprintf(
                'test -f "%s" && tail -n 80 "%s" || echo "Build log no disponible: %s"',
                $logPath,
                $logPath,
                $logPath
            );
        }

        if ($state === 'unsupported-stack') {
            $commands[] = 'echo "Recomendar un host Linux moderno con PHP compatible y toolchain homologada para PECL dbus."';
        }

        $commands[] = $targetRepoRoot !== ''
            ? sprintf('cd "%s" && php -r "echo json_decode(file_get_contents(\'composer.json\'), true)[\'require\'][\'php\'], PHP_EOL;"', $targetRepoRoot)
            : 'php -r "echo json_decode(file_get_contents(\'composer.json\'), true)[\'require\'][\'php\'], PHP_EOL;"';

        return $commands;
    }

    private function resolveNativeDbusBuildFailureAction(string $buildPhase): string
    {
        return match ($buildPhase) {
            'pecl-download' => 'inspect-native-dbus-pecl-download',
            'phpize' => 'inspect-native-dbus-build-phpize',
            'configure' => 'inspect-native-dbus-build-configure',
            'make' => 'inspect-native-dbus-build-make',
            default => 'inspect-native-dbus-build-stack',
        };
    }

    private function describeNativeDbusBuildFailurePhase(string $buildPhase, ?string $reason): string
    {
        return match ($buildPhase) {
            'pecl-download' => $reason === 'pecl-release-is-beta'
                ? 'La extension `dbus` fallo porque la release estable no esta disponible en PECL y la disponible aparece como beta. Conviene validar el canal PECL, inspeccionar la metadata del paquete y probar la release explicita `channel://pecl.php.net/dbus-0.1.1` antes de seguir.'
                : 'La extension `dbus` fallo antes de descargar o resolver correctamente la release PECL. Conviene inspeccionar el canal, la release objetivo y la disponibilidad real del paquete antes de repetir la instalacion.',
            'phpize' => 'La extension `dbus` fallo durante `phpize` o en la disponibilidad de la toolchain autotools. Conviene inspeccionar `phpize`, `autoconf`, `autoheader` y `aclocal` antes de pasar a `configure`.',
            'configure' => 'La extension `dbus` fallo durante `configure`. Conviene inspeccionar `pkg-config`, `xml2-config` y las dependencias de desarrollo requeridas antes de volver a compilar.',
            'make' => 'La extension `dbus` ya avanzo hasta `make`, pero la compilacion fallo. Conviene inspeccionar el toolchain C y la evidencia del error de compilacion antes de seguir insistiendo con PECL.',
            default => 'La extension `dbus` ya fue intentada pero la compilacion o instalacion fallo. Conviene inspeccionar el stack y la evidencia del build antes de seguir insistiendo con PECL.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function createPhpRuntimeCompatibilityStep(
        string $detectedPhpVersion,
        ?string $requiredPhpConstraint,
        ?string $requiredPhpVersion,
        string $targetRepoRoot
    ): array {
        $requiredLabel = $requiredPhpConstraint ?? ($requiredPhpVersion ?? '8.4.0');

        return [
            'action' => 'upgrade-runtime-php',
            'description' => sprintf(
                'El host Linux publica PHP `%s`, pero el repo exige `%s`. Homologar un runtime PHP moderno antes de volver a intentar PECL `dbus` y el ownership real.',
                $detectedPhpVersion !== '' ? $detectedPhpVersion : 'unknown',
                $requiredLabel
            ),
            'commands' => $this->buildPhpRuntimeCompatibilityCommands($requiredPhpVersion, $targetRepoRoot),
        ];
    }

    /**
     * @return list<string>
     */
    private function buildPhpRuntimeCompatibilityCommands(?string $requiredPhpVersion, string $targetRepoRoot): array
    {
        $majorMinorVersion = $requiredPhpVersion !== null
            ? implode('.', array_slice(explode('.', $requiredPhpVersion), 0, 2))
            : '8.4';
        $manifestProbe = $targetRepoRoot !== ''
            ? sprintf(
                'cd "%s" && php -r "echo json_decode(file_get_contents(\'composer.json\'), true)[\'require\'][\'php\'], PHP_EOL;"',
                $targetRepoRoot
            )
            : 'php -r "echo json_decode(file_get_contents(\'composer.json\'), true)[\'require\'][\'php\'], PHP_EOL;"';

        return [
            'php --version',
            $manifestProbe,
            'lsb_release -a || cat /etc/os-release',
            sprintf('sudo apt-cache policy php%s-cli php%s-dev php%s-pear', $majorMinorVersion, $majorMinorVersion, $majorMinorVersion),
            'sudo apt-get update',
            sprintf('sudo apt-get install -y php%s-cli php%s-dev php%s-pear', $majorMinorVersion, $majorMinorVersion, $majorMinorVersion),
            'php --version',
        ];
    }

    private function resolveRequiredPhpConstraint(): ?string
    {
        $composerManifestPath = $this->rootDir . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($composerManifestPath)) {
            return null;
        }

        $raw = file_get_contents($composerManifestPath);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $manifest */
            $manifest = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $constraint = $manifest['require']['php'] ?? null;

        return is_string($constraint) && trim($constraint) !== '' ? trim($constraint) : null;
    }

    private function resolveMinimumPhpVersionFromConstraint(?string $constraint): ?string
    {
        if ($constraint === null || trim($constraint) === '') {
            return null;
        }

        if (!preg_match('/(\d+\.\d+(?:\.\d+)?)/', $constraint, $matches)) {
            return null;
        }

        return $this->normalizePhpVersion($matches[1]);
    }

    private function normalizePhpVersion(string $version): string
    {
        if (!preg_match('/^\d+(?:\.\d+){0,2}/', trim($version), $matches)) {
            return '0.0.0';
        }

        $segments = explode('.', $matches[0]);
        while (count($segments) < 3) {
            $segments[] = '0';
        }

        return implode('.', array_slice($segments, 0, 3));
    }

    private function normalizeOsFamily(string $osFamily): string
    {
        return strtolower(trim($osFamily));
    }

    private function renderPreparationShellCommand(string $command, string $osFamily): string
    {
        if ($osFamily === 'Linux') {
            return $command;
        }

        return sprintf('echo %s', escapeshellarg($command));
    }

    /**
     * @param array<string, mixed> $environment
     * @param array<string, string> $environmentOverrides
     */
    private function resolvePreparationTargetOsFamily(array $environment, array $environmentOverrides): string
    {
        $override = $environmentOverrides['W4_SYSTEM_API_PREPARATION_TARGET_OS_FAMILY'] ?? null;
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        $osFamily = $environment['os_family'] ?? null;
        if (is_string($osFamily) && trim($osFamily) !== '') {
            return trim($osFamily);
        }

        return 'Unknown';
    }

    /**
     * @param array<string, string> $environmentOverrides
     */
    private function resolvePreparationTargetRepoRoot(array $environmentOverrides, string $osFamily): string
    {
        $override = $environmentOverrides['W4_SYSTEM_API_PREPARATION_TARGET_REPO_ROOT'] ?? null;
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        if ($osFamily === 'Linux') {
            return '/opt/w4/w4-os-system';
        }

        return $this->rootDir;
    }

    /**
     * @param array<string, string> $environmentOverrides
     */
    private function resolveNativeDbusBuildEvidencePath(string $normalizedEvidenceDir, array $environmentOverrides): ?string
    {
        $override = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH'] ?? null;
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        $targetOsFamily = $environmentOverrides['W4_SYSTEM_API_PREPARATION_TARGET_OS_FAMILY'] ?? null;
        if (!is_string($targetOsFamily) || trim($targetOsFamily) !== 'Linux') {
            return null;
        }

        $candidate = $normalizedEvidenceDir . DIRECTORY_SEPARATOR . 'wsl-ubuntu-operational-attempt.md';

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * @param array<string, string> $environmentOverrides
     */
    private function materializeNativeDbusBuildEvidenceArtifact(
        string $artifactPath,
        ?string $sourcePath,
        array $environmentOverrides
    ): ?string {
        $jsonOverride = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_JSON_PATH'] ?? null;
        if (is_string($jsonOverride) && trim($jsonOverride) !== '') {
            return trim($jsonOverride);
        }

        $explicitPayload = $this->buildNativeDbusBuildEvidencePayloadFromOverrides($environmentOverrides);
        if ($explicitPayload !== null) {
            $this->writeJsonFile($artifactPath, $explicitPayload);

            return $artifactPath;
        }

        if ($sourcePath === null || !is_file($sourcePath)) {
            return null;
        }

        $raw = file_get_contents($sourcePath);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $payload = $this->buildNativeDbusBuildEvidencePayload($sourcePath, $raw);
        $this->writeJsonFile($artifactPath, $payload);

        return $artifactPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNativeDbusBuildEvidencePayload(string $sourcePath, string $raw): array
    {
        $normalized = strtolower($raw);
        $markers = [];
        $state = 'unknown';
        $reason = null;
        $buildPhase = 'unknown';

        if (
            str_contains($normalized, 'failed to download pecl/dbus')
            || str_contains($normalized, 'latest release is beta')
            || str_contains($normalized, 'no releases available for package')
        ) {
            $markers[] = 'pecl-download';
            $state = 'build-failed';
            $buildPhase = 'pecl-download';
            $reason = str_contains($normalized, 'latest release is beta')
                ? 'pecl-release-is-beta'
                : 'native-dbus-extension-download-failed';
        }

        if (
            $state === 'unknown'
            && (
                str_contains($normalized, 'configure: error')
                || str_contains($normalized, 'cannot find xml2-config')
                || str_contains($normalized, 'libxml-2.0')
                || str_contains($normalized, 'phpize failed')
                || str_contains($normalized, 'cannot find autoconf')
                || str_contains($normalized, '$php_autoconf')
            )
        ) {
            $state = 'build-failed';
            $buildPhase = (
                str_contains($normalized, 'phpize failed')
                || str_contains($normalized, 'cannot find autoconf')
                || str_contains($normalized, '$php_autoconf')
            ) ? 'phpize' : 'configure';
            $markers[] = $buildPhase;
            $reason = str_contains($normalized, 'cannot find autoconf')
                ? 'autoconf-missing'
                : (str_contains($normalized, '$php_autoconf')
                    ? 'phpize-toolchain-misconfigured'
                    : (str_contains($normalized, 'libxml-2.0')
                        ? 'libxml-2.0'
                        : (str_contains($normalized, 'cannot find xml2-config')
                            ? 'xml2-config-missing'
                            : 'native-dbus-extension-configure-failed')));
        }

        if (
            str_contains($normalized, 'zend_object_store_get_object')
            || str_contains($normalized, 'make: ***')
            || str_contains($normalized, 'compilacion sigue fallando')
            || str_contains($normalized, 'no compila')
        ) {
            $markers[] = 'make';
            $state = 'build-failed';
            $buildPhase = 'make';
            $reason = str_contains($normalized, 'zend_object_store_get_object')
                ? 'zend_object_store_get_object'
                : (str_contains($normalized, 'make: *** [makefile:192: dbus.lo] error 1')
                    ? 'make: *** [Makefile:192: dbus.lo] Error 1'
                    : 'native-dbus-extension-build-failed');
        }

        if (
            $state === 'unknown'
            && (str_contains($normalized, 'unsupported stack') || str_contains($normalized, 'stack no soportado'))
        ) {
            $markers[] = 'unsupported-stack';
            $state = 'unsupported-stack';
            $reason = 'native-dbus-extension-unsupported-stack';
        }

        if ($reason !== null && $reason !== '' && !in_array($reason, $markers, true)) {
            array_unshift($markers, $reason);
        }

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-native-dbus-extension-build-evidence',
            'generated_at' => gmdate('c'),
            'source_path' => $sourcePath,
            'state' => $state,
            'build_phase' => $buildPhase,
            'reason' => $reason,
            'failure_signature' => $this->resolveNativeDbusFailureSignature($raw, $buildPhase, $reason),
            'log_path' => $sourcePath,
            'markers' => $markers,
        ];
    }

    /**
     * @param array<string, string> $environmentOverrides
     * @return array<string, mixed>|null
     */
    private function buildNativeDbusBuildEvidencePayloadFromOverrides(array $environmentOverrides): ?array
    {
        $state = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_STATE'] ?? null;
        $buildPhase = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_PHASE'] ?? null;
        $reason = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_REASON'] ?? null;
        $logPath = $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_LOG_PATH']
            ?? $environmentOverrides['W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH']
            ?? null;

        if (!is_string($state) || trim($state) === '') {
            return null;
        }

        $normalizedState = strtolower(trim($state));
        $normalizedReason = is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
        $normalizedLogPath = is_string($logPath) && trim($logPath) !== '' ? trim($logPath) : null;
        $normalizedBuildPhase = is_string($buildPhase) && trim($buildPhase) !== ''
            ? strtolower(trim($buildPhase))
            : $this->inferNativeDbusBuildPhaseFromReason($normalizedReason, $normalizedState);
        $failureSignature = $this->inferNativeDbusFailureSignatureFromReason($normalizedReason, $normalizedState);
        if ($normalizedLogPath !== null && is_file($normalizedLogPath)) {
            $rawLog = file_get_contents($normalizedLogPath);
            if (is_string($rawLog) && $rawLog !== '') {
                $resolvedFailureSignature = $this->resolveNativeDbusFailureSignature($rawLog, $normalizedBuildPhase, $normalizedReason);
                if ($resolvedFailureSignature !== null && $resolvedFailureSignature !== '') {
                    $failureSignature = $resolvedFailureSignature;
                }
            }
        }
        $markers = [];

        if ($normalizedReason !== null) {
            $markers[] = $normalizedReason;
        }

        if ($normalizedBuildPhase !== 'unknown') {
            $markers[] = $normalizedBuildPhase;
        }

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-native-dbus-extension-build-evidence',
            'generated_at' => gmdate('c'),
            'source_path' => $normalizedLogPath,
            'state' => $normalizedState,
            'build_phase' => $normalizedBuildPhase,
            'reason' => $normalizedReason,
            'failure_signature' => $failureSignature,
            'log_path' => $normalizedLogPath,
            'markers' => $markers,
        ];
    }

    private function inferNativeDbusBuildPhaseFromReason(?string $reason, string $state): string
    {
        if ($state !== 'build-failed' || $reason === null || trim($reason) === '') {
            return 'unknown';
        }

        $normalized = strtolower(trim($reason));

        if (
            str_contains($normalized, 'pecl-release-is-beta')
            || str_contains($normalized, 'download')
        ) {
            return 'pecl-download';
        }

        if (
            str_contains($normalized, 'libxml-2.0')
            || str_contains($normalized, 'xml2-config')
            || str_contains($normalized, 'configure')
        ) {
            return 'configure';
        }

        if (
            str_contains($normalized, 'phpize')
            || str_contains($normalized, 'autoconf')
            || str_contains($normalized, '$php_autoconf')
        ) {
            return 'phpize';
        }

        if (
            str_contains($normalized, 'zend_object_store_get_object')
            || str_contains($normalized, 'make:')
            || str_contains($normalized, 'dbus.lo')
            || str_contains($normalized, 'build-failed')
        ) {
            return 'make';
        }

        return 'unknown';
    }

    private function inferNativeDbusFailureSignatureFromReason(?string $reason, string $state): ?string
    {
        if ($state !== 'build-failed' || $reason === null || trim($reason) === '') {
            return null;
        }

        return trim($reason);
    }

    private function resolveNativeDbusFailureSignature(string $raw, string $buildPhase, ?string $reason): ?string
    {
        $lines = preg_split('/\R/', $raw) ?: [];
        $patterns = match ($buildPhase) {
            'pecl-download' => [
                '/failed to download pecl\/dbus/i',
                '/latest release is beta/i',
                '/no releases available for package/i',
            ],
            'phpize' => [
                '/cannot find autoconf/i',
                '/\$php_autoconf/i',
                '/phpize failed/i',
            ],
            'configure' => [
                '/configure:\s*error/i',
                '/cannot find xml2-config/i',
                '/libxml-2\.0/i',
            ],
            'make' => [
                '/make:\s*\*\*\*/i',
                '/zend_object_store_get_object/i',
                '/dbus\.lo/i',
            ],
            default => [],
        };

        foreach ($patterns as $pattern) {
            foreach ($lines as $line) {
                if (is_string($line) && preg_match($pattern, $line) === 1) {
                    return $this->normalizeNativeDbusFailureSignatureLine($line);
                }
            }
        }

        return $this->inferNativeDbusFailureSignatureFromReason($reason, 'build-failed');
    }

    private function normalizeNativeDbusFailureSignatureLine(string $line): string
    {
        $normalized = trim($line);
        $normalized = preg_replace('/^-\s*fallo final:\s*/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/^-\s*/', '', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if (str_starts_with($normalized, '`') && str_ends_with($normalized, '`')) {
            $normalized = trim($normalized, '`');
        }

        return trim($normalized);
    }

    /**
     * @return list<string>
     */
    private function buildArtifactInspectionCommands(string $artifactsDir, string $statusFilePath, string $osFamily): array
    {
        if ($osFamily === 'Linux') {
            return [
                sprintf('ls -la "%s"', $artifactsDir),
                sprintf('cat "%s"', $statusFilePath),
            ];
        }

        return [
            sprintf('Get-ChildItem -Force "%s"', $artifactsDir),
            sprintf('Get-Content "%s"', $statusFilePath),
        ];
    }

    /**
     * @return list<string>
     */
    private function buildRerunCommands(string $osFamily, string $targetRepoRoot): array
    {
        return [
            $this->buildValidationCommand('text', $osFamily, $targetRepoRoot),
            $this->buildValidationCommand('markdown', $osFamily, $targetRepoRoot),
            $this->buildValidationCommand('json', $osFamily, $targetRepoRoot),
        ];
    }

    private function buildValidationCommand(string $format, string $osFamily, string $targetRepoRoot): string
    {
        $command = sprintf(
            'php scripts/run_native_dbus_real_host_validation.php --format %s --target-os-family %s',
            $format,
            $osFamily
        );

        if ($targetRepoRoot === '') {
            return $command;
        }

        if ($osFamily === 'Linux') {
            return sprintf('cd "%s" && %s', $targetRepoRoot, $command);
        }

        return sprintf(
            'Set-Location "%s"; %s',
            $targetRepoRoot,
            $command
        );
    }

    /**
     * @return list<string>
     */
    private function buildVerificationCommands(string $osFamily): array
    {
        if ($osFamily === 'Linux') {
            return [
                'php --ri dbus',
                'busctl --system list | grep org.w4.System1 || true',
                'busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager',
            ];
        }

        return [
            'php --ri dbus',
            'Revisar manualmente la visibilidad de org.w4.System1 en el system bus del host objetivo.',
        ];
    }
}
