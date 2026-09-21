<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repositories';
$defaultOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repository-output';

/**
 * @return string
 */
function runWindowsCommand(string $command): string
{
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new ValidationError(trim(implode(PHP_EOL, $output)) ?: sprintf('Fallo al ejecutar: %s', $command));
    }

    return trim(implode(PHP_EOL, $output));
}

/**
 * @return array<int, string>
 */
function listWslDistros(): array
{
    $output = runWindowsCommand('wsl -l -q');
    $lines = preg_split('/\r?\n/', $output) ?: [];
    $distros = [];

    foreach ($lines as $line) {
        $line = trim(str_replace("\0", '', $line));
        if ($line !== '') {
            $distros[] = $line;
        }
    }

    return $distros;
}

function quoteForWindowsCommand(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
}

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function quoteWslDistribution(string $value): string
{
    if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
        return $value;
    }

    return quoteForWindowsCommand($value);
}

function isWslNativePath(string $path): bool
{
    return str_starts_with($path, '/');
}

function resolvePathForWsl(string $distribution, string $path): string
{
    if (isWslNativePath($path)) {
        return $path;
    }

    return runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($path)
    ));
}

try {
    $arguments = $argv ?? [];
    $snapshotId = null;
    $bundlePath = null;
    $outputDir = null;
    $distribution = 'Ubuntu';
    $checkOnly = false;
    $signingMode = 'unsigned';
    $gpgKeyId = null;
    $gpgHomedir = null;
    $gpgPassphrase = null;
    $gpgSecretKeyFile = null;
    $gpgOwnertrustFile = null;
    $generateLabKey = false;
    $labKeyType = 'rsa3072';
    $labKeyUsage = 'sign';
    $labKeyExpire = '7d';

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }

        if ($argument === '--generate-lab-key') {
            $generateLabKey = true;
            continue;
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--snapshot-id':
                $snapshotId = $value;
                break;

            case '--bundle':
                $bundlePath = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            case '--distribution':
                $distribution = $value;
                break;

            case '--signing-mode':
                $signingMode = $value;
                break;

            case '--gpg-key-id':
                $gpgKeyId = $value;
                break;

            case '--gpg-homedir':
                $gpgHomedir = $value;
                break;

            case '--gpg-passphrase':
                $gpgPassphrase = $value;
                break;

            case '--gpg-secret-key-file':
                $gpgSecretKeyFile = $value;
                break;

            case '--gpg-ownertrust-file':
                $gpgOwnertrustFile = $value;
                break;

            case '--lab-key-type':
                $labKeyType = $value;
                break;

            case '--lab-key-usage':
                $labKeyUsage = $value;
                break;

            case '--lab-key-expire':
                $labKeyExpire = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($snapshotId === null && $bundlePath === null) {
        throw new ValidationError('Debe indicar --snapshot-id o --bundle');
    }

    if (!in_array($signingMode, ['unsigned', 'gpg'], true)) {
        throw new ValidationError('signing-mode debe ser unsigned o gpg');
    }

    if ($gpgSecretKeyFile !== null && !isWslNativePath($gpgSecretKeyFile) && !is_file($gpgSecretKeyFile)) {
        throw new ValidationError(sprintf('No existe el archivo de clave secreta GPG indicado: %s', $gpgSecretKeyFile));
    }

    if ($gpgOwnertrustFile !== null && !isWslNativePath($gpgOwnertrustFile) && !is_file($gpgOwnertrustFile)) {
        throw new ValidationError(sprintf('No existe el archivo ownertrust GPG indicado: %s', $gpgOwnertrustFile));
    }

    if ($generateLabKey) {
        $signingMode = 'gpg';
        if ($gpgKeyId === null) {
            $gpgKeyId = 'W4-Update-Lab';
        }
    }

    if ($generateLabKey && ($gpgSecretKeyFile !== null || $gpgOwnertrustFile !== null)) {
        throw new ValidationError('No combine --generate-lab-key con --gpg-secret-key-file ni --gpg-ownertrust-file');
    }

    if ($signingMode === 'gpg' && $gpgKeyId === null) {
        throw new ValidationError('Debe indicar --gpg-key-id cuando signing-mode=gpg');
    }

    if ($signingMode !== 'gpg' && ($gpgSecretKeyFile !== null || $gpgOwnertrustFile !== null)) {
        throw new ValidationError('Los archivos GPG persistentes solo aplican cuando signing-mode=gpg');
    }

    if ($gpgOwnertrustFile !== null && $gpgSecretKeyFile === null && $gpgHomedir === null) {
        throw new ValidationError('Use --gpg-ownertrust-file junto con --gpg-secret-key-file o un --gpg-homedir ya provisionado');
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($bundlePath === null) {
        $bundlePath = $defaultBundleRoot . DIRECTORY_SEPARATOR . $snapshotId;
    }

    if (!is_dir($bundlePath)) {
        throw new ValidationError(sprintf('No existe el bundle de repositorio indicado: %s', $bundlePath));
    }

    $buildScriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'build-repo.sh';
    if (!is_file($buildScriptPath)) {
        throw new ValidationError(sprintf('No existe build-repo.sh en el bundle: %s', $buildScriptPath));
    }

    if ($outputDir === null) {
        $outputDir = $defaultOutputRoot . DIRECTORY_SEPARATOR . ($snapshotId ?? basename($bundlePath));
    }

    $wslBuildScriptPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($buildScriptPath)
    ));

    $wslOutputDir = resolvePathForWsl($distribution, $outputDir);
    $wslGpgSecretKeyFile = null;
    if ($gpgSecretKeyFile !== null) {
        $wslGpgSecretKeyFile = resolvePathForWsl($distribution, $gpgSecretKeyFile);
    }

    $wslGpgOwnertrustFile = null;
    if ($gpgOwnertrustFile !== null) {
        $wslGpgOwnertrustFile = resolvePathForWsl($distribution, $gpgOwnertrustFile);
    }

    $requiresManagedHomedir = $generateLabKey || $wslGpgSecretKeyFile !== null || $wslGpgOwnertrustFile !== null;
    $wslGpgHomedir = null;
    if ($gpgHomedir !== null) {
        $wslGpgHomedir = resolvePathForWsl($distribution, $gpgHomedir);
    } elseif ($requiresManagedHomedir) {
        $wslGpgHomedir = sprintf(
            '/var/tmp/w4-os-system/update-repositories/%s-%s',
            $snapshotId ?? basename($bundlePath),
            $generateLabKey ? 'signing-lab' : 'signing-imported'
        );
    }

    $bashSegments = [
        sprintf('mkdir -p %s', quoteForBash($wslOutputDir)),
        sprintf('chmod +x %s', quoteForBash($wslBuildScriptPath)),
    ];

    if ($requiresManagedHomedir) {
        $bashSegments[] = sprintf('mkdir -p %s', quoteForBash((string) $wslGpgHomedir));
        $bashSegments[] = sprintf('chmod 700 %s', quoteForBash((string) $wslGpgHomedir));
    }

    if ($wslGpgSecretKeyFile !== null) {
        $bashSegments[] = sprintf(
            'if ! gpg --batch --homedir %s --list-secret-keys %s >/dev/null 2>&1; then gpg --batch --yes --homedir %s --import %s >/dev/null 2>&1; fi',
            quoteForBash((string) $wslGpgHomedir),
            quoteForBash((string) $gpgKeyId),
            quoteForBash((string) $wslGpgHomedir),
            quoteForBash($wslGpgSecretKeyFile)
        );
    }

    if ($wslGpgOwnertrustFile !== null) {
        $bashSegments[] = sprintf(
            'gpg --batch --yes --homedir %s --import-ownertrust %s >/dev/null 2>&1',
            quoteForBash((string) $wslGpgHomedir),
            quoteForBash($wslGpgOwnertrustFile)
        );
    }

    if ($generateLabKey) {
        $bashSegments[] = sprintf(
            'if ! gpg --batch --homedir %s --list-keys %s >/dev/null 2>&1; then gpg --batch --homedir %s --passphrase %s --quick-generate-key %s %s %s %s >/dev/null 2>&1; fi',
            quoteForBash((string) $wslGpgHomedir),
            quoteForBash((string) $gpgKeyId),
            quoteForBash((string) $wslGpgHomedir),
            quoteForBash($gpgPassphrase ?? ''),
            quoteForBash((string) $gpgKeyId),
            quoteForBash($labKeyType),
            quoteForBash($labKeyUsage),
            quoteForBash($labKeyExpire)
        );
    }

    if ($signingMode === 'gpg') {
        $bashSegments[] = 'export W4_UPDATE_REPO_SIGNING_MODE=gpg';
        $bashSegments[] = sprintf('export W4_UPDATE_REPO_GPG_KEY_ID=%s', quoteForBash((string) $gpgKeyId));
        if ($wslGpgHomedir !== null) {
            $bashSegments[] = sprintf('export W4_UPDATE_REPO_GPG_HOMEDIR=%s', quoteForBash($wslGpgHomedir));
        }
        if ($gpgPassphrase !== null) {
            $bashSegments[] = sprintf('export W4_UPDATE_REPO_GPG_PASSPHRASE=%s', quoteForBash($gpgPassphrase));
        }
    }

    $bashSegments[] = sprintf(
        'bash %s %s',
        quoteForBash($wslBuildScriptPath),
        quoteForBash($wslOutputDir)
    );

    $runCommand = sprintf(
        'wsl -d %s -u root -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand(implode(' && ', $bashSegments))
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'bundle_path_windows' => $bundlePath,
            'build_script_path_wsl' => $wslBuildScriptPath,
            'output_dir_windows' => isWslNativePath($outputDir) ? null : $outputDir,
            'output_dir_wsl' => $wslOutputDir,
            'signing_mode' => $signingMode,
            'gpg_key_id' => $gpgKeyId,
            'gpg_homedir_wsl' => $wslGpgHomedir,
            'gpg_secret_key_file_wsl' => $wslGpgSecretKeyFile,
            'gpg_ownertrust_file_wsl' => $wslGpgOwnertrustFile,
            'generate_lab_key' => $generateLabKey,
            'run_command' => $runCommand,
        ]);
        exit(0);
    }

    $executionOutput = runWindowsCommand($runCommand);

    printJson([
        'status' => 'ok',
        'distribution' => $distribution,
        'bundle_path_windows' => $bundlePath,
        'output_dir_windows' => isWslNativePath($outputDir) ? null : $outputDir,
        'output_dir_wsl' => $wslOutputDir,
        'signing_mode' => $signingMode,
        'gpg_key_id' => $gpgKeyId,
        'gpg_homedir_wsl' => $wslGpgHomedir,
        'gpg_secret_key_file_wsl' => $wslGpgSecretKeyFile,
        'gpg_ownertrust_file_wsl' => $wslGpgOwnertrustFile,
        'generate_lab_key' => $generateLabKey,
        'execution_output' => $executionOutput,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
