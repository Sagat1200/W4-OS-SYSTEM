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
        $environment = is_array($steps['diagnosis']['environment'] ?? null) ? $steps['diagnosis']['environment'] : [];
        $osFamily = $this->resolvePreparationTargetOsFamily($environment, $effectiveOverrides);
        $preparationPlan = $this->createPreparationPlan(
            $steps['diagnosis'],
            $steps['service_definition'],
            $steps['startup'],
            $steps['coupling'],
            $steps['ownership'],
            $effectiveOverrides,
            $osFamily,
            $attemptOwnership,
            $ownershipValidated,
            $couplingReady
        );

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
            'artifacts_dir' => $artifactsDir,
            'runtime_backend' => $effectiveOverrides['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'native-dbus-extension',
            'preparation_target_os_family' => $osFamily,
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
            sprintf('- Blocking reason: `%s`', (string) ($report['blocking_reason'] ?? 'n/a')),
            sprintf('- Next action: `%s`', (string) ($report['next_action'] ?? 'n/a')),
            sprintf('- Report path: `%s`', (string) ($report['report_path'] ?? 'n/a')),
            sprintf('- Preparation shell script: `%s`', (string) ($report['preparation_shell_script_path'] ?? 'n/a')),
            sprintf('- Preparation checklist: `%s`', (string) ($report['preparation_checklist_path'] ?? 'n/a')),
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
     * @param array<string, string> $environmentOverrides
     * @return list<array<string, mixed>>
     */
    private function createPreparationPlan(
        array $diagnosis,
        array $serviceDefinition,
        array $startup,
        array $coupling,
        ?array $ownership,
        array $environmentOverrides,
        string $osFamily,
        bool $attemptOwnership,
        bool $ownershipValidated,
        bool $couplingReady
    ): array {
        $plan = [];
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

        if ((($startup['startup_acknowledged'] ?? false) === true) && !$couplingReady) {
            $plan[] = [
                'action' => 'inspect-native-runtime-artifacts',
                'description' => 'Revisar los artefactos del runtime nativo y confirmar que startup, status y pid quedaron materializados antes del ownership.',
                'commands' => [
                    sprintf('Get-ChildItem -Force "%s"', $environmentOverrides['W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR'] ?? ''),
                    sprintf('Get-Content "%s"', $serviceDefinition['artifacts']['status_file_path'] ?? ''),
                ],
            ];
        }

        if ($attemptOwnership && !$ownershipValidated && is_array($ownership)) {
            $plan[] = [
                'action' => 'retry-native-bus-ownership',
                'description' => 'Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.',
                'commands' => [
                    'php scripts/run_native_dbus_real_host_validation.php --format text',
                ],
            ];
        }

        if ($ownershipValidated || (!$attemptOwnership && $couplingReady)) {
            $plan[] = [
                'action' => 'capture-release-evidence',
                'description' => 'Conservar el reporte y los artefactos como evidencia para el cierre de la validacion V1 del runtime D-Bus nativo.',
                'commands' => [
                    'php scripts/run_native_dbus_real_host_validation.php --format markdown',
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
}
