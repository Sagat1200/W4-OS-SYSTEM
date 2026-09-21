<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Manifest\ManifestToolkit;

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repositories';

/**
 * @param array<string> $left
 * @param array<string> $right
 * @return array<string>
 */
function sortedStringDifference(array $left, array $right): array
{
    $difference = array_values(array_diff($left, $right));
    sort($difference);

    return $difference;
}

/**
 * @param array<string> $left
 * @param array<string> $right
 * @return array<string>
 */
function sortedStringIntersection(array $left, array $right): array
{
    $intersection = array_values(array_intersect($left, $right));
    sort($intersection);

    return $intersection;
}

/**
 * @param array<string, mixed> $baseManifest
 * @param array<string, mixed> $homeProfile
 * @param array<string, mixed> $businessProfile
 * @return array<int, array{name:string,version:string,architecture:string,depends:string,description:string,profile_scope:string,source:string}>
 */
function buildRepositoryPackageCatalog(
    array $baseManifest,
    array $homeProfile,
    array $businessProfile,
    string $targetVersion
): array {
    /** @var array<string> $baseRequiredPackages */
    $baseRequiredPackages = $baseManifest['packages']['required'];
    $homeRequiredPackages = $homeProfile['required_packages'];
    $businessRequiredPackages = $businessProfile['required_packages'];

    $desktopSharedPackages = sortedStringIntersection(
        sortedStringDifference($homeRequiredPackages, $baseRequiredPackages),
        sortedStringDifference($businessRequiredPackages, $baseRequiredPackages)
    );

    $homeEditionPackages = sortedStringDifference(
        sortedStringDifference($homeRequiredPackages, $baseRequiredPackages),
        $desktopSharedPackages
    );
    $businessEditionPackages = sortedStringDifference(
        sortedStringDifference($businessRequiredPackages, $baseRequiredPackages),
        $desktopSharedPackages
    );

    return [
        [
            'name' => 'w4-base-meta',
            'version' => $targetVersion,
            'architecture' => 'all',
            'depends' => implode(', ', $baseRequiredPackages),
            'description' => 'W4 OS base metapackage derived from the base manifest',
            'profile_scope' => 'base',
            'source' => 'w4-linux-base.packages.required',
        ],
        [
            'name' => 'w4-desktop-meta',
            'version' => $targetVersion,
            'architecture' => 'all',
            'depends' => implode(', ', $desktopSharedPackages),
            'description' => 'W4 OS desktop metapackage derived from shared edition requirements',
            'profile_scope' => 'desktop',
            'source' => 'intersection(w4-os-home,w4-os-business).packages.required',
        ],
        [
            'name' => 'w4-recovery-tools',
            'version' => $targetVersion,
            'architecture' => 'all',
            'depends' => 'bash, btrfs-progs',
            'description' => 'W4 OS recovery tools package for update validation',
            'profile_scope' => 'base',
            'source' => 'update-recovery-tooling',
        ],
        [
            'name' => 'w4-home-meta',
            'version' => $targetVersion,
            'architecture' => 'all',
            'depends' => implode(', ', array_merge(['w4-base-meta', 'w4-desktop-meta'], $homeEditionPackages)),
            'description' => 'W4 OS Home metapackage derived from the home edition profile',
            'profile_scope' => 'home',
            'source' => 'w4-os-home',
        ],
        [
            'name' => 'w4-business-meta',
            'version' => $targetVersion,
            'architecture' => 'all',
            'depends' => implode(', ', array_merge(['w4-base-meta', 'w4-desktop-meta'], $businessEditionPackages)),
            'description' => 'W4 OS Business metapackage derived from the business edition profile',
            'profile_scope' => 'business',
            'source' => 'w4-os-business',
        ],
    ];
}

/**
 * @param array<int, array{name:string,version:string,architecture:string,depends:string,description:string,profile_scope:string,source:string}> $packages
 */
function renderRepositoryBuildScript(
    string $snapshotId,
    string $channel,
    string $targetVersion,
    string $packageSet,
    array $packages
): string {
    $packageLines = [];
    $packageSourceLines = [];

    foreach ($packages as $package) {
        $packageLines[] = sprintf(
            'build_package "%s" "%s" "%s" "%s" "%s" "%s"',
            addcslashes($package['name'], "\\\"\n\r\t\$"),
            addcslashes($package['version'], "\\\"\n\r\t\$"),
            addcslashes($package['architecture'], "\\\"\n\r\t\$"),
            addcslashes($package['depends'], "\\\"\n\r\t\$"),
            addcslashes($package['description'], "\\\"\n\r\t\$"),
            addcslashes($package['profile_scope'], "\\\"\n\r\t\$")
        );
        $packageSourceLines[] = sprintf(
            '  "%s": "%s"',
            addcslashes($package['name'], "\\\"\n\r\t\$"),
            addcslashes($package['source'], "\\\"\n\r\t\$")
        );
    }

    $packageBlock = implode(PHP_EOL, $packageLines);
    $packageSourceBlock = implode(",\n", $packageSourceLines);

    return <<<BASH
#!/usr/bin/env bash
set -euo pipefail

OUTPUT_DIR="\${1:-}"
SCRIPT_DIR="\$(cd -- "\$(dirname -- "\${BASH_SOURCE[0]}")" && pwd)"
SNAPSHOT_ID="${snapshotId}"
CHANNEL="${channel}"
TARGET_VERSION="${targetVersion}"
PACKAGE_SET="${packageSet}"
WORK_ROOT="\${W4_UPDATE_REPO_WORK_ROOT:-/var/tmp/w4-os-system/update-repositories/\${SNAPSHOT_ID}}"
REPO_DIR="\${WORK_ROOT}/repo"
PKG_DIR="\${WORK_ROOT}/packages"
POOL_DIR="\${REPO_DIR}/pool/main/w4"
DISTS_DIR="\${REPO_DIR}/dists/\${CHANNEL}/main/binary-amd64"

if [[ -z "\${OUTPUT_DIR}" ]]; then
  OUTPUT_DIR="\${SCRIPT_DIR}/output"
fi

log() {
  echo "[w4-update-repo] \$*" >&2
}

die() {
  log "ERROR: \$*"
  exit 1
}

require_command() {
  local command_name="\$1"
  command -v "\${command_name}" >/dev/null 2>&1 || die "falta el comando requerido: \${command_name}"
}

build_package() {
  local name="\$1"
  local version="\$2"
  local architecture="\$3"
  local depends="\$4"
  local description="\$5"
  local profile_scope="\$6"

  local package_root="\${PKG_DIR}/\${name}"
  local output_path="\${POOL_DIR}/\${name}_\${version}_\${architecture}.deb"

  rm -rf "\${package_root}"
  mkdir -p "\${package_root}/DEBIAN" "\${package_root}/usr/share/w4/packages"

  cat > "\${package_root}/DEBIAN/control" <<EOF
Package: \${name}
Version: \${version}
Section: metapackages
Priority: optional
Architecture: \${architecture}
Maintainer: W4 OS System <w4@example.invalid>
Description: \${description}
EOF

  if [[ -n "\${depends}" ]]; then
    printf 'Depends: %s\n' "\${depends}" >> "\${package_root}/DEBIAN/control"
  fi

  cat > "\${package_root}/usr/share/w4/packages/\${name}.json" <<EOF
{
  "package_name": "\${name}",
  "version": "\${version}",
  "repository_snapshot_id": "\${SNAPSHOT_ID}",
  "channel": "\${CHANNEL}",
  "package_set": "\${PACKAGE_SET}",
  "profile_scope": "\${profile_scope}"
}
EOF

  dpkg-deb --build "\${package_root}" "\${output_path}" >/dev/null
}

log "Preparando repositorio APT W4 de laboratorio"
log "Snapshot: \${SNAPSHOT_ID}"
log "Canal: \${CHANNEL}"
log "Version objetivo: \${TARGET_VERSION}"
log "Package set: \${PACKAGE_SET}"

require_command dpkg-deb
require_command dpkg-scanpackages
require_command date
require_command gzip
require_command stat
require_command sha256sum

rm -rf "\${REPO_DIR}" "\${PKG_DIR}"
mkdir -p "\${REPO_DIR}" "\${PKG_DIR}" "\${POOL_DIR}" "\${DISTS_DIR}"

${packageBlock}

(
  cd "\${REPO_DIR}"
  dpkg-scanpackages --multiversion pool /dev/null > Packages
  gzip -kf Packages
  sha256sum Packages Packages.gz > SHA256SUMS
)

cp "\${REPO_DIR}/Packages" "\${DISTS_DIR}/Packages"
cp "\${REPO_DIR}/Packages.gz" "\${DISTS_DIR}/Packages.gz"

packages_sha256="\$(sha256sum "\${DISTS_DIR}/Packages" | awk '{print \$1}')"
packages_gz_sha256="\$(sha256sum "\${DISTS_DIR}/Packages.gz" | awk '{print \$1}')"
packages_size="\$(stat -c %s "\${DISTS_DIR}/Packages")"
packages_gz_size="\$(stat -c %s "\${DISTS_DIR}/Packages.gz")"
release_date="\$(LC_ALL=C date -Ru)"

cat > "\${REPO_DIR}/dists/\${CHANNEL}/Release" <<EOF
Origin: W4 OS
Label: W4 OS
Suite: \${CHANNEL}
Codename: \${CHANNEL}
Date: \${release_date}
Architectures: amd64
Components: main
Description: W4 OS update repository generated from repository manifests
SHA256:
 \${packages_sha256} \${packages_size} main/binary-amd64/Packages
 \${packages_gz_sha256} \${packages_gz_size} main/binary-amd64/Packages.gz
EOF

cat > "\${REPO_DIR}/apt-source.list.template" <<'EOF'
deb [trusted=yes] file:__W4_REPO_ROOT__ ./
EOF

cat > "\${REPO_DIR}/apt-source.dists.list.template" <<EOF
deb [trusted=yes] file:__W4_REPO_ROOT__ \${CHANNEL} main
EOF

cat > "\${REPO_DIR}/repo.env" <<EOF
W4_REPOSITORY_SNAPSHOT_ID="\${SNAPSHOT_ID}"
W4_REPOSITORY_CHANNEL="\${CHANNEL}"
W4_REPOSITORY_TARGET_VERSION="\${TARGET_VERSION}"
W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ \${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL="deb [trusted=yes] file:\${OUTPUT_DIR} \${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ./"
W4_UPDATE_APT_SOURCE_LINE_LOCAL="deb [trusted=yes] file:\${OUTPUT_DIR} ./"
W4_UPDATE_APT_SOURCE_LINE_DISTS_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ \${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_DISTS_LOCAL="deb [trusted=yes] file:\${OUTPUT_DIR} \${CHANNEL} main"
EOF

cat > "\${REPO_DIR}/package-sources.json" <<EOF
{
${packageSourceBlock}
}
EOF

cat > "\${REPO_DIR}/REPOSITORY_README.txt" <<EOF
W4 OS Update Repository (lab)

Contenido:
- pool/main/w4/*.deb
- Packages
- Packages.gz
- SHA256SUMS
- dists/\${CHANNEL}/main/binary-amd64/Packages
- dists/\${CHANNEL}/main/binary-amd64/Packages.gz
- dists/\${CHANNEL}/Release
- apt-source.list.template
- apt-source.dists.list.template
- package-sources.json
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir preferentemente:
   W4_UPDATE_APT_SOURCE_MODE=dists
   W4_UPDATE_APT_SOURCE_LINE_DISTS="deb [trusted=yes] file:/ruta/al/repositorio \${CHANNEL} main"
3. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

Origen declarativo:
- los metapaquetes se derivan de los manifests/perfiles reales del repositorio
- package-sources.json resume la fuente usada para cada paquete

Compatibilidad:
- el runner ya prioriza la source dists en modo auto, que es la ruta recomendada para futuras pruebas
- se conserva la source plana para el laboratorio actual: deb [trusted=yes] file:/ruta ./
- ademas se publica una estructura tipo APT bajo dists/\${CHANNEL} para preparar una fuente W4 mas cercana a produccion

Snapshot: \${SNAPSHOT_ID}
Canal: \${CHANNEL}
Version objetivo: \${TARGET_VERSION}
Package set: \${PACKAGE_SET}
EOF

rm -rf "\${OUTPUT_DIR}"
mkdir -p "\${OUTPUT_DIR}"
cp -a "\${REPO_DIR}/." "\${OUTPUT_DIR}/"

log "Repositorio listo en \${OUTPUT_DIR}"
echo "Resultado: \${OUTPUT_DIR}"
BASH;
}

try {
    $arguments = $argv ?? [];
    $snapshotId = null;
    $channel = null;
    $targetVersion = '1.0.1-lab';
    $outputDir = null;
    $packageSet = 'both';

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--snapshot-id':
                $snapshotId = $value;
                break;

            case '--channel':
                $channel = $value;
                break;

            case '--target-version':
                $targetVersion = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            case '--package-set':
                $packageSet = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($snapshotId === null) {
        throw new ValidationError('Debe indicar --snapshot-id');
    }

    if ($channel === null) {
        throw new ValidationError('Debe indicar --channel');
    }

    if (!preg_match('/^[A-Za-z0-9._:-]+$/', $snapshotId)) {
        throw new ValidationError('snapshot-id contiene caracteres no soportados');
    }

    if (!preg_match('/^[A-Za-z0-9._-]+$/', $channel)) {
        throw new ValidationError('channel contiene caracteres no soportados');
    }

    if (!in_array($packageSet, ['home', 'business', 'both'], true)) {
        throw new ValidationError('package-set debe ser home, business o both');
    }

    $manifestToolkit = new ManifestToolkit($rootDir);
    $manifests = $manifestToolkit->loadManifests();
    $manifestToolkit->validateAll($manifests);

    /** @var array<string, mixed> $baseManifest */
    $baseManifest = $manifests['w4-linux-base'];
    $homeProfile = $manifestToolkit->resolveProfile($manifests, 'w4-os-home');
    $businessProfile = $manifestToolkit->resolveProfile($manifests, 'w4-os-business');

    $packageCatalog = buildRepositoryPackageCatalog(
        $baseManifest,
        $homeProfile,
        $businessProfile,
        $targetVersion
    );

    $packages = array_values(array_filter(
        $packageCatalog,
        static function (array $package) use ($packageSet): bool {
            if ($packageSet === 'both') {
                return true;
            }

            if (in_array($package['profile_scope'], ['base', 'desktop'], true)) {
                return true;
            }

            return $package['profile_scope'] === $packageSet;
        }
    ));

    if ($outputDir === null) {
        $outputDir = $defaultBundleRoot . DIRECTORY_SEPARATOR . $snapshotId;
    }

    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta del bundle: %s', $outputDir));
    }

    $manifest = [
        'repository_bundle_schema_version' => 1,
        'kind' => 'update-repository-bundle',
        'repository_snapshot' => [
            'id' => $snapshotId,
            'channel' => $channel,
        ],
        'target_version' => $targetVersion,
        'package_set' => $packageSet,
        'package_strategy' => 'derived-from-manifests',
        'source_profiles' => [
            'base' => 'w4-linux-base',
            'home' => 'w4-os-home',
            'business' => 'w4-os-business',
        ],
        'packages' => $packages,
        'generated_artifacts' => [
            'build-repo.sh',
            'repository-manifest.json',
            'apt-source.list.template',
            'apt-source.dists.list.template',
            'REPOSITORY_BUNDLE_README.txt',
        ],
    ];

    $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'repository-manifest.json';
    $scriptPath = $outputDir . DIRECTORY_SEPARATOR . 'build-repo.sh';
    $sourceTemplatePath = $outputDir . DIRECTORY_SEPARATOR . 'apt-source.list.template';
    $distsSourceTemplatePath = $outputDir . DIRECTORY_SEPARATOR . 'apt-source.dists.list.template';
    $readmePath = $outputDir . DIRECTORY_SEPARATOR . 'REPOSITORY_BUNDLE_README.txt';

    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($manifestJson === false) {
        throw new ValidationError('No se pudo serializar repository-manifest.json');
    }

    if (file_put_contents($manifestPath, $manifestJson . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    $buildScript = renderRepositoryBuildScript($snapshotId, $channel, $targetVersion, $packageSet, $packages);
    if (file_put_contents($scriptPath, $buildScript . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $scriptPath));
    }

    if (file_put_contents($sourceTemplatePath, "deb [trusted=yes] file:__W4_REPO_ROOT__ ./" . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $sourceTemplatePath));
    }

    if (file_put_contents($distsSourceTemplatePath, sprintf("deb [trusted=yes] file:__W4_REPO_ROOT__ %s main", $channel) . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $distsSourceTemplatePath));
    }

    $readme = <<<TEXT
W4 OS Update Repository Bundle

Archivos generados:
- build-repo.sh
- repository-manifest.json
- apt-source.list.template
- apt-source.dists.list.template

Uso previsto:
1. ejecutar build-repo.sh en Linux o WSL para producir el repositorio APT
2. copiar el repositorio resultante al sistema objetivo
3. preferir W4_UPDATE_APT_SOURCE_MODE=dists y W4_UPDATE_APT_SOURCE_LINE_DISTS al lanzar run-update-offline.sh
4. usar W4_UPDATE_APT_SOURCE_LINE o W4_UPDATE_APT_SOURCE_FILE solo como compatibilidad/fallback

Parametros embebidos:
- snapshot_id: {$snapshotId}
- channel: {$channel}
- target_version: {$targetVersion}
- package_set: {$packageSet}
TEXT;

    if (file_put_contents($readmePath, $readme . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $readmePath));
    }

    printJson([
        'status' => 'ok',
        'bundle_dir' => $outputDir,
        'snapshot_id' => $snapshotId,
        'channel' => $channel,
        'target_version' => $targetVersion,
        'package_set' => $packageSet,
        'package_count' => count($packages),
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
