<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiServicesToolkit
{
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
                'service' => [
                    'unit' => $unit,
                ],
            ];
        }

        return [
            'kind' => 'system-api-service-status',
            'status' => 'ok',
            'backend' => 'systemctl',
            'service' => [
                'unit' => $properties['Id'] ?? $unit,
                'description' => $properties['Description'] ?? null,
                'load_state' => $properties['LoadState'] ?? null,
                'active_state' => $properties['ActiveState'] ?? null,
                'sub_state' => $properties['SubState'] ?? null,
                'unit_file_state' => $properties['UnitFileState'] ?? null,
                'fragment_path' => $properties['FragmentPath'] ?? null,
            ],
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
        $result = $this->runLinuxCommand(['sh', '-lc', sprintf('command -v %s', escapeshellarg($command))], allowFailure: true);

        return $result['exit_code'] === 0 && trim($result['stdout']) !== '';
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
