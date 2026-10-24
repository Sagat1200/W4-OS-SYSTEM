<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;

final class SystemApiPackagesToolkit
{
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
                'Metodo no soportado: el backend de paquetes no pudo ejecutarse (%s)',
                trim($result['stderr']) !== '' ? trim($result['stderr']) : implode(' ', $command)
            ));
        }

        return $result;
    }
}
