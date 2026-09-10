<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultLiveBundleDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live';
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'iso';

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('JSON invalido en %s: %s', $path, $exception->getMessage()));
    }

    return $data;
}

/**
 * @param array<string, mixed> $liveManifest
 */
function validateLiveManifest(array $liveManifest, string $sourcePath): void
{
    if (($liveManifest['live_bundle_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: live_bundle_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($liveManifest['kind'] ?? null) !== 'live-bundle') {
        throw new ValidationError(sprintf('%s: kind debe ser live-bundle', basename($sourcePath)));
    }

    foreach (['profile_id', 'profile_name', 'base_manifest_id'] as $field) {
        $value = $liveManifest[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: falta el campo %s', basename($sourcePath), $field));
        }
    }
}

/**
 * @param array<string, mixed> $liveManifest
 * @return array<string, string>
 */
function isoVariables(array $liveManifest): array
{
    /** @var array<string, mixed> $branding */
    $branding = is_array($liveManifest['branding'] ?? null) ? $liveManifest['branding'] : [];

    $profileId = (string) $liveManifest['profile_id'];
    $profileName = (string) $liveManifest['profile_name'];
    $edition = (string) ($branding['edition'] ?? str_replace('W4 OS ', '', $profileName));
    $distributionName = (string) ($branding['distribution_name'] ?? 'W4 OS');
    $volumeId = (string) ($branding['volume_id'] ?? strtoupper(str_replace(['-', ' '], '_', $profileId . '_LIVE')));
    $isoFilename = sprintf('%s-live-amd64.iso', $profileId);

    return [
        'profile_id' => $profileId,
        'profile_name' => $profileName,
        'edition' => $edition,
        'distribution_name' => $distributionName,
        'volume_id' => substr($volumeId, 0, 32),
        'iso_filename' => $isoFilename,
    ];
}

/**
 * @param array<string, string> $vars
 */
function buildReadme(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
W4 OS ISO Bundle

Perfil: {$vars['profile_name']}
Edicion: {$vars['edition']}
ISO objetivo: {$vars['iso_filename']}
Volume ID: {$vars['volume_id']}

Este bundle empaqueta una ISO UEFI arrancable a partir de un `image-root`
ya compuesto por la etapa live.

Requiere en el host Linux:
- xorriso
- grub-mkrescue o, en su defecto, grub-mkstandalone + mtools + dosfstools
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildComposeScript(array $vars): string
{
    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

IMAGE_ROOT_DIR="${1:-}"
OUTPUT_DIR="${2:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROFILE_ID="%PROFILE_ID%"
PROFILE_NAME="%PROFILE_NAME%"
ISO_FILENAME="%ISO_FILENAME%"
VOLUME_ID="%VOLUME_ID%"
ISO_STAGE_DIR_DEFAULT="/var/tmp/w4-os-system/${PROFILE_ID}/iso-stage"
ISO_STAGE_DIR="${W4_ISO_STAGE_DIR:-${ISO_STAGE_DIR_DEFAULT}}"

require_file() {
  local path="${1}"
  if [[ ! -f "${path}" ]]; then
    echo "ERROR: falta el archivo requerido: ${path}" >&2
    exit 1
  fi
}

if [[ -z "${IMAGE_ROOT_DIR}" ]]; then
  echo "ERROR: debe indicar el image-root de origen" >&2
  exit 1
fi

if [[ ! -d "${IMAGE_ROOT_DIR}" ]]; then
  echo "ERROR: no existe el image-root de origen: ${IMAGE_ROOT_DIR}" >&2
  exit 1
fi

if [[ -z "${OUTPUT_DIR}" ]]; then
  OUTPUT_DIR="${SCRIPT_DIR}/output"
fi

if ! command -v xorriso >/dev/null 2>&1; then
  echo "ERROR: xorriso no esta disponible en este entorno Linux." >&2
  exit 1
fi

echo "==> W4 OS System ISO composition"
echo "Profile: ${PROFILE_NAME}"
echo "Image root: ${IMAGE_ROOT_DIR}"
echo "Output final: ${OUTPUT_DIR}"
echo "ISO stage: ${ISO_STAGE_DIR}"

require_file "${IMAGE_ROOT_DIR}/boot/grub/grub.cfg"
require_file "${IMAGE_ROOT_DIR}/live/vmlinuz"
require_file "${IMAGE_ROOT_DIR}/live/initrd"
require_file "${IMAGE_ROOT_DIR}/live/filesystem.squashfs"

rm -rf "${ISO_STAGE_DIR}"
mkdir -p "${ISO_STAGE_DIR}/image-root" "${ISO_STAGE_DIR}/metadata"
rsync -a --delete "${IMAGE_ROOT_DIR}/" "${ISO_STAGE_DIR}/image-root/"

if command -v grub-mkrescue >/dev/null 2>&1; then
  echo "==> Generando ISO con grub-mkrescue"
  grub-mkrescue -o "${ISO_STAGE_DIR}/${ISO_FILENAME}" "${ISO_STAGE_DIR}/image-root"
else
  if ! command -v grub-mkstandalone >/dev/null 2>&1; then
    echo "ERROR: no se encontro ni grub-mkrescue ni grub-mkstandalone." >&2
    exit 1
  fi

  if ! command -v mformat >/dev/null 2>&1 || ! command -v mmd >/dev/null 2>&1 || ! command -v mcopy >/dev/null 2>&1; then
    echo "ERROR: faltan herramientas mtools (mformat/mmd/mcopy) para construir EFI fallback." >&2
    exit 1
  fi

  if ! command -v mkfs.vfat >/dev/null 2>&1; then
    echo "ERROR: mkfs.vfat no esta disponible para construir EFI fallback." >&2
    exit 1
  fi

  echo "==> Generando ISO UEFI con xorriso + grub-mkstandalone"
  mkdir -p "${ISO_STAGE_DIR}/work/EFI/BOOT" "${ISO_STAGE_DIR}/image-root/EFI"

  cat > "${ISO_STAGE_DIR}/work/grub-embed.cfg" <<'EOF'
search --file --set=root /live/filesystem.squashfs
set prefix=($root)/boot/grub
configfile /boot/grub/grub.cfg
EOF

  grub-mkstandalone \
    -O x86_64-efi \
    -o "${ISO_STAGE_DIR}/work/EFI/BOOT/BOOTX64.EFI" \
    "boot/grub/grub.cfg=${ISO_STAGE_DIR}/work/grub-embed.cfg"

  truncate -s 20M "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img"
  mkfs.vfat "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" >/dev/null
  mmd -i "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" ::/EFI ::/EFI/BOOT
  mcopy -i "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" "${ISO_STAGE_DIR}/work/EFI/BOOT/BOOTX64.EFI" ::/EFI/BOOT/BOOTX64.EFI

  xorriso -as mkisofs \
    -iso-level 3 \
    -full-iso9660-filenames \
    -volid "${VOLUME_ID}" \
    -eltorito-alt-boot \
    -e EFI/efiboot.img \
    -no-emul-boot \
    -isohybrid-gpt-basdat \
    -output "${ISO_STAGE_DIR}/${ISO_FILENAME}" \
    "${ISO_STAGE_DIR}/image-root"
fi

echo "==> Generando metadata ISO"
sha256sum "${ISO_STAGE_DIR}/${ISO_FILENAME}" > "${ISO_STAGE_DIR}/metadata/SHA256SUMS"

cat > "${ISO_STAGE_DIR}/metadata/iso-summary.env" <<EOF
W4_PROFILE_ID="${PROFILE_ID}"
W4_PROFILE_NAME="${PROFILE_NAME}"
W4_ISO_FILENAME="${ISO_FILENAME}"
W4_VOLUME_ID="${VOLUME_ID}"
W4_IMAGE_ROOT="${IMAGE_ROOT_DIR}"
W4_STAGE_DIR="${ISO_STAGE_DIR}"
W4_GENERATED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

echo "==> Sincronizando artefactos ISO al workspace"
rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}"
rsync -a --delete "${ISO_STAGE_DIR}/" "${OUTPUT_DIR}/"

echo "==> ISO preparada"
echo "Resultado: ${OUTPUT_DIR}/${ISO_FILENAME}"
BASH;

    $script = str_replace(
        ['%PROFILE_ID%', '%PROFILE_NAME%', '%ISO_FILENAME%', '%VOLUME_ID%'],
        [$vars['profile_id'], $vars['profile_name'], $vars['iso_filename'], $vars['volume_id']],
        $script
    );

    return str_replace(["\r\n", "\r"], "\n", $script);
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $liveManifestPath = null;
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

            case '--live-manifest':
                $liveManifestPath = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $liveManifestPath === null) {
        throw new ValidationError('Debe indicar --profile o --live-manifest');
    }

    if ($liveManifestPath === null) {
        $liveManifestPath = $defaultLiveBundleDir . DIRECTORY_SEPARATOR . $profileId . DIRECTORY_SEPARATOR . 'live-manifest.json';
    }

    $liveManifest = readJsonFile($liveManifestPath);
    validateLiveManifest($liveManifest, $liveManifestPath);

    /** @var string $resolvedProfileId */
    $resolvedProfileId = $liveManifest['profile_id'];
    if ($profileId !== null && $profileId !== $resolvedProfileId) {
        throw new ValidationError('El perfil indicado no coincide con el live manifest proporcionado');
    }

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $resolvedProfileId;
    }

    if (!is_dir($outputPath) && !mkdir($outputPath, 0777, true) && !is_dir($outputPath)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputPath));
    }

    $vars = isoVariables($liveManifest);

    $files = [
        'README-ISO.txt' => buildReadme($vars),
        'compose-iso.sh' => buildComposeScript($vars),
    ];

    foreach ($files as $relativePath => $contents) {
        $targetPath = $outputPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir) && !mkdir($parentDir, 0777, true) && !is_dir($parentDir)) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta %s', $parentDir));
        }

        if (file_put_contents($targetPath, $contents) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $targetPath));
        }
    }

    $isoManifest = [
        'iso_bundle_schema_version' => 1,
        'kind' => 'iso-bundle',
        'profile_id' => $liveManifest['profile_id'],
        'profile_name' => $liveManifest['profile_name'],
        'base_manifest_id' => $liveManifest['base_manifest_id'],
        'source_live_manifest' => basename($liveManifestPath),
        'branding' => $vars,
        'iso_tooling_required' => ['xorriso', 'grub-mkrescue|grub-mkstandalone', 'mtools', 'dosfstools'],
        'generated_files' => array_keys($files),
        'next_steps' => [
            'instalar tooling ISO en WSL si aplica',
            'componer ISO arrancable UEFI',
            'verificar checksum del artefacto',
            'probar arranque en VM',
        ],
    ];

    $manifestPath = $outputPath . DIRECTORY_SEPARATOR . 'iso-manifest.json';
    if (file_put_contents($manifestPath, json_encode($isoManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $manifestPath));
    }

    printJson([
        'status' => 'ok',
        'profile_id' => $resolvedProfileId,
        'output_directory' => $outputPath,
        'generated_files' => array_merge(['iso-manifest.json'], array_keys($files)),
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
