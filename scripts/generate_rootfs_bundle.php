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
    $codename = $buildInput['upstream']['codename'] ?? $track;
    $requiredPackages = implode(' ', $buildInput['packages']['required']);
    $requiredPackagesForMmdebstrap = implode(',', $buildInput['packages']['required']);
    $recommendedPackages = implode(' ', $buildInput['packages']['recommended']);
    $repositories = implode(PHP_EOL, array_map(
        static fn (string $repository): string => sprintf('echo "  - %s"', $repository),
        $buildInput['repositories']
    ));
    $directoryCommands = implode(PHP_EOL, array_map(
        static fn (string $directory): string => sprintf('mkdir -p "${ROOTFS_DIR}%s"', $directory),
        $directories
    ));

    $script = <<<BASH
#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="\${1:-./rootfs}"
BOOTSTRAP_KEYRING_DIR="/var/tmp/w4-os-system/{$profileId}/bootstrap-keyring"

prepare_host_debian_bootstrap_keyring() {
  local work_dir="\${1}"
  local output_keyring="\${work_dir}/debian-host-bootstrap-keyring.gpg"

  mkdir -p "\${work_dir}"
  rm -f "\${output_keyring}"

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]] && command -v gpg >/dev/null 2>&1; then
    gpg --batch --no-default-keyring --keyring /usr/share/keyrings/debian-archive-current.gpg --export > "\${output_keyring}"
    if [[ -s "\${output_keyring}" ]]; then
      printf '%s\n' "\${output_keyring}"
      return 0
    fi
  fi

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-current.gpg "\${output_keyring}"
    printf '%s\n' "\${output_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-keyring.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-keyring.gpg "\${output_keyring}"
    printf '%s\n' "\${output_keyring}"
    return 0
  fi

  echo "ERROR: no se encontro un keyring Debian utilizable para bootstrap en el host Linux." >&2
  exit 1
}

ensure_debian_keyring_in_rootfs() {
  local rootfs_dir="\${1}"
  local target_dir="\${rootfs_dir}/usr/share/keyrings"
  local target_keyring="\${target_dir}/debian-archive-keyring.gpg"
  local compatibility_keyring="\${target_dir}/debian-archive-current.gpg"

  if [[ -f "\${target_keyring}" ]]; then
    ln -sf debian-archive-keyring.gpg "\${compatibility_keyring}"
    return 0
  fi

  mkdir -p "\${target_dir}"

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]] && command -v gpg >/dev/null 2>&1; then
    gpg --no-default-keyring --keyring /usr/share/keyrings/debian-archive-current.gpg --export > "\${target_keyring}"
    ln -sf debian-archive-keyring.gpg "\${compatibility_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-keyring.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-keyring.gpg "\${target_keyring}"
    ln -sf debian-archive-keyring.gpg "\${compatibility_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-current.gpg "\${target_keyring}"
    ln -sf debian-archive-keyring.gpg "\${compatibility_keyring}"
    return 0
  fi

  echo "ERROR: no se encontro un keyring Debian utilizable en el host Linux." >&2
  exit 1
}

normalize_debian_sources_keyring() {
  local rootfs_dir="\${1}"

  if [[ -f "\${rootfs_dir}/etc/apt/sources.list" ]]; then
    sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' "\${rootfs_dir}/etc/apt/sources.list"
  fi

  if [[ -d "\${rootfs_dir}/etc/apt/sources.list.d" ]]; then
    find "\${rootfs_dir}/etc/apt/sources.list.d" -maxdepth 1 -type f -name '*.list' -exec \
      sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' {} +
  fi
}

cleanup() {
  umount -lf "\${ROOTFS_DIR}/proc" 2>/dev/null || true
  umount -lf "\${ROOTFS_DIR}/sys" 2>/dev/null || true
  umount -lf "\${ROOTFS_DIR}/dev" 2>/dev/null || true
}

trap cleanup EXIT

echo "==> W4 OS System rootfs assembly"
echo "Profile: {$profileId}"
echo "Distribution: {$distribution}"
echo "Track: {$track}"
echo "Codename: {$codename}"
echo "Output: \${ROOTFS_DIR}"

if ! command -v mmdebstrap >/dev/null 2>&1 && ! command -v debootstrap >/dev/null 2>&1; then
  echo "ERROR: no hay ni mmdebstrap ni debootstrap disponibles en este entorno Linux." >&2
  exit 1
fi

mkdir -p "\${ROOTFS_DIR}"
HOST_BOOTSTRAP_KEYRING="\$(prepare_host_debian_bootstrap_keyring "\${BOOTSTRAP_KEYRING_DIR}")"

echo "==> Repositorios declarados"
{$repositories}
echo "==> Keyring bootstrap: \${HOST_BOOTSTRAP_KEYRING}"

if command -v mmdebstrap >/dev/null 2>&1; then
  echo "==> Bootstrap base Debian con mmdebstrap"
  mmdebstrap \
    --variant=minbase \
    --include={$requiredPackagesForMmdebstrap} \
    --aptopt='Acquire::Retries "3"' \
    {$codename} "\${ROOTFS_DIR}" \
    "deb [signed-by=\${HOST_BOOTSTRAP_KEYRING}] https://deb.debian.org/debian {$codename} main"
else
  echo "==> Bootstrap base Debian con debootstrap"
  debootstrap --keyring="\${HOST_BOOTSTRAP_KEYRING}" --merged-usr --variant=minbase {$codename} "\${ROOTFS_DIR}" https://deb.debian.org/debian/

  echo "==> Asegurando keyring Debian dentro del rootfs"
  ensure_debian_keyring_in_rootfs "\${ROOTFS_DIR}"
  normalize_debian_sources_keyring "\${ROOTFS_DIR}"

  echo "==> Asegurando estructura base de directorios"
  {$directoryCommands}

  echo "==> Montando pseudo-filesystems para chroot"
  mount --bind /dev "\${ROOTFS_DIR}/dev"
  mount -t proc proc "\${ROOTFS_DIR}/proc"
  mount -t sysfs sysfs "\${ROOTFS_DIR}/sys"

  echo "==> Instalacion de paquetes requeridos"
  chroot "\${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 update
  chroot "\${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 install -y {$requiredPackages}
fi

echo "==> Asegurando keyring Debian dentro del rootfs"
ensure_debian_keyring_in_rootfs "\${ROOTFS_DIR}"
normalize_debian_sources_keyring "\${ROOTFS_DIR}"

echo "==> Asegurando estructura base de directorios"
{$directoryCommands}

echo "==> Paquetes recomendados sugeridos"
echo "{$recommendedPackages}"

echo "==> Limpiando identidades del entorno"
rm -f "\${ROOTFS_DIR}/etc/machine-id"
touch "\${ROOTFS_DIR}/etc/machine-id"
rm -f "\${ROOTFS_DIR}/var/lib/dbus/machine-id"

echo "==> Rootfs base ensamblado"
echo "Siguiente etapa: configuracion de primer inicio, instalador y formato de imagen."
BASH;

    return str_replace(["\r\n", "\r"], "\n", $script);
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

    $requiredPackages = implode("\n", $buildInput['packages']['required']) . "\n";
    $recommendedPackages = implode("\n", $buildInput['packages']['recommended']) . "\n";
    $repositories = implode("\n", $buildInput['repositories']) . "\n";
    $directoryList = implode("\n", $directories) . "\n";
    $rootfsScript = buildRootfsScript($buildInput, $directories);

    $filesToWrite = [
        $outputPath . DIRECTORY_SEPARATOR . 'rootfs-manifest.json' => json_encode($rootfsManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
        $outputPath . DIRECTORY_SEPARATOR . 'packages.required.list' => $requiredPackages,
        $outputPath . DIRECTORY_SEPARATOR . 'packages.recommended.list' => $recommendedPackages,
        $outputPath . DIRECTORY_SEPARATOR . 'repositories.list' => $repositories,
        $outputPath . DIRECTORY_SEPARATOR . 'directories.list' => $directoryList,
        $outputPath . DIRECTORY_SEPARATOR . 'build-rootfs.sh' => $rootfsScript . "\n",
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
