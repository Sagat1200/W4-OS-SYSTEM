<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiServicesToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function start(string $unit, ?string $expectedRevision = null): array
    {
        return $this->runAction('start', $unit, $expectedRevision);
    }

    /**
     * @return array<string, mixed>
     */
    public function stop(string $unit, ?string $expectedRevision = null): array
    {
        return $this->runAction('stop', $unit, $expectedRevision);
    }

    /**
     * @return array<string, mixed>
     */
    public function restart(string $unit, ?string $expectedRevision = null): array
    {
        return $this->runAction('restart', $unit, $expectedRevision);
    }

    /**
     * @return array<string, mixed>
     */
    public function enable(string $unit, ?string $expectedRevision = null): array
    {
        return $this->runAction('enable', $unit, $expectedRevision);
    }

    /**
     * @return array<string, mixed>
     */
    public function disable(string $unit, ?string $expectedRevision = null): array
    {
        return $this->runAction('disable', $unit, $expectedRevision);
    }

    /**
     * @return array<string, mixed>
     */
    public function list(?string $unit = null): array
    {
        $output = $this->resolveListOutput();
        $items = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $trimmed);
            if (!is_array($parts) || count($parts) < 2) {
                continue;
            }

            $entry = [
                'unit' => $parts[0],
                'unit_file_state' => $parts[1],
                'vendor_preset' => $parts[2] ?? null,
            ];

            if ($unit === null || $entry['unit'] === $unit) {
                $items[] = $entry;
            }
        }

        return [
            'kind' => 'system-api-services-list',
            'status' => 'ok',
            'backend' => 'systemctl',
            'filter_unit' => $unit,
            'items' => $items,
            'count' => count($items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function status(string $unit): array
    {
        $output = $this->resolveStatusOutput($unit);
        $properties = $this->parseProperties($output);

        if ($properties === []) {
            return [
                'kind' => 'system-api-service-status',
                'status' => 'service-not-found',
                'backend' => 'systemctl',
                'revision' => null,
                'service' => [
                    'unit' => $unit,
                    'revision' => null,
                ],
            ];
        }

        $service = [
            'unit' => $properties['Id'] ?? $unit,
            'description' => $properties['Description'] ?? null,
            'load_state' => $properties['LoadState'] ?? null,
            'active_state' => $properties['ActiveState'] ?? null,
            'sub_state' => $properties['SubState'] ?? null,
            'unit_file_state' => $properties['UnitFileState'] ?? null,
            'fragment_path' => $properties['FragmentPath'] ?? null,
        ];
        $revision = $this->computeServiceRevision($service);
        $service['revision'] = $revision;

        return [
            'kind' => 'system-api-service-status',
            'status' => 'ok',
            'backend' => 'systemctl',
            'revision' => $revision,
            'service' => $service,
        ];
    }

    private function resolveListOutput(): string
    {
        $fixture = getenv('W4_SYSTEM_API_SERVICES_LIST_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('services.list', 'systemctl');
        $result = $this->runLinuxCommand([
            'systemctl',
            'list-unit-files',
            '--type=service',
            '--no-pager',
            '--no-legend',
        ]);

        return $result['stdout'];
    }

    /**
     * @return array<string, mixed>
     */
    private function runAction(string $action, string $unit, ?string $expectedRevision = null): array
    {
        $this->assertLinuxCommandSupport(sprintf('services.%s', $action), 'systemctl');
        $beforeStatus = $this->status($unit);
        $observedRevisionBefore = is_string($beforeStatus['revision'] ?? null) ? $beforeStatus['revision'] : null;
        if ($expectedRevision !== null && $observedRevisionBefore !== $expectedRevision) {
            throw new ValidationError(sprintf(
                'Conflicto: expected_revision no coincide para %s (esperada=%s, observada=%s)',
                $unit,
                $expectedRevision,
                $observedRevisionBefore ?? 'null'
            ));
        }

        $result = $this->resolveActionExecutionResult($action, $unit);
        $statusReport = $this->status($unit);
        $operationId = sprintf('service-%s-%s', $action, substr(sha1($unit . '|' . $action), 0, 12));
        $succeeded = ($result['exit_code'] ?? 1) === 0;
        $observedRevisionAfter = is_string($statusReport['revision'] ?? null) ? $statusReport['revision'] : null;

        return [
            'kind' => 'system-api-service-operation',
            'status' => $succeeded ? 'ok' : 'failed',
            'backend' => 'systemctl',
            'action' => $action,
            'unit' => $unit,
            'operation_id' => $operationId,
            'state' => $succeeded ? 'succeeded' : 'failed',
            'expected_revision' => $expectedRevision,
            'observed_revision_before' => $observedRevisionBefore,
            'observed_revision_after' => $observedRevisionAfter,
            'command' => sprintf('systemctl %s %s', $action, $unit),
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
            'service' => $statusReport['service'] ?? ['unit' => $unit],
            'next_action' => $succeeded ? 'observe-service-state' : 'inspect-service-state',
        ];
    }

    /**
     * @return array{stdout:string,stderr:string,exit_code:int}
     */
    private function resolveActionExecutionResult(string $action, string $unit): array
    {
        $prefix = strtoupper(str_replace('-', '_', $action));
        $stdoutFixture = getenv(sprintf('W4_SYSTEM_API_SERVICES_%s_STDOUT', $prefix));
        $stderrFixture = getenv(sprintf('W4_SYSTEM_API_SERVICES_%s_STDERR', $prefix));
        $exitCodeFixture = getenv(sprintf('W4_SYSTEM_API_SERVICES_%s_EXIT_CODE', $prefix));

        if (is_string($stdoutFixture) || is_string($stderrFixture) || is_string($exitCodeFixture)) {
            return [
                'stdout' => is_string($stdoutFixture) ? $stdoutFixture : '',
                'stderr' => is_string($stderrFixture) ? $stderrFixture : '',
                'exit_code' => is_string($exitCodeFixture) && preg_match('/^-?\d+$/', $exitCodeFixture) === 1
                    ? (int) $exitCodeFixture
                    : 0,
            ];
        }

        return $this->runLinuxCommand([
            'systemctl',
            $action,
            $unit,
            '--no-pager',
        ], allowFailure: true);
    }

    private function resolveStatusOutput(string $unit): string
    {
        $fixture = getenv('W4_SYSTEM_API_SERVICES_STATUS_OUTPUT');
        if (is_string($fixture)) {
            return $fixture;
        }

        $this->assertLinuxCommandSupport('services.status', 'systemctl');
        $result = $this->runLinuxCommand([
            'systemctl',
            'show',
            $unit,
            '--no-pager',
            '--property=Id,Description,LoadState,ActiveState,SubState,UnitFileState,FragmentPath',
        ], allowFailure: true);

        return $result['stdout'];
    }

    /**
     * @return array<string, string>
     */
    private function parseProperties(string $content): array
    {
        $properties = [];

        foreach (preg_split('/\r?\n/', trim($content)) ?: [] as $line) {
            if (preg_match('/^([^=]+)=(.*)$/', $line, $matches) !== 1) {
                continue;
            }

            $properties[trim($matches[1])] = trim($matches[2]);
        }

        return $properties;
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
        if ($command === 'systemctl' && $this->hasSystemctlFixtureOverrides()) {
            return true;
        }

        $result = $this->runLinuxCommand(['sh', '-lc', sprintf('command -v %s', escapeshellarg($command))], allowFailure: true);

        return $result['exit_code'] === 0 && trim($result['stdout']) !== '';
    }

    private function hasSystemctlFixtureOverrides(): bool
    {
        foreach ([
            'W4_SYSTEM_API_SERVICES_LIST_OUTPUT',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT',
            'W4_SYSTEM_API_SERVICES_START_EXIT_CODE',
            'W4_SYSTEM_API_SERVICES_START_STDOUT',
            'W4_SYSTEM_API_SERVICES_START_STDERR',
            'W4_SYSTEM_API_SERVICES_STOP_EXIT_CODE',
            'W4_SYSTEM_API_SERVICES_STOP_STDOUT',
            'W4_SYSTEM_API_SERVICES_STOP_STDERR',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE',
            'W4_SYSTEM_API_SERVICES_RESTART_STDOUT',
            'W4_SYSTEM_API_SERVICES_RESTART_STDERR',
            'W4_SYSTEM_API_SERVICES_ENABLE_EXIT_CODE',
            'W4_SYSTEM_API_SERVICES_ENABLE_STDOUT',
            'W4_SYSTEM_API_SERVICES_ENABLE_STDERR',
            'W4_SYSTEM_API_SERVICES_DISABLE_EXIT_CODE',
            'W4_SYSTEM_API_SERVICES_DISABLE_STDOUT',
            'W4_SYSTEM_API_SERVICES_DISABLE_STDERR',
        ] as $key) {
            if (getenv($key) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $service
     */
    private function computeServiceRevision(array $service): string
    {
        $encoded = json_encode([
            'unit' => $service['unit'] ?? null,
            'description' => $service['description'] ?? null,
            'load_state' => $service['load_state'] ?? null,
            'active_state' => $service['active_state'] ?? null,
            'sub_state' => $service['sub_state'] ?? null,
            'unit_file_state' => $service['unit_file_state'] ?? null,
            'fragment_path' => $service['fragment_path'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new ValidationError('No se pudo calcular la revision del servicio');
        }

        return sha1($encoded);
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
                'Metodo no soportado: el backend de servicios no pudo ejecutarse (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : implode(' ', $command)
            ));
        }

        return $result;
    }
}
