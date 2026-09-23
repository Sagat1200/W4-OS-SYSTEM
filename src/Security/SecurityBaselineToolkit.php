<?php

declare(strict_types=1);

namespace W4\OS\Security;

use W4\OS\Installer\InstallerToolkit;
use W4\OS\Manifest\ManifestToolkit;
use W4\OS\Support\ValidationError;

final class SecurityBaselineToolkit
{
    private ManifestToolkit $manifestToolkit;
    private InstallerToolkit $installerToolkit;

    public function __construct(private readonly string $rootDir)
    {
        $this->manifestToolkit = new ManifestToolkit($rootDir);
        $this->installerToolkit = new InstallerToolkit();
    }

    /**
     * @return array<string, mixed>
     */
    public function createBaseline(string $profileId, string $installerProfilePath): array
    {
        $manifests = $this->manifestToolkit->loadManifests();
        $this->manifestToolkit->validateAll($manifests);
        $resolvedProfile = $this->manifestToolkit->resolveProfile($manifests, $profileId);

        $installationProfile = $this->installerToolkit->readJsonFile($installerProfilePath);
        $this->installerToolkit->validateInstallationProfile($installationProfile, $installerProfilePath);

        if (($installationProfile['build_profile_id'] ?? null) !== $profileId) {
            throw new ValidationError(sprintf(
                'El installation profile %s no corresponde al profile_id %s',
                basename($installerProfilePath),
                $profileId
            ));
        }

        /** @var array<string, mixed> $baseManifest */
        $baseManifest = $manifests[$resolvedProfile['inherits']];
        /** @var list<string> $requiredPackages */
        $requiredPackages = $resolvedProfile['required_packages'];
        /** @var list<string> $recommendedPackages */
        $recommendedPackages = $resolvedProfile['recommended_packages'];
        /** @var list<string> $repositories */
        $repositories = $baseManifest['repositories'];
        /** @var array<string, mixed> $account */
        $account = $installationProfile['identity']['account'];
        /** @var array<string, mixed> $encryption */
        $encryption = $installationProfile['security']['encryption'];

        $controls = [
            $this->buildControl(
                id: 'encrypted-root',
                title: 'Root cifrado visible',
                severity: 'required',
                category: 'encryption',
                implementationState: ($encryption['enabled'] ?? false) === true && ($encryption['type'] ?? null) === 'luks2-passphrase' ? 'implemented' : 'gap',
                validationScope: 'runtime',
                description: 'La imagen instalada debe arrancar con root cifrado y un mapper visible para el sistema de archivos raiz.',
                expected: [
                    'root_source_prefix' => '/dev/mapper/',
                    'encryption_type' => (string) ($encryption['type'] ?? ''),
                ],
                evidence: [
                    $this->relativePath($installerProfilePath),
                ]
            ),
            $this->buildControl(
                id: 'standard-account',
                title: 'Cuenta estandar no root',
                severity: 'required',
                category: 'identity',
                implementationState: (($account['username'] ?? '') !== '' && ($account['username'] ?? '') !== 'root') ? 'implemented' : 'gap',
                validationScope: 'runtime',
                description: 'La imagen debe operar con una cuenta local nominal que no sea root.',
                expected: [
                    'username' => (string) ($account['username'] ?? ''),
                    'uid_must_not_be' => 0,
                ],
                evidence: [
                    $this->relativePath($installerProfilePath),
                ]
            ),
            $this->buildControl(
                id: 'auditable-admin-path',
                title: 'Ruta explicita de administracion',
                severity: 'required',
                category: 'privilege',
                implementationState: $this->containsPackage($requiredPackages, $recommendedPackages, 'sudo') ? 'implemented' : 'gap',
                validationScope: 'runtime',
                description: 'La elevacion explicita debe pasar por una herramienta auditable de administracion.',
                expected: [
                    'command' => 'sudo',
                ],
                evidence: [
                    'manifests/w4-linux-base.manifest.json',
                ]
            ),
            $this->buildControl(
                id: 'remote-admin-disabled-by-default',
                title: 'Administracion remota deshabilitada por defecto',
                severity: 'required',
                category: 'services',
                implementationState: $this->containsPackage($requiredPackages, $recommendedPackages, 'openssh-server') ? 'gap' : 'implemented',
                validationScope: 'runtime',
                description: 'La imagen no debe habilitar SSH server como camino normal de administracion inicial.',
                expected: [
                    'service' => 'ssh',
                    'enabled_states_forbidden' => ['enabled', 'enabled-runtime', 'linked', 'linked-runtime', 'alias'],
                ],
                evidence: [
                    'manifests/w4-linux-base.manifest.json',
                    $this->relativePath($installerProfilePath),
                ]
            ),
            $this->buildControl(
                id: 'firewall-control-plane',
                title: 'Control de firewall presente',
                severity: 'required',
                category: 'network',
                implementationState: $this->containsAnyPackage($requiredPackages, $recommendedPackages, ['ufw', 'nftables']) ? 'implemented' : 'gap',
                validationScope: 'runtime',
                description: 'La imagen debe exponer al menos una autoridad local verificable para reglas de firewall.',
                expected: [
                    'accepted_commands' => ['ufw', 'nft'],
                ],
                evidence: [
                    'Docs/W4-OS/159_W4_OS_FIREWALL_SYSTEM.md',
                    'Docs/W4-OS/157_W4_OS_SECURITY_BASELINE.md',
                ]
            ),
            $this->buildControl(
                id: 'mac-enforcement',
                title: 'Control MAC presente',
                severity: 'required',
                category: 'hardening',
                implementationState: $this->containsPackage($requiredPackages, $recommendedPackages, 'apparmor') ? 'implemented' : 'gap',
                validationScope: 'runtime',
                description: 'La imagen debe poder demostrar un control de mandatory access control activo o al menos instalado.',
                expected: [
                    'module' => 'apparmor',
                ],
                evidence: [
                    'Docs/W4-OS/158_W4_OS_APPARMOR_ARCHITECTURE.md',
                    'Docs/W4-OS/157_W4_OS_SECURITY_BASELINE.md',
                ]
            ),
            $this->buildControl(
                id: 'authenticated-updates',
                title: 'Actualizacion autenticada',
                severity: 'required',
                category: 'updates',
                implementationState: in_array('w4-main', $repositories, true) && in_array('debian-security', $repositories, true) ? 'implemented' : 'gap',
                validationScope: 'pipeline',
                description: 'La baseline exige una fuente de actualizacion autenticada y trazable, validada en el pipeline firmado.',
                expected: [
                    'required_repositories' => ['w4-main', 'debian-security'],
                    'signed_profile' => 'prod',
                ],
                evidence: [
                    'manifests/w4-linux-base.manifest.json',
                    'build/update/repository-output/w4-main-2026-09-20T180000Z-signed-prod/',
                    'Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md',
                ]
            ),
        ];

        return [
            'security_baseline_schema_version' => 1,
            'kind' => 'security-baseline',
            'profile_id' => $profileId,
            'profile_name' => $resolvedProfile['name'],
            'installer_profile_id' => $installationProfile['id'],
            'scope' => 'installed-image',
            'generated_from' => [
                'manifest_profile' => $profileId,
                'installer_profile' => $this->relativePath($installerProfilePath),
                'baseline_document' => 'Docs/W4-OS/157_W4_OS_SECURITY_BASELINE.md',
            ],
            'controls' => $controls,
            'summary' => $this->summarizeControls($controls),
        ];
    }

    /**
     * @param array<string, mixed> $baseline
     * @return array<string, mixed>
     */
    public function writeBundle(array $baseline, string $bundleDir): array
    {
        if (!is_dir($bundleDir) && !mkdir($bundleDir, 0777, true) && !is_dir($bundleDir)) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta %s', $bundleDir));
        }

        $jsonPath = $bundleDir . DIRECTORY_SEPARATOR . 'security-baseline.json';
        $verifyPath = $bundleDir . DIRECTORY_SEPARATOR . 'verify-security-baseline.php';
        $readmePath = $bundleDir . DIRECTORY_SEPARATOR . 'SECURITY_BASELINE_README.txt';

        $this->writeJsonFile($jsonPath, $baseline);
        $this->writeTextFile($verifyPath, $this->buildVerifierPhp($baseline));
        $this->writeTextFile($readmePath, $this->buildReadme($baseline));

        return [
            'status' => 'ok',
            'bundle_dir' => $bundleDir,
            'profile_id' => $baseline['profile_id'],
            'generated_artifacts' => [
                'security-baseline.json',
                'verify-security-baseline.php',
                'SECURITY_BASELINE_README.txt',
            ],
            'summary' => $baseline['summary'],
        ];
    }

    /**
     * @param array<string, mixed> $baseline
     * @return string
     */
    private function buildVerifierPhp(array $baseline): string
    {
        $username = $this->phpLiteral((string) $this->controlExpectedValue($baseline, 'standard-account', 'username'));
        $rootPrefix = $this->phpLiteral((string) $this->controlExpectedValue($baseline, 'encrypted-root', 'root_source_prefix'));
        $acceptedFirewallCommands = var_export((array) $this->controlExpectedValue($baseline, 'firewall-control-plane', 'accepted_commands'), true);
        $forbiddenEnabledStates = var_export((array) $this->controlExpectedValue($baseline, 'remote-admin-disabled-by-default', 'enabled_states_forbidden'), true);

        return <<<PHP
<?php

declare(strict_types=1);

\$bundleDir = __DIR__;
\$baselinePath = \$bundleDir . DIRECTORY_SEPARATOR . 'security-baseline.json';
\$reportPath = \$argv[1] ?? (\$bundleDir . DIRECTORY_SEPARATOR . 'security-baseline-report.json');
\$raw = file_get_contents(\$baselinePath);
if (\$raw === false) {
    fwrite(STDERR, "ERROR: No se pudo leer security-baseline.json\\n");
    exit(1);
}

try {
    /** @var array<string, mixed> \$baseline */
    \$baseline = json_decode(\$raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException \$exception) {
    fwrite(STDERR, sprintf("ERROR: JSON invalido en security-baseline.json: %s\\n", \$exception->getMessage()));
    exit(1);
}

/**
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runCommand(string \$command): array
{
    \$descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    \$process = proc_open(['/bin/bash', '-lc', \$command], \$descriptorSpec, \$pipes, __DIR__);
    if (!is_resource(\$process)) {
        return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'No se pudo crear el proceso'];
    }

    fclose(\$pipes[0]);
    \$stdout = stream_get_contents(\$pipes[1]);
    \$stderr = stream_get_contents(\$pipes[2]);
    fclose(\$pipes[1]);
    fclose(\$pipes[2]);

    return [
        'exit_code' => proc_close(\$process),
        'stdout' => is_string(\$stdout) ? trim(\$stdout) : '',
        'stderr' => is_string(\$stderr) ? trim(\$stderr) : '',
    ];
}

function addResult(array &\$results, string \$id, string \$status, string \$detail): void
{
    \$results[] = [
        'id' => \$id,
        'status' => \$status,
        'detail' => \$detail,
    ];
}

\$results = [];
\$username = {$username};
\$rootPrefix = {$rootPrefix};
\$acceptedFirewallCommands = {$acceptedFirewallCommands};
\$forbiddenEnabledStates = {$forbiddenEnabledStates};

\$rootSource = runCommand("findmnt -n -o SOURCE /");
if (\$rootSource['exit_code'] === 0 && str_starts_with(\$rootSource['stdout'], \$rootPrefix)) {
    addResult(\$results, 'encrypted-root', 'passed', 'La raiz esta montada desde ' . \$rootSource['stdout']);
} else {
    addResult(\$results, 'encrypted-root', 'failed', 'La raiz no esta montada sobre un mapper cifrado visible');
}

\$account = runCommand('id -u ' . escapeshellarg(\$username));
if (\$account['exit_code'] === 0 && \$account['stdout'] !== '0') {
    addResult(\$results, 'standard-account', 'passed', 'La cuenta ' . \$username . ' existe y no usa UID 0');
} else {
    addResult(\$results, 'standard-account', 'failed', 'La cuenta esperada no existe o usa UID 0');
}

\$sudo = runCommand('command -v sudo');
if (\$sudo['exit_code'] === 0 && \$sudo['stdout'] !== '') {
    addResult(\$results, 'auditable-admin-path', 'passed', 'sudo esta disponible en ' . \$sudo['stdout']);
} else {
    addResult(\$results, 'auditable-admin-path', 'failed', 'sudo no esta disponible');
}

\$sshService = runCommand('systemctl is-enabled ssh 2>/dev/null || systemctl is-enabled ssh.service 2>/dev/null');
if (\$sshService['exit_code'] !== 0) {
    addResult(\$results, 'remote-admin-disabled-by-default', 'passed', 'ssh no esta habilitado por defecto');
} elseif (in_array(\$sshService['stdout'], \$forbiddenEnabledStates, true)) {
    addResult(\$results, 'remote-admin-disabled-by-default', 'failed', 'ssh aparece habilitado: ' . \$sshService['stdout']);
} else {
    addResult(\$results, 'remote-admin-disabled-by-default', 'passed', 'ssh no esta habilitado por defecto (' . \$sshService['stdout'] . ')');
}

\$firewallCommand = '';
foreach (\$acceptedFirewallCommands as \$candidate) {
    \$commandResult = runCommand('command -v ' . escapeshellarg(\$candidate));
    if (\$commandResult['exit_code'] === 0 && \$commandResult['stdout'] !== '') {
        \$firewallCommand = \$commandResult['stdout'];
        break;
    }
}
if (\$firewallCommand !== '') {
    addResult(\$results, 'firewall-control-plane', 'passed', 'Herramienta de firewall disponible: ' . \$firewallCommand);
} else {
    addResult(\$results, 'firewall-control-plane', 'failed', 'No se encontro ufw ni nft en la imagen');
}

\$apparmor = runCommand("test -r /sys/module/apparmor/parameters/enabled && grep -qx 'Y' /sys/module/apparmor/parameters/enabled");
if (\$apparmor['exit_code'] === 0) {
    addResult(\$results, 'mac-enforcement', 'passed', 'AppArmor aparece activo en el kernel');
} else {
    addResult(\$results, 'mac-enforcement', 'failed', 'No se pudo confirmar AppArmor activo');
}

addResult(\$results, 'authenticated-updates', 'skipped', 'Control validado por pipeline firmado; revisar security-baseline.json y evidencia de MX-004');

\$summary = [
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
];

foreach (\$results as \$result) {
    \$summary[\$result['status']]++;
}

\$report = [
    'security_baseline_report_schema_version' => 1,
    'kind' => 'security-baseline-report',
    'profile_id' => \$baseline['profile_id'],
    'generated_at' => gmdate(DATE_ATOM),
    'results' => \$results,
    'summary' => \$summary,
];

\$json = json_encode(\$report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (\$json === false || file_put_contents(\$reportPath, \$json . PHP_EOL) === false) {
    fwrite(STDERR, "ERROR: No se pudo escribir el reporte de baseline\\n");
    exit(1);
}

fwrite(STDOUT, \$json . PHP_EOL);
exit(\$summary['failed'] === 0 ? 0 : 2);
PHP;
    }

    /**
     * @param array<string, mixed> $baseline
     */
    private function buildReadme(array $baseline): string
    {
        $profileId = (string) $baseline['profile_id'];

        return <<<TXT
W4 OS Security Baseline
=======================

Perfil: {$profileId}

Artefactos:
- security-baseline.json
- verify-security-baseline.php

Uso recomendado sobre una imagen instalada:

  php ./verify-security-baseline.php

O guardando el reporte en una ruta explicita:

  php ./verify-security-baseline.php /ruta/security-baseline-report.json

Notas:
- Los controles con validation_scope=runtime se verifican sobre la imagen en ejecucion.
- Los controles con validation_scope=pipeline se trazan contra la evidencia del pipeline firmado y quedan como skipped en el reporte local.
- Este bundle representa la primera capa de MX-005: checklist validable por imagen y gaps explicitados.
TXT;
    }

    /**
     * @param array<string, mixed> $baseline
     * @return mixed
     */
    private function controlExpectedValue(array $baseline, string $controlId, string $field): mixed
    {
        /** @var list<array<string, mixed>> $controls */
        $controls = $baseline['controls'];
        foreach ($controls as $control) {
            if (($control['id'] ?? null) === $controlId) {
                /** @var array<string, mixed> $expected */
                $expected = $control['expected'];
                return $expected[$field] ?? null;
            }
        }

        throw new ValidationError(sprintf('No existe el control %s en el baseline generado', $controlId));
    }

    /**
     * @param array<string, mixed> $expected
     * @param list<string> $evidence
     * @return array<string, mixed>
     */
    private function buildControl(
        string $id,
        string $title,
        string $severity,
        string $category,
        string $implementationState,
        string $validationScope,
        string $description,
        array $expected,
        array $evidence
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'severity' => $severity,
            'category' => $category,
            'implementation_state' => $implementationState,
            'validation_scope' => $validationScope,
            'description' => $description,
            'expected' => $expected,
            'evidence' => $evidence,
        ];
    }

    /**
     * @param list<array<string, mixed>> $controls
     * @return array<string, int>
     */
    private function summarizeControls(array $controls): array
    {
        $summary = [
            'implemented' => 0,
            'gap' => 0,
        ];

        foreach ($controls as $control) {
            $state = (string) $control['implementation_state'];
            if (!array_key_exists($state, $summary)) {
                $summary[$state] = 0;
            }

            $summary[$state]++;
        }

        return $summary;
    }

    /**
     * @param list<string> $requiredPackages
     * @param list<string> $recommendedPackages
     */
    private function containsPackage(array $requiredPackages, array $recommendedPackages, string $package): bool
    {
        return in_array($package, $requiredPackages, true) || in_array($package, $recommendedPackages, true);
    }

    /**
     * @param list<string> $requiredPackages
     * @param list<string> $recommendedPackages
     * @param list<string> $packages
     */
    private function containsAnyPackage(array $requiredPackages, array $recommendedPackages, array $packages): bool
    {
        foreach ($packages as $package) {
            if ($this->containsPackage($requiredPackages, $recommendedPackages, $package)) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir) . DIRECTORY_SEPARATOR;
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($normalizedPath, $normalizedRoot)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedRoot)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJsonFile(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new ValidationError(sprintf('No se pudo serializar JSON para %s', $path));
        }

        $this->writeTextFile($path, $json . PHP_EOL);
    }

    private function writeTextFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $path));
        }
    }

    private function phpLiteral(string $value): string
    {
        return var_export($value, true);
    }
}
