<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\Update\RepositoryPublicationToolkit;

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repositories';

/**
 * @param list<string> $command
 * @return array{exitCode:int,stdout:string,stderr:string}
 */
function runPhpCommand(array $command, string $cwd): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        throw new ValidationError(sprintf('No se pudo ejecutar el comando: %s', implode(' ', $command)));
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exitCode' => $exitCode,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

/**
 * @return array<string, mixed>
 */
function decodeJsonPayload(string $json): array
{
    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('La salida JSON del runner firmado es invalida: %s', $exception->getMessage()));
    }

    if (!is_array($data)) {
        throw new ValidationError('La salida JSON del runner firmado debe ser un objeto');
    }

    return $data;
}

try {
    $arguments = $argv ?? [];
    $snapshotId = null;
    $bundleDir = null;
    $outputDir = null;
    $distribution = 'Ubuntu';
    $gpgHomedir = null;
    $gpgPassphrase = null;
    $checkOnly = false;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
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
                $bundleDir = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            case '--distribution':
                $distribution = $value;
                break;

            case '--gpg-homedir':
                $gpgHomedir = $value;
                break;

            case '--gpg-passphrase':
                $gpgPassphrase = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($snapshotId === null && $bundleDir === null) {
        throw new ValidationError('Debe indicar --snapshot-id o --bundle');
    }

    if ($bundleDir === null) {
        $bundleDir = $defaultBundleRoot . DIRECTORY_SEPARATOR . $snapshotId;
    }

    if (!is_dir($bundleDir)) {
        throw new ValidationError(sprintf('No existe el bundle de repositorio indicado: %s', $bundleDir));
    }

    $toolkit = new RepositoryPublicationToolkit();
    $bundleManifest = $toolkit->readRepositoryBundleManifest($bundleDir);

    $outputDir ??= $toolkit->determineDefaultOutputDir($rootDir, $bundleManifest, 'prod');

    $runnerCommand = [
        PHP_BINARY,
        $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_update_repository_bundle_in_wsl.php',
        '--bundle',
        $bundleDir,
        '--output-dir',
        $outputDir,
        '--distribution',
        $distribution,
        '--signing-profile',
        'prod',
    ];

    if ($gpgHomedir !== null) {
        $runnerCommand[] = '--gpg-homedir';
        $runnerCommand[] = $gpgHomedir;
    }

    if ($gpgPassphrase !== null) {
        $runnerCommand[] = '--gpg-passphrase';
        $runnerCommand[] = $gpgPassphrase;
    }

    if ($checkOnly) {
        $runnerCommand[] = '--check-only';
    }

    $runnerResult = runPhpCommand($runnerCommand, $rootDir);
    if ($runnerResult['exitCode'] !== 0) {
        throw new ValidationError(trim($runnerResult['stderr']) !== '' ? trim($runnerResult['stderr']) : 'Fallo el runner firmado oficial');
    }

    $runnerPayload = decodeJsonPayload($runnerResult['stdout']);

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'publication_profile' => 'official-prod',
            'bundle_dir' => $bundleDir,
            'output_dir' => $outputDir,
            'distribution' => $distribution,
            'snapshot_id' => $bundleManifest['repository_snapshot']['id'],
            'channel' => $bundleManifest['repository_snapshot']['channel'],
            'verification_targets' => [
                'repo.env',
                'dists/' . $bundleManifest['repository_snapshot']['channel'] . '/Release',
                'dists/' . $bundleManifest['repository_snapshot']['channel'] . '/InRelease',
                'dists/' . $bundleManifest['repository_snapshot']['channel'] . '/Release.gpg',
                'keyrings/w4-update-archive-keyring.gpg',
            ],
            'runner_preview' => $runnerPayload,
        ]);
        exit(0);
    }

    $verification = $toolkit->validatePublishedRepository($outputDir, $bundleManifest);
    $publicationManifestPath = $outputDir . DIRECTORY_SEPARATOR . 'publication-manifest.json';

    $toolkit->writePublicationRecord(
        $publicationManifestPath,
        [
            'publication_manifest_schema_version' => 1,
            'kind' => 'published-update-repository',
            'publication_profile' => 'official-prod',
            'signing_profile' => 'prod',
            'bundle_dir' => $bundleDir,
            'output_dir' => $outputDir,
            'distribution' => $distribution,
            'published_at' => gmdate('c'),
            'repository_snapshot' => $bundleManifest['repository_snapshot'],
            'target_version' => $bundleManifest['target_version'],
            'verification' => $verification,
        ]
    );

    printJson([
        'status' => 'ok',
        'publication_profile' => 'official-prod',
        'bundle_dir' => $bundleDir,
        'output_dir' => $outputDir,
        'distribution' => $distribution,
        'snapshot_id' => $bundleManifest['repository_snapshot']['id'],
        'channel' => $bundleManifest['repository_snapshot']['channel'],
        'publication_manifest' => $publicationManifestPath,
        'verification' => $verification,
        'runner_result' => [
            'status' => $runnerPayload['status'] ?? null,
            'signing_mode' => $runnerPayload['signing_mode'] ?? null,
            'gpg_key_id' => $runnerPayload['gpg_key_id'] ?? null,
            'gpg_homedir_wsl' => $runnerPayload['gpg_homedir_wsl'] ?? null,
        ],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
