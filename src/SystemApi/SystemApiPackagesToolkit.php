<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;
use W4\OS\Update\UpdateToolkit;

final class SystemApiPackagesToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function listUpdates(?int $limit = null): array
    {
        $output = $this->resolveUpdatesListOutput();
        $items = $this->parseUpgradablePackages($output);

        if (is_int($limit) && $limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return [
            'kind' => 'system-api-updates-list',
            'status' => 'ok',
            'backend' => 'apt',
            'catalog_revision' => sha1($output),
            'items' => $items,
            'count' => count($items),
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function checkUpdates(bool $refresh = false, ?int $limit = null): array
    {
        $refreshReport = $refresh ? $this->refreshUpdateCatalog() : null;
        $list = $this->listUpdates($limit);

        return [
            'kind' => 'system-api-updates-check',
            'status' => ($list['count'] ?? 0) > 0 ? 'updates-available' : 'up-to-date',
            'backend' => 'apt',
            'refresh_requested' => $refresh,
            'refresh_performed' => $refresh,
            'refresh' => $refreshReport,
            'catalog_revision' => $list['catalog_revision'] ?? null,
            'updates' => $list,
            'count' => $list['count'] ?? 0,
        ];
    }

    /**
     * @param array<string, mixed>|null $plan
     * @return array<string, mixed>
     */
    public function applyUpdates(
        string $operationId,
        string $planHash,
        string $idempotencyKey,
        ?array $plan = null,
        ?string $planPath = null
    ): array {
        $resolvedPlan = $this->resolveUpdateApplyPlan($plan, $planPath);
        $this->assertUpdatePlanMatches($resolvedPlan, $operationId, $planHash);

        $result = $this->resolveUpdatesApplyExecutionResult($resolvedPlan);
        $succeeded = ($result['exit_code'] ?? 1) === 0;
        $durableState = $this->buildUpdatesDurableState($resolvedPlan, $succeeded, $result);
        $postReboot = $this->buildUpdatesPostRebootContract($resolvedPlan, $succeeded);

        return [
            'kind' => 'system-api-update-operation',
            'status' => $succeeded ? 'pending-reboot' : 'failed',
            'backend' => 'apt-get',
            'operation_id' => $operationId,
            'plan_hash' => $planHash,
            'idempotency_key' => $idempotencyKey,
            'profile_id' => $resolvedPlan['profile_id'] ?? null,
            'source_version' => $resolvedPlan['source_version'] ?? null,
            'target_version' => $resolvedPlan['target_version'] ?? null,
            'apply_mode' => $resolvedPlan['execution']['apply_mode'] ?? null,
            'stage' => $succeeded ? 'prepared' : 'failed',
            'state' => $succeeded ? 'needs_attention' : 'failed',
            'command' => 'updates:apply',
            'command_sequence' => $this->renderCommandSequence($this->buildUpdatesApplyCommands($resolvedPlan)),
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
            'package_changes' => $resolvedPlan['package_changes'] ?? [],
            'repository_snapshot' => $resolvedPlan['repository_snapshot'] ?? [],
            'snapshot' => is_array($resolvedPlan['snapshot'] ?? null) ? $resolvedPlan['snapshot'] : null,
            'staging' => is_array($resolvedPlan['staging'] ?? null) ? $resolvedPlan['staging'] : null,
            'boot_manifest' => is_array($resolvedPlan['boot_manifest'] ?? null) ? $resolvedPlan['boot_manifest'] : null,
            'reboot_required' => (($resolvedPlan['execution']['reboot_required'] ?? false) === true),
            'stages' => $resolvedPlan['execution']['stages'] ?? [],
            'evidence' => $this->normalizeOptionalStringList($resolvedPlan['evidence'] ?? null, 'params.plan.evidence'),
            'notes' => $this->normalizeOptionalStringList($resolvedPlan['notes'] ?? null, 'params.plan.notes'),
            'durable_state' => $durableState,
            'post_reboot' => $postReboot,
            'coordination' => $this->buildUpdatesCoordinationContract($resolvedPlan, $succeeded, $postReboot),
            'next_action' => $succeeded ? 'reboot-into-post-update-health-checks' : 'inspect-update-state',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $selection
     * @return array<string, mixed>
     */
    public function plan(string $action, array $selection, string $conffilePolicy, ?string $outputPath = null): array
    {
        $normalizedAction = $this->normalizeAction($action);
        $normalizedSelection = $this->normalizeSelection($selection);
        $normalizedConffilePolicy = $this->normalizeConffilePolicy($conffilePolicy);

        $plan = $this->resolvePlanPayload($normalizedAction, $normalizedSelection, $normalizedConffilePolicy);
        if ($outputPath !== null) {
            $this->writeJsonFile($outputPath, $plan);
        }

        $plan['command'] = 'packages:plan';
        $plan['output_path'] = $outputPath !== null ? str_replace(DIRECTORY_SEPARATOR, '/', $outputPath) : null;

        return $plan;
    }

    /**
     * @param array<string, mixed>|null $plan
     * @return array<string, mixed>
     */
    public function apply(
        string $planId,
        string $planHash,
        string $idempotencyKey,
        ?string $expectedCatalogRevision = null,
        ?array $plan = null,
        ?string $planPath = null
    ): array {
        $resolvedPlan = $this->resolveApplyPlan($plan, $planPath);
        $this->assertPlanMatches($resolvedPlan, $planId, $planHash);
        $observedCatalogRevision = $this->assertCatalogRevisionMatches($resolvedPlan, $expectedCatalogRevision);

        $result = $this->resolveApplyExecutionResult($resolvedPlan);
        $operationId = sprintf('pkg-op-%s', substr(sha1($idempotencyKey . '|' . $planHash), 0, 12));
        $succeeded = ($result['exit_code'] ?? 1) === 0;

        return [
            'kind' => 'system-api-package-operation',
            'status' => $succeeded ? 'ok' : 'failed',
            'backend' => 'apt-get',
            'operation_id' => $operationId,
            'plan_id' => $planId,
            'plan_hash' => $planHash,
            'idempotency_key' => $idempotencyKey,
            'expected_catalog_revision' => $expectedCatalogRevision,
            'observed_catalog_revision' => $observedCatalogRevision,
            'action' => $resolvedPlan['action'],
            'state' => $succeeded ? 'succeeded' : 'failed',
            'command' => $this->renderCommand($this->buildApplyCommand($resolvedPlan)),
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
            'requested_packages' => $resolvedPlan['requested_packages'],
            'effects' => [
                'installs' => $resolvedPlan['installs'] ?? [],
                'upgrades' => $resolvedPlan['upgrades'] ?? [],
                'removals' => $resolvedPlan['removals'] ?? [],
            ],
            'next_action' => $succeeded ? 'reconcile-installed-packages' : 'inspect-package-state',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query): array
    {
        $output = $this->resolveSearchOutput($query);
        $items = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $parts = preg_split('/\s+-\s+/', $trimmed, 2);
            $nameAndArchitecture = trim($parts[0] ?? '');
            if ($nameAndArchitecture === '') {
                continue;
            }

            $nameParts = explode(':', $nameAndArchitecture, 2);
            $items[] = [
                'name' => $nameParts[0],
                'architecture' => $nameParts[1] ?? null,
                'summary' => isset($parts[1]) ? trim($parts[1]) : null,
            ];
        }

        return [
            'kind' => 'system-api-packages-search',
            'status' => 'ok',
            'query' => $query,
            'backend' => 'apt-cache',
            'items' => $items,
            'count' => count($items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function info(string $name, ?string $architecture = null): array
    {
        $showOutput = $this->resolveInfoShowOutput($name, $architecture);
        if (trim($showOutput) === '') {
            return [
                'kind' => 'system-api-package-info',
                'status' => 'package-not-found',
                'backend' => 'apt-cache',
                'package' => [
                    'name' => $name,
                    'architecture' => $architecture,
                ],
            ];
        }

        $fields = $this->parseDebianControlBlock($showOutput);
        $policy = $this->parsePolicyBlock($this->resolveInfoPolicyOutput($name, $architecture));

        return [
            'kind' => 'system-api-package-info',
            'status' => 'ok',
            'backend' => 'apt-cache',
            'package' => [
                'name' => $fields['Package'] ?? $name,
                'architecture' => $fields['Architecture'] ?? $architecture,
                'source' => $fields['Source'] ?? null,
                'section' => $fields['Section'] ?? null,
                'priority' => $fields['Priority'] ?? null,
                'installed_version' => $policy['Installed'] ?? null,
                'candidate_version' => $policy['Candidate'] ?? null,
                'version' => $fields['Version'] ?? null,
                'homepage' => $fields['Homepage'] ?? null,
                'summary' => $fields['Description'] ?? null,
                'description' => $fields['_long_description'] ?? ($fields['Description'] ?? null),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listInstalled(?int $limit = null): array
    {
        $output = $this->resolveInstalledListOutput();
        $items = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $parts = preg_split('/\t+/', $trimmed);
            if (!is_array($parts) || count($parts) < 4) {
                continue;
            }

            $items[] = [
                'name' => trim($parts[0]),
                'architecture' => trim($parts[1]),
                'version' => trim($parts[2]),
                'status' => trim($parts[3]),
            ];
        }

        if (is_int($limit) && $limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return [
            'kind' => 'system-api-packages-installed-list',
            'status' => 'ok',
            'backend' => 'dpkg-query',
            'items' => $items,
            'count' => count($items),
            'limit' => $limit,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $selection
     * @return array<string, mixed>
     */
    private function resolvePlanPayload(string $action, array $selection, string $conffilePolicy): array
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_PLAN_JSON');
        if (is_string($fixture)) {
            $plan = $this->decodeJsonObject($fixture, 'W4_SYSTEM_API_PACKAGES_PLAN_JSON');

            return $this->ensurePlanHashes($plan, $action, $selection, $conffilePolicy);
        }

        $this->assertLinuxCommandSupport('packages.plan', 'apt-get');
        $this->assertLinuxCommandSupport('packages.plan', 'apt-cache');

        $simulation = $this->resolvePlanSimulationOutput($action, $selection, $conffilePolicy);
        $requestedPackages = [];
        foreach ($selection as $target) {
            $snapshot = $this->info($target['name'], $target['architecture']);
            $package = is_array($snapshot['package'] ?? null) ? $snapshot['package'] : [];
            $requestedPackages[] = [
                'name' => $target['name'],
                'architecture' => $target['architecture'],
                'requested_version' => $target['version'],
                'origin' => $target['origin'],
                'installed_version' => $package['installed_version'] ?? null,
                'candidate_version' => $package['candidate_version'] ?? null,
            ];
        }

        $plannedChanges = $this->parseSimulationChanges($simulation, $requestedPackages);
        $createdAt = gmdate('c');
        $expiresAt = gmdate('c', time() + 300);
        $planId = sprintf('pkg-plan-%s', substr(sha1(json_encode([$action, $selection, $conffilePolicy], JSON_THROW_ON_ERROR)), 0, 12));

        $plan = [
            'kind' => 'package-plan',
            'status' => 'ok',
            'backend' => 'apt-get',
            'action' => $action,
            'plan_id' => $planId,
            'actor_scope' => 'local-system-api',
            'catalog_revision' => sha1($simulation),
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
            'requested_packages' => $requestedPackages,
            'installs' => $plannedChanges['installs'],
            'upgrades' => $plannedChanges['upgrades'],
            'removals' => $plannedChanges['removals'],
            'download_bytes' => $plannedChanges['download_bytes'],
            'extra_disk_bytes' => $plannedChanges['extra_disk_bytes'],
            'conffile_policy' => $conffilePolicy,
            'protected_packages' => [],
            'reboot_estimate' => 'unknown',
            'simulated_command' => $this->renderCommand($this->buildPlanCommand($action, $selection, $conffilePolicy)),
            'summary' => [
                'requested' => count($requestedPackages),
                'installs' => count($plannedChanges['installs']),
                'upgrades' => count($plannedChanges['upgrades']),
                'removals' => count($plannedChanges['removals']),
            ],
        ];

        return $this->ensurePlanHashes($plan, $action, $selection, $conffilePolicy);
    }

    /**
     * @param array<int, array<string, mixed>> $selection
     */
    private function resolvePlanSimulationOutput(string $action, array $selection, string $conffilePolicy): string
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_PLAN_SIMULATION_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $result = $this->runLinuxCommand($this->buildPlanCommand($action, $selection, $conffilePolicy), allowFailure: true);
        if ($result['exit_code'] !== 0) {
            throw new ValidationError(sprintf(
                'Backend no disponible: packages.plan no pudo resolver el plan (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : 'apt-get simulation failed'
            ));
        }

        return $result['stdout'];
    }

    /**
     * @param array<string, mixed>|null $plan
     * @return array<string, mixed>
     */
    private function resolveApplyPlan(?array $plan, ?string $planPath): array
    {
        if ($plan !== null) {
            return $plan;
        }

        if ($planPath !== null) {
            $raw = file_get_contents($planPath);
            if (!is_string($raw)) {
                throw new ValidationError(sprintf('params.plan_path no se pudo leer: %s', $planPath));
            }

            return $this->decodeJsonObject($raw, 'params.plan_path');
        }

        throw new ValidationError('params.plan o params.plan_path es obligatorio para packages.apply');
    }

    /**
     * @param array<string, mixed>|null $plan
     * @return array<string, mixed>
     */
    private function resolveUpdateApplyPlan(?array $plan, ?string $planPath): array
    {
        if ($plan !== null) {
            (new UpdateToolkit())->validateUpdatePlan($plan, 'params.plan');

            return $plan;
        }

        if ($planPath !== null) {
            $raw = file_get_contents($planPath);
            if (!is_string($raw)) {
                throw new ValidationError(sprintf('params.plan_path no se pudo leer: %s', $planPath));
            }

            $decoded = $this->decodeJsonObject($raw, 'params.plan_path');
            (new UpdateToolkit())->validateUpdatePlan($decoded, $planPath);

            return $decoded;
        }

        throw new ValidationError('params.plan o params.plan_path es obligatorio para updates.apply');
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function assertPlanMatches(array $plan, string $planId, string $planHash): void
    {
        $resolvedPlanId = $plan['plan_id'] ?? null;
        $resolvedPlanHash = $plan['plan_hash'] ?? null;

        if (!is_string($resolvedPlanId) || trim($resolvedPlanId) === '') {
            throw new ValidationError('params.plan.plan_id debe estar presente');
        }

        if (!is_string($resolvedPlanHash) || trim($resolvedPlanHash) === '') {
            throw new ValidationError('params.plan.plan_hash debe estar presente');
        }

        $expectedHash = $this->computePlanHash($plan);
        if ($expectedHash !== $planHash || trim($resolvedPlanHash) !== $planHash || trim($resolvedPlanId) !== $planId) {
            throw new ValidationError('Conflicto: plan_id o plan_hash no coinciden con el plan suministrado');
        }
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function assertCatalogRevisionMatches(array $plan, ?string $expectedCatalogRevision): ?string
    {
        $observedCatalogRevision = $plan['catalog_revision'] ?? null;
        if ($observedCatalogRevision !== null && !is_string($observedCatalogRevision)) {
            throw new ValidationError('params.plan.catalog_revision debe ser un string cuando esta presente');
        }

        if ($expectedCatalogRevision !== null && $observedCatalogRevision !== $expectedCatalogRevision) {
            throw new ValidationError(sprintf(
                'Conflicto: expected_catalog_revision no coincide con el plan suministrado (esperada=%s, observada=%s)',
                $expectedCatalogRevision,
                is_string($observedCatalogRevision) ? $observedCatalogRevision : 'null'
            ));
        }

        return is_string($observedCatalogRevision) ? $observedCatalogRevision : null;
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function assertUpdatePlanMatches(array $plan, string $operationId, string $planHash): void
    {
        $resolvedOperationId = $plan['operation_id'] ?? null;
        if (!is_string($resolvedOperationId) || trim($resolvedOperationId) === '') {
            throw new ValidationError('params.plan.operation_id debe estar presente');
        }

        $expectedHash = $this->computeUpdatePlanHash($plan);
        if (trim($resolvedOperationId) !== $operationId || $expectedHash !== $planHash) {
            throw new ValidationError('Conflicto: operation_id o plan_hash no coinciden con el update-plan suministrado');
        }
    }

    /**
     * @param array<string, mixed> $plan
     * @param array{stdout:string,stderr:string,exit_code:int} $result
     * @return array<string, mixed>
     */
    private function buildUpdatesDurableState(array $plan, bool $succeeded, array $result): array
    {
        $template = $plan['durable_state_template'] ?? null;
        if ($template !== null && !is_array($template)) {
            throw new ValidationError('params.plan.durable_state_template debe ser un objeto');
        }

        $timestamps = is_array($template['timestamps'] ?? null) ? $template['timestamps'] : [];
        $observedAt = gmdate('c');
        $stderr = is_string($result['stderr'] ?? null) ? trim($result['stderr']) : '';

        $durableState = array_merge($template ?? [], [
            'update_operation_schema_version' => 1,
            'kind' => 'update-operation',
            'operation_id' => $plan['operation_id'],
            'profile_id' => $plan['profile_id'],
            'source_version' => $plan['source_version'],
            'target_version' => $plan['target_version'],
            'stage' => $succeeded ? 'prepared' : 'failed',
            'repository_snapshot' => is_array($plan['repository_snapshot'] ?? null) ? $plan['repository_snapshot'] : [],
            'snapshot' => is_array($plan['snapshot'] ?? null) ? $plan['snapshot'] : [],
            'boot_manifest' => is_array($plan['boot_manifest'] ?? null) ? $plan['boot_manifest'] : [],
            'plan_hash' => $this->computeUpdatePlanHash($plan),
            'apply_mode' => is_string($plan['execution']['apply_mode'] ?? null) ? $plan['execution']['apply_mode'] : null,
            'reboot_required' => (($plan['execution']['reboot_required'] ?? false) === true),
            'last_error' => $succeeded ? null : ($stderr !== '' ? $stderr : 'updates.apply failed'),
            'timestamps' => [
                'created_at' => is_string($timestamps['created_at'] ?? null) ? $timestamps['created_at'] : $observedAt,
                'updated_at' => $observedAt,
            ],
        ]);

        (new UpdateToolkit())->validateOperation($durableState, 'updates.apply durable_state');

        return $durableState;
    }

    /**
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function buildUpdatesPostRebootContract(array $plan, bool $succeeded): array
    {
        $manifest = $plan['health_check_manifest'] ?? null;
        if ($manifest !== null && !is_array($manifest)) {
            throw new ValidationError('params.plan.health_check_manifest debe ser un objeto');
        }

        return [
            'pending_state' => is_string($manifest['pending_state'] ?? null) ? trim((string) $manifest['pending_state']) : 'pending_health',
            'success_state' => is_string($manifest['success_state'] ?? null) ? trim((string) $manifest['success_state']) : 'confirmed',
            'failure_state' => is_string($manifest['failure_state'] ?? null) ? trim((string) $manifest['failure_state']) : 'failed',
            'required_checks' => $this->normalizeOptionalStringList($manifest['required'] ?? null, 'params.plan.health_check_manifest.required'),
            'test_file_path' => is_string($manifest['test_file_path'] ?? null) ? trim((string) $manifest['test_file_path']) : null,
            'resume_from_stage' => $succeeded ? 'prepared' : 'failed',
            'next_action' => $succeeded ? 'run-post-reboot-health-checks' : 'inspect-update-state',
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $postReboot
     * @return array<string, mixed>
     */
    private function buildUpdatesCoordinationContract(array $plan, bool $succeeded, array $postReboot): array
    {
        return [
            'exclusive_scope' => 'system-updates',
            'mutating_operation_policy' => 'single-active-operation',
            'busy_state' => $succeeded ? 'pending-reboot-health-reconciliation' : 'failed-before-reboot',
            'reboot_required' => (($plan['execution']['reboot_required'] ?? false) === true),
            'next_stage' => $succeeded
                ? (is_string($postReboot['pending_state'] ?? null) ? $postReboot['pending_state'] : 'pending_health')
                : 'failed',
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @return array{stdout:string,stderr:string,exit_code:int}
     */
    private function resolveApplyExecutionResult(array $plan): array
    {
        $stdoutFixture = getenv('W4_SYSTEM_API_PACKAGES_APPLY_STDOUT');
        $stderrFixture = getenv('W4_SYSTEM_API_PACKAGES_APPLY_STDERR');
        $exitCodeFixture = getenv('W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE');
        if (is_string($stdoutFixture) || is_string($stderrFixture) || is_string($exitCodeFixture)) {
            return [
                'stdout' => is_string($stdoutFixture) ? $stdoutFixture : '',
                'stderr' => is_string($stderrFixture) ? $stderrFixture : '',
                'exit_code' => is_string($exitCodeFixture) && preg_match('/^-?\d+$/', $exitCodeFixture) === 1
                    ? (int) $exitCodeFixture
                    : 0,
            ];
        }

        $this->assertLinuxCommandSupport('packages.apply', 'apt-get');

        return $this->runLinuxCommand($this->buildApplyCommand($plan), allowFailure: true);
    }

    /**
     * @param array<string, mixed> $plan
     * @return array{stdout:string,stderr:string,exit_code:int}
     */
    private function resolveUpdatesApplyExecutionResult(array $plan): array
    {
        $stdoutFixture = getenv('W4_SYSTEM_API_UPDATES_APPLY_STDOUT');
        $stderrFixture = getenv('W4_SYSTEM_API_UPDATES_APPLY_STDERR');
        $exitCodeFixture = getenv('W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE');
        if (is_string($stdoutFixture) || is_string($stderrFixture) || is_string($exitCodeFixture)) {
            return [
                'stdout' => is_string($stdoutFixture) ? $stdoutFixture : '',
                'stderr' => is_string($stderrFixture) ? $stderrFixture : '',
                'exit_code' => is_string($exitCodeFixture) && preg_match('/^-?\d+$/', $exitCodeFixture) === 1
                    ? (int) $exitCodeFixture
                    : 0,
            ];
        }

        $commands = $this->buildUpdatesApplyCommands($plan);
        if ($commands === []) {
            throw new ValidationError('params.plan.package_changes no contiene acciones aplicables');
        }

        $stdoutParts = [];
        $stderrParts = [];
        foreach ($commands as $command) {
            $this->assertLinuxCommandSupport('updates.apply', 'apt-get');
            $result = $this->runLinuxCommand($command, allowFailure: true);
            if (trim($result['stdout']) !== '') {
                $stdoutParts[] = trim($result['stdout']);
            }

            if (trim($result['stderr']) !== '') {
                $stderrParts[] = trim($result['stderr']);
            }

            if (($result['exit_code'] ?? 1) !== 0) {
                return [
                    'stdout' => implode(PHP_EOL, $stdoutParts),
                    'stderr' => implode(PHP_EOL, $stderrParts),
                    'exit_code' => (int) $result['exit_code'],
                ];
            }
        }

        return [
            'stdout' => implode(PHP_EOL, $stdoutParts),
            'stderr' => implode(PHP_EOL, $stderrParts),
            'exit_code' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @return list<string>
     */
    private function buildApplyCommand(array $plan): array
    {
        $action = $this->normalizeAction((string) ($plan['action'] ?? ''));
        $requestedPackages = $plan['requested_packages'] ?? null;
        if (!is_array($requestedPackages) || $requestedPackages === []) {
            throw new ValidationError('params.plan.requested_packages debe contener al menos un paquete');
        }

        $selection = [];
        foreach ($requestedPackages as $package) {
            if (!is_array($package)) {
                throw new ValidationError('params.plan.requested_packages debe ser una lista de objetos');
            }

            $selection[] = [
                'name' => (string) ($package['name'] ?? ''),
                'architecture' => isset($package['architecture']) && is_string($package['architecture']) ? $package['architecture'] : null,
                'version' => isset($package['requested_version']) && is_string($package['requested_version']) ? $package['requested_version'] : null,
                'origin' => isset($package['origin']) && is_string($package['origin']) ? $package['origin'] : null,
            ];
        }

        $command = ['apt-get', '-y', '-o', 'APT::Color=0'];
        foreach ($this->buildConffileOptions((string) ($plan['conffile_policy'] ?? 'keep-local')) as $option) {
            $command[] = $option;
        }

        if ($action === 'upgrade') {
            $command[] = 'install';
            $command[] = '--only-upgrade';
        } else {
            $command[] = $action;
        }

        foreach ($selection as $target) {
            $command[] = $this->renderPackageSelector($target);
        }

        return $command;
    }

    /**
     * @param array<string, mixed> $plan
     * @return list<list<string>>
     */
    private function buildUpdatesApplyCommands(array $plan): array
    {
        $packageChanges = $plan['package_changes'] ?? null;
        if (!is_array($packageChanges)) {
            throw new ValidationError('params.plan.package_changes debe ser un objeto');
        }

        $install = $this->normalizeStringList($packageChanges['install'] ?? [], 'params.plan.package_changes.install');
        $upgrade = $this->normalizeStringList($packageChanges['upgrade'] ?? [], 'params.plan.package_changes.upgrade');
        $remove = $this->normalizeStringList($packageChanges['remove'] ?? [], 'params.plan.package_changes.remove');

        $commands = [];
        $installAndUpgrade = array_values(array_unique(array_merge($install, $upgrade)));
        if ($installAndUpgrade !== []) {
            $command = ['apt-get', '-y', '-o', 'APT::Color=0', 'install'];
            foreach ($installAndUpgrade as $package) {
                $command[] = $package;
            }

            $commands[] = $command;
        }

        if ($remove !== []) {
            $command = ['apt-get', '-y', '-o', 'APT::Color=0', 'remove'];
            foreach ($remove as $package) {
                $command[] = $package;
            }

            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @param array<int, array<string, mixed>> $selection
     * @return list<string>
     */
    private function buildPlanCommand(string $action, array $selection, string $conffilePolicy): array
    {
        $command = ['apt-get', '-s', '-o', 'APT::Color=0'];
        foreach ($this->buildConffileOptions($conffilePolicy) as $option) {
            $command[] = $option;
        }

        if ($action === 'upgrade') {
            $command[] = 'install';
            $command[] = '--only-upgrade';
        } else {
            $command[] = $action;
        }

        foreach ($selection as $target) {
            $command[] = $this->renderPackageSelector($target);
        }

        return $command;
    }

    /**
     * @param list<list<string>> $commands
     * @return list<string>
     */
    private function renderCommandSequence(array $commands): array
    {
        $rendered = [];
        foreach ($commands as $command) {
            $rendered[] = $this->renderCommand($command);
        }

        return $rendered;
    }

    /**
     * @param array<string, mixed> $target
     */
    private function renderPackageSelector(array $target): string
    {
        $selector = $target['name'];
        if (is_string($target['architecture'] ?? null) && trim($target['architecture']) !== '') {
            $selector .= ':' . trim($target['architecture']);
        }

        if (is_string($target['version'] ?? null) && trim($target['version']) !== '') {
            $selector .= '=' . trim($target['version']);
        }

        return $selector;
    }

    /**
     * @return list<string>
     */
    private function buildConffileOptions(string $conffilePolicy): array
    {
        return match ($this->normalizeConffilePolicy($conffilePolicy)) {
            'keep-local' => ['-o', 'Dpkg::Options::=--force-confold'],
            'adopt-new' => ['-o', 'Dpkg::Options::=--force-confnew'],
            'review-required' => ['-o', 'Dpkg::Options::=--force-confdef'],
        };
    }

    /**
     * @param array<int, array<string, mixed>> $selection
     * @return list<array{name:string,architecture:?string,version:?string,origin:?string}>
     */
    private function normalizeSelection(array $selection): array
    {
        if ($selection === [] || array_is_list($selection) === false) {
            throw new ValidationError('params.selection debe ser una lista no vacia');
        }

        $normalized = [];
        foreach ($selection as $index => $target) {
            if (!is_array($target)) {
                throw new ValidationError(sprintf('params.selection[%d] debe ser un objeto', $index));
            }

            $name = $target['name'] ?? null;
            if (!is_string($name) || trim($name) === '') {
                throw new ValidationError(sprintf('params.selection[%d].name debe ser un string no vacio', $index));
            }

            $normalized[] = [
                'name' => trim($name),
                'architecture' => isset($target['architecture']) && is_string($target['architecture']) && trim($target['architecture']) !== ''
                    ? trim($target['architecture'])
                    : null,
                'version' => isset($target['version']) && is_string($target['version']) && trim($target['version']) !== ''
                    ? trim($target['version'])
                    : null,
                'origin' => isset($target['origin']) && is_string($target['origin']) && trim($target['origin']) !== ''
                    ? trim($target['origin'])
                    : null,
            ];
        }

        return $normalized;
    }

    private function normalizeAction(string $action): string
    {
        $normalized = trim($action);
        if (!in_array($normalized, ['install', 'remove', 'upgrade'], true)) {
            throw new ValidationError('params.action debe ser install, remove o upgrade');
        }

        return $normalized;
    }

    private function normalizeConffilePolicy(string $policy): string
    {
        $normalized = trim($policy);
        if (!in_array($normalized, ['keep-local', 'adopt-new', 'review-required'], true)) {
            throw new ValidationError('params.conffile_policy debe ser keep-local, adopt-new o review-required');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<int, array<string, mixed>> $selection
     * @return array<string, mixed>
     */
    private function ensurePlanHashes(array $plan, string $action, array $selection, string $conffilePolicy): array
    {
        $plan['kind'] = 'package-plan';
        $plan['status'] = is_string($plan['status'] ?? null) ? $plan['status'] : 'ok';
        $plan['backend'] = is_string($plan['backend'] ?? null) ? $plan['backend'] : 'apt-get';
        $plan['action'] = is_string($plan['action'] ?? null) ? $plan['action'] : $action;
        $plan['requested_packages'] = is_array($plan['requested_packages'] ?? null) ? $plan['requested_packages'] : $selection;
        $plan['conffile_policy'] = is_string($plan['conffile_policy'] ?? null) ? $plan['conffile_policy'] : $conffilePolicy;
        $plan['created_at'] = is_string($plan['created_at'] ?? null) ? $plan['created_at'] : gmdate('c');
        $plan['expires_at'] = is_string($plan['expires_at'] ?? null) ? $plan['expires_at'] : gmdate('c', time() + 300);
        $plan['plan_id'] = is_string($plan['plan_id'] ?? null) && trim((string) $plan['plan_id']) !== ''
            ? trim((string) $plan['plan_id'])
            : sprintf('pkg-plan-%s', substr(sha1(json_encode([$action, $selection, $conffilePolicy], JSON_THROW_ON_ERROR)), 0, 12));
        $plan['catalog_revision'] = is_string($plan['catalog_revision'] ?? null)
            ? $plan['catalog_revision']
            : sha1(json_encode($plan['requested_packages'], JSON_THROW_ON_ERROR));
        $plan['installs'] = is_array($plan['installs'] ?? null) ? $plan['installs'] : [];
        $plan['upgrades'] = is_array($plan['upgrades'] ?? null) ? $plan['upgrades'] : [];
        $plan['removals'] = is_array($plan['removals'] ?? null) ? $plan['removals'] : [];
        $plan['download_bytes'] = is_int($plan['download_bytes'] ?? null) ? $plan['download_bytes'] : 0;
        $plan['extra_disk_bytes'] = is_int($plan['extra_disk_bytes'] ?? null) ? $plan['extra_disk_bytes'] : 0;
        $plan['protected_packages'] = is_array($plan['protected_packages'] ?? null) ? $plan['protected_packages'] : [];
        $plan['reboot_estimate'] = is_string($plan['reboot_estimate'] ?? null) ? $plan['reboot_estimate'] : 'unknown';
        $plan['summary'] = is_array($plan['summary'] ?? null) ? $plan['summary'] : [
            'requested' => count(is_array($plan['requested_packages']) ? $plan['requested_packages'] : []),
            'installs' => count($plan['installs']),
            'upgrades' => count($plan['upgrades']),
            'removals' => count($plan['removals']),
        ];
        $plan['plan_hash'] = $this->computePlanHash($plan);

        return $plan;
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function computePlanHash(array $plan): string
    {
        unset($plan['plan_hash'], $plan['command'], $plan['output_path']);

        $encoded = json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar el plan de paquetes');
        }

        return sha1($encoded);
    }

    /**
     * @param array<int, array<string, mixed>> $requestedPackages
     * @return array{installs:array<int, array<string, mixed>>,upgrades:array<int, array<string, mixed>>,removals:array<int, array<string, mixed>>,download_bytes:int,extra_disk_bytes:int}
     */
    private function parseSimulationChanges(string $output, array $requestedPackages): array
    {
        $installs = [];
        $upgrades = [];
        $removals = [];
        $requestedByName = [];
        foreach ($requestedPackages as $package) {
            $requestedByName[(string) ($package['name'] ?? '')] = $package;
        }

        foreach (preg_split('/\r?\n/', $output) ?: [] as $line) {
            if (preg_match('/^Inst\s+([^\s:]+)(?::([^\s]+))?(?:\s+\[([^\]]+)\])?(?:\s+\(([^)]+)\))?/', trim($line), $matches) === 1) {
                $name = $matches[1];
                $architecture = $matches[2] ?? ($requestedByName[$name]['architecture'] ?? null);
                $installedVersion = $requestedByName[$name]['installed_version'] ?? ($matches[3] ?? null);
                $targetVersion = null;
                if (isset($matches[4])) {
                    $targetVersion = trim(explode(' ', trim($matches[4]))[0]);
                }

                $entry = [
                    'name' => $name,
                    'architecture' => $architecture !== '' ? $architecture : null,
                    'installed_version' => $installedVersion !== '' ? $installedVersion : null,
                    'target_version' => $targetVersion,
                ];

                if ($installedVersion !== null && $installedVersion !== '' && $installedVersion !== $targetVersion) {
                    $upgrades[] = $entry;
                } else {
                    $installs[] = $entry;
                }

                continue;
            }

            if (preg_match('/^Remv\s+([^\s:]+)(?::([^\s]+))?(?:\s+\[([^\]]+)\])?/', trim($line), $matches) === 1) {
                $removals[] = [
                    'name' => $matches[1],
                    'architecture' => isset($matches[2]) && $matches[2] !== '' ? $matches[2] : null,
                    'installed_version' => $matches[3] ?? null,
                ];
            }
        }

        return [
            'installs' => $installs,
            'upgrades' => $upgrades,
            'removals' => $removals,
            'download_bytes' => $this->extractApproximateBytes($output, '/Need to get\s+([0-9.,]+)\s*([kMGT]?B)/i'),
            'extra_disk_bytes' => $this->extractApproximateBytes($output, '/After this operation,\s+([0-9.,]+)\s*([kMGT]?B)/i'),
        ];
    }

    private function extractApproximateBytes(string $output, string $pattern): int
    {
        if (preg_match($pattern, $output, $matches) !== 1) {
            return 0;
        }

        $amount = str_replace(',', '.', $matches[1]);
        if (!is_numeric($amount)) {
            return 0;
        }

        $multiplier = match (strtoupper($matches[2] ?? 'B')) {
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
            default => 1,
        };

        return (int) round((float) $amount * $multiplier);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $json, string $label): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('%s contiene JSON invalido: %s', $label, $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('%s debe contener un objeto JSON', $label));
        }

        return $decoded;
    }

    private function writeJsonFile(string $path, array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new ValidationError(sprintf('No se pudo serializar el plan para %s', $path));
        }

        $result = file_put_contents($path, $encoded . PHP_EOL);
        if ($result === false) {
            throw new ValidationError(sprintf('No se pudo escribir el plan en %s', $path));
        }
    }

    /**
     * @param list<string> $command
     */
    private function renderCommand(array $command): string
    {
        $parts = [];
        foreach ($command as $part) {
            $parts[] = preg_match('/^[A-Za-z0-9._:=\\/-]+$/', $part) === 1 ? $part : escapeshellarg($part);
        }

        return implode(' ', $parts);
    }

    private function resolveSearchOutput(string $query): string
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_SEARCH_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('packages.search', 'apt-cache');
        $result = $this->runLinuxCommand(['apt-cache', 'search', '--names-only', $query]);

        return $result['stdout'];
    }

    private function resolveInfoShowOutput(string $name, ?string $architecture): string
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_INFO_SHOW_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('packages.info', 'apt-cache');
        $packageSelector = $architecture !== null ? sprintf('%s:%s', $name, $architecture) : $name;
        $result = $this->runLinuxCommand(['apt-cache', 'show', $packageSelector], allowFailure: true);

        return $result['stdout'];
    }

    private function resolveInfoPolicyOutput(string $name, ?string $architecture): string
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_INFO_POLICY_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('packages.info', 'apt-cache');
        $packageSelector = $architecture !== null ? sprintf('%s:%s', $name, $architecture) : $name;
        $result = $this->runLinuxCommand(['apt-cache', 'policy', $packageSelector], allowFailure: true);

        return $result['stdout'];
    }

    private function resolveInstalledListOutput(): string
    {
        $fixture = getenv('W4_SYSTEM_API_PACKAGES_LIST_INSTALLED_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('packages.list_installed', 'dpkg-query');
        $result = $this->runLinuxCommand([
            'dpkg-query',
            '-W',
            '-f=${binary:Package}\t${Architecture}\t${Version}\t${db:Status-Abbrev}\n',
        ]);

        return $result['stdout'];
    }

    private function resolveUpdatesListOutput(): string
    {
        $fixture = getenv('W4_SYSTEM_API_UPDATES_LIST_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('updates.list', 'apt');
        $result = $this->runLinuxCommand(['apt', 'list', '--upgradable'], allowFailure: true);
        if ($result['exit_code'] !== 0) {
            throw new ValidationError(sprintf(
                'Backend no disponible: updates.list no pudo consultar actualizaciones (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : 'apt list --upgradable failed'
            ));
        }

        return $result['stdout'];
    }

    /**
     * @return array<string, mixed>
     */
    private function refreshUpdateCatalog(): array
    {
        $stdoutFixture = getenv('W4_SYSTEM_API_UPDATES_REFRESH_STDOUT');
        $stderrFixture = getenv('W4_SYSTEM_API_UPDATES_REFRESH_STDERR');
        $exitCodeFixture = getenv('W4_SYSTEM_API_UPDATES_REFRESH_EXIT_CODE');

        if (is_string($stdoutFixture) || is_string($stderrFixture) || is_string($exitCodeFixture)) {
            $exitCode = is_string($exitCodeFixture) && preg_match('/^-?\d+$/', $exitCodeFixture) === 1
                ? (int) $exitCodeFixture
                : 0;
            if ($exitCode !== 0) {
                throw new ValidationError(sprintf(
                    'Backend no disponible: updates.check no pudo refrescar el catalogo (%s)',
                    is_string($stderrFixture) && trim($stderrFixture) !== '' ? trim($stderrFixture) : 'apt-get update failed'
                ));
            }

            return [
                'status' => 'ok',
                'command' => 'apt-get update',
                'stdout' => is_string($stdoutFixture) ? $stdoutFixture : '',
                'stderr' => is_string($stderrFixture) ? $stderrFixture : '',
                'exit_code' => $exitCode,
            ];
        }

        $this->assertLinuxCommandSupport('updates.check', 'apt-get');
        $result = $this->runLinuxCommand(['apt-get', 'update'], allowFailure: true);
        if ($result['exit_code'] !== 0) {
            throw new ValidationError(sprintf(
                'Backend no disponible: updates.check no pudo refrescar el catalogo (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : 'apt-get update failed'
            ));
        }

        return [
            'status' => 'ok',
            'command' => 'apt-get update',
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
            'exit_code' => $result['exit_code'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseDebianControlBlock(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content) ?: [];
        $fields = [];
        $currentField = null;
        $firstBlockCompleted = false;

        foreach ($lines as $line) {
            if ($line === '') {
                if ($currentField !== null) {
                    $currentField = null;
                } elseif ($fields !== []) {
                    $firstBlockCompleted = true;
                }

                if ($firstBlockCompleted) {
                    break;
                }

                continue;
            }

            if (preg_match('/^([A-Za-z0-9-]+):\s*(.*)$/', $line, $matches) === 1) {
                $currentField = $matches[1];
                $fields[$currentField] = $matches[2];
                continue;
            }

            if ($currentField !== null && str_starts_with($line, ' ')) {
                $continuation = ltrim($line);
                if ($currentField === 'Description') {
                    $existing = $fields['_long_description'] ?? $fields['Description'] ?? '';
                    $fields['_long_description'] = trim($existing . "\n" . $continuation);
                } else {
                    $fields[$currentField] = trim(($fields[$currentField] ?? '') . "\n" . $continuation);
                }
            }
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function parsePolicyBlock(string $content): array
    {
        $fields = [];

        foreach (preg_split('/\r?\n/', $content) ?: [] as $line) {
            if (preg_match('/^\s*([A-Za-z][A-Za-z ]+):\s*(.+)$/', $line, $matches) === 1) {
                $fields[trim($matches[1])] = trim($matches[2]);
            }
        }

        return $fields;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeStringList(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf('%s debe ser una lista de strings', $field));
        }

        $items = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || trim($entry) === '') {
                throw new ValidationError(sprintf('%s debe ser una lista de strings no vacios', $field));
            }

            $items[] = trim($entry);
        }

        return $items;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeOptionalStringList(mixed $value, string $field): array
    {
        if ($value === null) {
            return [];
        }

        return $this->normalizeStringList($value, $field);
    }


    /**
     * @return list<array<string, mixed>>
     */
    private function parseUpgradablePackages(string $output): array
    {
        $items = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, 'Listing...')) {
                continue;
            }

            if (preg_match('/^([^\/\s:]+)(?::([^\s\/]+))?\/([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+\[upgradable from:\s*(.+)\]$/', $trimmed, $matches) !== 1) {
                continue;
            }

            $items[] = [
                'name' => trim($matches[1]),
                'architecture' => trim($matches[2] ?? '') !== '' ? trim($matches[2]) : null,
                'origin' => trim($matches[3]),
                'candidate_version' => trim($matches[4]),
                'channel_architecture' => trim($matches[5]),
                'installed_version' => trim($matches[6]),
            ];
        }

        return $items;
    }

    private function assertLinuxCommandSupport(string $method, string $command): void
    {
        if ($this->resolveOsFamily() !== 'Linux') {
            throw new ValidationError(sprintf('Metodo no soportado: %s requiere un host Linux con %s', $method, $command));
        }

        if (!$this->commandExists($command)) {
            throw new ValidationError(sprintf('Metodo no soportado: %s requiere %s disponible en el host', $method, $command));
        }
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function computeUpdatePlanHash(array $plan): string
    {
        $encoded = json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar el update-plan');
        }

        return sha1($encoded);
    }

    private function resolveOsFamily(): string
    {
        $override = getenv('W4_SYSTEM_API_HOST_OS_FAMILY');
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        $probeOverride = getenv('W4_SYSTEM_API_PROBE_OS_FAMILY');
        if (is_string($probeOverride) && trim($probeOverride) !== '') {
            return trim($probeOverride);
        }

        return PHP_OS_FAMILY;
    }

    private function commandExists(string $command): bool
    {
        if (in_array($command, ['apt', 'apt-cache', 'apt-get', 'dpkg-query'], true) && $this->hasPackageFixtureOverrides()) {
            return true;
        }

        $result = $this->runLinuxCommand(['sh', '-lc', sprintf('command -v %s', escapeshellarg($command))], allowFailure: true);

        return $result['exit_code'] === 0 && trim($result['stdout']) !== '';
    }

    private function hasPackageFixtureOverrides(): bool
    {
        foreach ([
            'W4_SYSTEM_API_PACKAGES_SEARCH_OUTPUT',
            'W4_SYSTEM_API_PACKAGES_INFO_SHOW_OUTPUT',
            'W4_SYSTEM_API_PACKAGES_INFO_POLICY_OUTPUT',
            'W4_SYSTEM_API_PACKAGES_LIST_INSTALLED_OUTPUT',
            'W4_SYSTEM_API_UPDATES_LIST_OUTPUT',
            'W4_SYSTEM_API_UPDATES_REFRESH_STDOUT',
            'W4_SYSTEM_API_UPDATES_REFRESH_STDERR',
            'W4_SYSTEM_API_UPDATES_REFRESH_EXIT_CODE',
            'W4_SYSTEM_API_UPDATES_APPLY_STDOUT',
            'W4_SYSTEM_API_UPDATES_APPLY_STDERR',
            'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE',
            'W4_SYSTEM_API_PACKAGES_PLAN_JSON',
            'W4_SYSTEM_API_PACKAGES_PLAN_SIMULATION_OUTPUT',
            'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT',
            'W4_SYSTEM_API_PACKAGES_APPLY_STDERR',
            'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE',
        ] as $key) {
            if (getenv($key) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $command
     * @return array{stdout:string,stderr:string,exit_code:int}
     */
    private function runLinuxCommand(array $command, bool $allowFailure = false): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, null, $_ENV);
        if (!is_resource($process)) {
            throw new ValidationError(sprintf('No se pudo ejecutar el comando requerido: %s', implode(' ', $command)));
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $result = [
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
            'exit_code' => is_int($exitCode) ? $exitCode : 1,
        ];

        if (!$allowFailure && $result['exit_code'] !== 0) {
            throw new ValidationError(sprintf(
                'Metodo no soportado: el backend de paquetes no pudo ejecutarse (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : implode(' ', $command)
            ));
        }

        return $result;
    }
}
