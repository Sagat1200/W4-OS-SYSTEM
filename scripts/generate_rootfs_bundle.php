<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultInputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'rootfs';

/**
 * @return array<string, mixed>
 */
function readBuildInput(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el build input: %s', $path));
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Build input invalido: %s', $exception->getMessage()));
    }

    return $data;
}

/**
 * @param array<string, mixed> $buildInput
 */
function validateBuildInput(array $buildInput, string $sourcePath): void
{
    if (($buildInput['build_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: build_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($buildInput['kind'] ?? null) !== 'build-input') {
        throw new ValidationError(sprintf('%s: kind debe ser build-input', basename($sourcePath)));
    }

    foreach (['profile_id', 'profile_name', 'base_manifest_id'] as $field) {
        $value = $buildInput[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: falta el campo %s', basename($sourcePath), $field));
        }
    }

    foreach (['repositories', 'features'] as $field) {
        if (!is_array($buildInput[$field] ?? null)) {
            throw new ValidationError(sprintf('%s: %s debe ser una lista', basename($sourcePath), $field));
        }
    }

    foreach (['packages', 'meta_packages', 'target', 'upstream', 'source_manifests'] as $field) {
        if (!is_array($buildInput[$field] ?? null)) {
            throw new ValidationError(sprintf('%s: %s debe ser un objeto', basename($sourcePath), $field));
        }
    }
}

/**
 * @return list<string>
 */
function defaultRootfsDirectories(): array
{
    return [
        '/boot',
        '/boot/efi',
        '/dev',
        '/etc',
        '/etc/apt',
        '/etc/apt/sources.list.d',
        '/etc/default',
        '/etc/systemd/system',
        '/home',
        '/proc',
        '/root',
        '/run',
        '/sys',
        '/tmp',
        '/usr',
        '/usr/local',
        '/var',
        '/var/cache/apt',
        '/var/lib/apt',
        '/var/lib/dpkg',
        '/var/log',
        '/var/tmp',
    ];
}

/**
 * @param list<string> $directories
 */
function buildRootfsScript(array $buildInput, array $directories): string
{
    $profileId = $buildInput['profile_id'];
    $distribution = $buildInput['upstream']['distribution'];
    $track = $buildInput['upstream']['track'];
    $requiredPackages = implode(' ', $buildInput['packages']['required']);
    $recommendedPackages = implode(' ', $buildInput['packages']['recommended']);
    $repositories = implode(PHP_EOL, array_map(
        static fn (string $repository): string => sprintf('echo "  - %s"', $repository),
        $buildInput['repositories']
    ));
    $directoryCommands = implode(PHP_EOL, array_map(
        static fn (string $directory): string => sprintf('mkdir -p "${ROOTFS_DIR}%s"', $directory),
        $directories
    ));

    return <<<BASH
#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="\${1:-./rootfs}"

echo "==> W4 OS System rootfs assembly"
echo "Profile: {$profileId}"
echo "Distribution: {$distribution}"
echo "Track: {$track}"
echo "Output: \${ROOTFS_DIR}"

if ! command -v debootstrap >/dev/null 2>&1; then
  echo "ERROR: debootstrap no esta disponible en este entorno Linux." >&2
  exit 1
fi

mkdir -p "\${ROOTFS_DIR}"
{$directoryCommands}

echo "==> Repositorios declarados"
{$repositories}

echo "==> Bootstrap base Debian"
debootstrap --variant=minbase {$track} "\${ROOTFS_DIR}" http://deb.debian.org/debian/

echo "==> Instalacion de paquetes requeridos"
chroot "\${ROOTFS_DIR}" apt-get update
chroot "\${ROOTFS_DIR}" apt-get install -y {$requiredPackages}

echo "==> Paquetes recomendados sugeridos"
echo "{$recommendedPackages}"

echo "==> Limpiando identidades del entorno"
rm -f "\${ROOTFS_DIR}/etc/machine-id"
touch "\${ROOTFS_DIR}/etc/machine-id"
rm -f "\${ROOTFS_DIR}/var/lib/dbus/machine-id"

echo "==> Rootfs base ensamblado"
echo "Siguiente etapa: configuracion de primer inicio, instalador y formato de imagen."
BASH;
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $inputPath = null;
    $outputPath = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--profile':
                $profileId = $value;
                break;

            case '--input':
                $inputPath = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $inputPath === null) {
        throw new ValidationError('Debe indicar --profile o --input');
    }

    if ($inputPath === null) {
        $inputPath = $defaultInputDir . DIRECTORY_SEPARATOR . $profileId . '.build-input.json';
    }

    $buildInput = readBuildInput($inputPath);
    validateBuildInput($buildInput, $inputPath);

    /** @var string $resolvedProfileId */
    $resolvedProfileId = $buildInput['profile_id'];
    if ($profileId !== null && $profileId !== $resolvedProfileId) {
        throw new ValidationError('El perfil indicado no coincide con el build input proporcionado');
    }

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $resolvedProfileId;
    }

    if (!is_dir($outputPath) && !mkdir($outputPath, 0777, true) && !is_dir($outputPath)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputPath));
    }

    $directories = defaultRootfsDirectories();
    $rootfsManifest = [
        'rootfs_schema_version' => 1,
        'kind' => 'rootfs-bundle',
        'profile_id' => $buildInput['profile_id'],
        'profile_name' => $buildInput['profile_name'],
        'base_manifest_id' => $buildInput['base_manifest_id'],
        'source_build_input' => basename($inputPath),
        'target' => $buildInput['target'],
        'upstream' => $buildInput['upstream'],
        'repositories' => $buildInput['repositories'],
        'packages' => $buildInput['packages'],
        'meta_packages' => $buildInput['meta_packages'],
        'features' => $buildInput['features'],
        'rootfs_directories' => $directories,
        'identity_cleanup' => [
            '/etc/machine-id',
            '/var/lib/dbus/machine-id',
        ],
        'next_steps' => [
            'configurar primer inicio',
            'anadir instalador y componentes live',
            'generar formato de imagen',
            'ejecutar pruebas de arranque',
        ],
    ];

    $requiredPackages = implode(PHP_EOL, $buildInput['packages']['required']) . PHP_EOL;
    $recommendedPackages = implode(PHP_EOL, $buildInput['packages']['recommended']) . PHP_EOL;
    $repositories = implode(PHP_EOL, $buildInput['repositories']) . PHP_EOL;
    $directoryList = implode(PHP_EOL, $directories) . PHP_EOL;
    $rootfsScript = buildRootfsScript($buildInput, $directories);

    $filesToWrite = [
        $outputPath . DIRECTORY_SEPARATOR . 'rootfs-manifest.json' => json_encode($rootfsManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        $outputPath . DIRECTORY_SEPARATOR . 'packages.required.list' => $requiredPackages,
        $outputPath . DIRECTORY_SEPARATOR . 'packages.recommended.list' => $recommendedPackages,
        $outputPath . DIRECTORY_SEPARATOR . 'repositories.list' => $repositories,
        $outputPath . DIRECTORY_SEPARATOR . 'directories.list' => $directoryList,
        $outputPath . DIRECTORY_SEPARATOR . 'build-rootfs.sh' => $rootfsScript . PHP_EOL,
    ];

    foreach ($filesToWrite as $path => $contents) {
        if (file_put_contents($path, $contents) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $path));
        }
    }

    printJson([
        'status' => 'ok',
        'profile_id' => $buildInput['profile_id'],
        'output_directory' => $outputPath,
        'generated_files' => array_map('basename', array_keys($filesToWrite)),
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
