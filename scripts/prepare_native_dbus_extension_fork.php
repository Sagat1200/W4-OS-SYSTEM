<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$workspaceDir = $rootDir
    . DIRECTORY_SEPARATOR . 'build'
    . DIRECTORY_SEPARATOR . 'system-api'
    . DIRECTORY_SEPARATOR . 'native-dbus-extension-fork';
$sourceDir = null;
$sourceArchivePath = null;
$buildLogPath = null;
$buildEvidenceJsonPath = null;
$phpTarget = '8.4';
$peclPackage = 'DBus-0.1.1';
$format = 'json';

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--workspace-dir':
                $workspaceDir = $value;
                break;

            case '--source-dir':
                $sourceDir = $value;
                break;

            case '--source-archive':
                $sourceArchivePath = $value;
                break;

            case '--build-log-path':
                $buildLogPath = $value;
                break;

            case '--build-evidence-json-path':
                $buildEvidenceJsonPath = $value;
                break;

            case '--php-target':
                $phpTarget = $value;
                break;

            case '--pecl-package':
                $peclPackage = $value;
                break;

            case '--format':
                $format = strtolower($value);
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($format, ['json', 'text'], true)) {
        throw new ValidationError(sprintf('Formato no soportado: %s', $format));
    }

    $workspaceDir = normalizePath(resolveWorkspacePath($rootDir, $workspaceDir));
    $paths = [
        'workspace_dir' => $workspaceDir,
        'upstream_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'upstream',
        'fork_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'fork',
        'fork_current_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'fork' . DIRECTORY_SEPARATOR . 'current',
        'patches_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'patches',
        'evidence_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'evidence',
        'notes_dir' => $workspaceDir . DIRECTORY_SEPARATOR . 'notes',
    ];

    foreach ($paths as $path) {
        ensureDirectory($path);
    }

    $copiedInputs = [];
    $resolvedSourceDir = resolveOptionalInputPath($rootDir, $sourceDir);
    $resolvedSourceArchivePath = resolveOptionalInputPath($rootDir, $sourceArchivePath);
    $resolvedBuildLogPath = resolveOptionalInputPath($rootDir, $buildLogPath);
    $resolvedBuildEvidenceJsonPath = resolveOptionalInputPath($rootDir, $buildEvidenceJsonPath);
    $importedSourceDir = null;

    if ($resolvedSourceDir !== null) {
        if (!is_dir($resolvedSourceDir)) {
            throw new ValidationError(sprintf('El source-dir no existe o no es directorio: %s', $resolvedSourceDir));
        }

        $importedSourceDir = $paths['upstream_dir'] . DIRECTORY_SEPARATOR . 'imported-source';
        replaceDirectory($resolvedSourceDir, $importedSourceDir);
        $copiedInputs['source_dir'] = normalizePath($importedSourceDir);
    }

    if ($resolvedSourceArchivePath !== null) {
        if (!is_file($resolvedSourceArchivePath)) {
            throw new ValidationError(sprintf('El source-archive no existe: %s', $resolvedSourceArchivePath));
        }

        $importedArchivePath = $paths['upstream_dir'] . DIRECTORY_SEPARATOR . basename($resolvedSourceArchivePath);
        copyFile($resolvedSourceArchivePath, $importedArchivePath);
        $copiedInputs['source_archive'] = normalizePath($importedArchivePath);

        $importedSourceDir = $paths['upstream_dir'] . DIRECTORY_SEPARATOR . 'imported-source';
        extractArchiveIntoDirectory($importedArchivePath, $importedSourceDir);
        $copiedInputs['source_archive_extracted_dir'] = normalizePath($importedSourceDir);
    }

    if ($importedSourceDir !== null && is_dir($importedSourceDir)) {
        replaceDirectory($importedSourceDir, $paths['fork_current_dir']);
        $copiedInputs['fork_current_dir'] = normalizePath($paths['fork_current_dir']);
    }

    if ($resolvedBuildLogPath !== null) {
        if (!is_file($resolvedBuildLogPath)) {
            throw new ValidationError(sprintf('El build-log no existe: %s', $resolvedBuildLogPath));
        }

        $importedBuildLogPath = $paths['evidence_dir'] . DIRECTORY_SEPARATOR . 'native-dbus-build.log';
        copyFile($resolvedBuildLogPath, $importedBuildLogPath);
        $copiedInputs['build_log'] = normalizePath($importedBuildLogPath);
    }

    if ($resolvedBuildEvidenceJsonPath !== null) {
        if (!is_file($resolvedBuildEvidenceJsonPath)) {
            throw new ValidationError(sprintf('El build-evidence-json no existe: %s', $resolvedBuildEvidenceJsonPath));
        }

        $importedEvidenceJsonPath = $paths['evidence_dir'] . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json';
        copyFile($resolvedBuildEvidenceJsonPath, $importedEvidenceJsonPath);
        $copiedInputs['build_evidence_json'] = normalizePath($importedEvidenceJsonPath);
    }

    $patchQueueReadmePath = $paths['patches_dir'] . DIRECTORY_SEPARATOR . 'README.md';
    $portingPlanPath = $paths['notes_dir'] . DIRECTORY_SEPARATOR . 'php84-porting-plan.md';
    $manifestPath = $paths['workspace_dir'] . DIRECTORY_SEPARATOR . 'workspace-manifest.json';

    writeTextFile($patchQueueReadmePath, renderPatchQueueReadme($peclPackage));
    writeTextFile(
        $portingPlanPath,
        renderPortingPlan(
            $phpTarget,
            $peclPackage,
            $copiedInputs,
            $resolvedBuildLogPath,
            $resolvedBuildEvidenceJsonPath
        )
    );

    $manifest = [
        'api_version' => '1.0',
        'kind' => 'system-api-native-dbus-fork-workspace',
        'generated_at' => gmdate('c'),
        'workspace_dir' => normalizePath($paths['workspace_dir']),
        'php_target' => $phpTarget,
        'pecl_package' => $peclPackage,
        'paths' => array_map(static fn (string $path): string => normalizePath($path), $paths),
        'copied_inputs' => $copiedInputs,
        'notes' => [
            'porting_plan_path' => normalizePath($portingPlanPath),
            'patch_queue_readme_path' => normalizePath($patchQueueReadmePath),
        ],
        'next_steps' => [
            'Revisar el source upstream ya importado dentro de `upstream/imported-source` y trabajar el patch editable en `fork/current`.',
            'Localizar y reemplazar los usos legacy de TSRMLS, zend_parse_parameters y zend_object_store_get_object.',
            'Registrar cada intento de patch bajo patches/ y rerun de phpize/configure/make con evidencia nueva.',
        ],
    ];

    writeJsonFile($manifestPath, $manifest);

    $result = [
        'status' => 'workspace-prepared',
        'workspace_dir' => normalizePath($paths['workspace_dir']),
        'manifest_path' => normalizePath($manifestPath),
        'porting_plan_path' => normalizePath($portingPlanPath),
        'patch_queue_readme_path' => normalizePath($patchQueueReadmePath),
        'copied_inputs' => $copiedInputs,
        'next_action' => 'Aplicar el primer patch de compatibilidad PHP 8.4 dentro del workspace preparado.',
    ];

    if ($format === 'text') {
        fwrite(STDOUT, renderTextSummary($result));
    } else {
        printJson($result);
    }

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

function resolveWorkspacePath(string $rootDir, string $path): string
{
    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, '\\\\')) {
        return $path;
    }

    return $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function resolveOptionalInputPath(string $rootDir, ?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }

    return resolveWorkspacePath($rootDir, trim($path));
}

function normalizePath(string $path): string
{
    return rtrim(str_replace('\\', '/', trim($path)), '/');
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio: %s', $path));
    }
}

function writeTextFile(string $path, string $contents): void
{
    $result = file_put_contents($path, $contents);
    if ($result === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo: %s', $path));
    }
}

/**
 * @param array<string, mixed> $payload
 */
function writeJsonFile(string $path, array $payload): void
{
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    writeTextFile($path, $json . PHP_EOL);
}

function copyFile(string $sourcePath, string $targetPath): void
{
    $normalizedSourcePath = normalizePath($sourcePath);
    $normalizedTargetPath = normalizePath($targetPath);
    if ($normalizedSourcePath === $normalizedTargetPath) {
        return;
    }

    ensureDirectory(dirname($targetPath));
    if (!copy($sourcePath, $targetPath)) {
        throw new ValidationError(sprintf('No se pudo copiar %s hacia %s', $sourcePath, $targetPath));
    }
}

function replaceDirectory(string $sourceDir, string $targetDir): void
{
    deleteDirectory($targetDir);
    ensureDirectory($targetDir);

    $items = scandir($sourceDir);
    if ($items === false) {
        throw new ValidationError(sprintf('No se pudo leer el directorio: %s', $sourceDir));
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $item;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($sourcePath)) {
            replaceDirectory($sourcePath, $targetPath);
            continue;
        }

        copyFile($sourcePath, $targetPath);
    }
}

function deleteDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath)) {
            deleteDirectory($itemPath);
            continue;
        }

        @unlink($itemPath);
    }

    @rmdir($path);
}

function extractArchiveIntoDirectory(string $archivePath, string $targetDir): void
{
    deleteDirectory($targetDir);
    ensureDirectory($targetDir);

    $extension = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));
    if ($extension === 'zip') {
        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw new ValidationError(sprintf('No se pudo abrir el archivo zip: %s', $archivePath));
        }

        if (!$zip->extractTo($targetDir)) {
            $zip->close();
            throw new ValidationError(sprintf('No se pudo extraer el archivo zip: %s', $archivePath));
        }

        $zip->close();
        collapseSingleTopLevelDirectory($targetDir);
        return;
    }

    try {
        $archiveForExtraction = $archivePath;
        if (str_ends_with(strtolower($archivePath), '.tar.gz') || str_ends_with(strtolower($archivePath), '.tgz')) {
            $archiveForExtraction = decompressTarGzArchive($archivePath);
        }

        $phar = new PharData($archiveForExtraction);
        $phar->extractTo($targetDir, null, true);
        collapseSingleTopLevelDirectory($targetDir);
    } catch (Exception $exception) {
        throw new ValidationError(sprintf('No se pudo extraer el archivo %s: %s', $archivePath, $exception->getMessage()));
    }
}

function decompressTarGzArchive(string $archivePath): string
{
    $tarPath = preg_replace('/(\.tar)?\.gz$/i', '.tar', $archivePath);
    if ($tarPath === null || $tarPath === '') {
        throw new ValidationError(sprintf('No se pudo resolver el tar intermedio para %s', $archivePath));
    }

    if (is_file($tarPath)) {
        return $tarPath;
    }

    try {
        $phar = new PharData($archivePath);
        $phar->decompress();
    } catch (Exception $exception) {
        throw new ValidationError(sprintf('No se pudo descomprimir %s: %s', $archivePath, $exception->getMessage()));
    }

    if (!is_file($tarPath)) {
        throw new ValidationError(sprintf('El tar intermedio esperado no existe: %s', $tarPath));
    }

    return $tarPath;
}

function collapseSingleTopLevelDirectory(string $path): void
{
    $items = array_values(array_filter(scandir($path) ?: [], static function (string $item): bool {
        return $item !== '.' && $item !== '..';
    }));

    if (count($items) !== 1) {
        return;
    }

    $onlyItemPath = $path . DIRECTORY_SEPARATOR . $items[0];
    if (!is_dir($onlyItemPath)) {
        return;
    }

    $tempPath = $path . DIRECTORY_SEPARATOR . '__flattened';
    rename($onlyItemPath, $tempPath);

    $currentItems = array_values(array_filter(scandir($path) ?: [], static function (string $item): bool {
        return $item !== '.' && $item !== '..';
    }));

    foreach ($currentItems as $item) {
        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if ($itemPath === $tempPath) {
            continue;
        }

        if (is_dir($itemPath)) {
            deleteDirectory($itemPath);
            continue;
        }

        @unlink($itemPath);
    }

    $flattenedItems = scandir($tempPath);
    if ($flattenedItems === false) {
        throw new ValidationError(sprintf('No se pudo leer el directorio extraido: %s', $tempPath));
    }

    foreach ($flattenedItems as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        rename(
            $tempPath . DIRECTORY_SEPARATOR . $item,
            $path . DIRECTORY_SEPARATOR . $item
        );
    }

    @rmdir($tempPath);
}

/**
 * @param array<string, string> $copiedInputs
 */
function renderPortingPlan(
    string $phpTarget,
    string $peclPackage,
    array $copiedInputs,
    ?string $originalBuildLogPath,
    ?string $originalBuildEvidenceJsonPath
): string {
    $lines = [
        '# Native D-Bus PECL Fork Workspace',
        '',
        sprintf('- Target PHP: `%s`', $phpTarget),
        sprintf('- PECL package: `%s`', $peclPackage),
        '- Scope: preparar el fork transitorio autorizado para V1 sin perder la evidencia del build real.',
        '',
        '## Imported Evidence',
        '',
        sprintf(
            '- Build log: `%s`',
            $copiedInputs['build_log'] ?? ($originalBuildLogPath !== null ? normalizePath($originalBuildLogPath) : 'pending-import')
        ),
        sprintf(
            '- Structured evidence: `%s`',
            $copiedInputs['build_evidence_json'] ?? ($originalBuildEvidenceJsonPath !== null ? normalizePath($originalBuildEvidenceJsonPath) : 'pending-import')
        ),
        sprintf(
            '- Imported source tree: `%s`',
            $copiedInputs['source_archive_extracted_dir']
                ?? ($copiedInputs['source_dir'] ?? 'pending-import')
        ),
        sprintf(
            '- Editable fork tree: `%s`',
            $copiedInputs['fork_current_dir'] ?? 'pending-import'
        ),
        '',
        '## Patch Targets',
        '',
        '1. Revisar y reemplazar macros legacy `TSRMLS_*` incompatibles con PHP 8.4.',
        '2. Revisar llamadas a `zend_parse_parameters` y ajustar la firma segun la API moderna.',
        '3. Sustituir el acceso legacy a objetos (`zend_object_store_get_object`) por la ruta compatible con PHP moderno.',
        '4. Documentar cada ajuste incremental en `patches/` antes de reintentar `phpize`, `configure` y `make`.',
        '',
        '## Execution Loop',
        '',
        '1. Importar o actualizar el codigo fuente upstream dentro de `upstream/`.',
        '2. Trabajar el patch activo dentro de `fork/current/` y registrar la evidencia del cambio.',
        '3. Repetir `phpize`, `./configure` y `make`, guardando logs frescos en `evidence/`.',
        '4. Regenerar el reporte del runner para confirmar si el bloqueo se movio o quedo resuelto.',
        '',
    ];

    if (!isset($copiedInputs['source_dir']) && !isset($copiedInputs['source_archive'])) {
        $lines[] = '## Pending Input';
        $lines[] = '';
        $lines[] = '- Todavia no se importo el source upstream de `DBus 0.1.1`; ese es el siguiente paso tecnico dentro del workspace.';
        $lines[] = '';
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

function renderPatchQueueReadme(string $peclPackage): string
{
    return implode(PHP_EOL, [
        '# Patch Queue',
        '',
        sprintf('Este directorio guarda los parches incrementales del fork temporal de `%s`.', $peclPackage),
        '',
        'Convencion sugerida:',
        '',
        '- `0001-*.patch` para compatibilidad de compilacion basica.',
        '- `0002-*.patch` para limpieza de warnings o ajustes de API.',
        '- `series.txt` si despues se necesita un orden explicito.',
        '',
    ]) . PHP_EOL;
}

/**
 * @param array<string, mixed> $result
 */
function renderTextSummary(array $result): string
{
    $lines = [
        sprintf('status: %s', (string) ($result['status'] ?? 'unknown')),
        sprintf('workspace_dir: %s', (string) ($result['workspace_dir'] ?? 'n/a')),
        sprintf('manifest_path: %s', (string) ($result['manifest_path'] ?? 'n/a')),
        sprintf('porting_plan_path: %s', (string) ($result['porting_plan_path'] ?? 'n/a')),
        sprintf('fork_current_dir: %s', (string) (($result['copied_inputs']['fork_current_dir'] ?? 'n/a'))),
        sprintf('next_action: %s', (string) ($result['next_action'] ?? 'n/a')),
    ];

    return implode(PHP_EOL, $lines) . PHP_EOL;
}
