<?php

declare(strict_types=1);

namespace W4\OS\Update;

use JsonException;
use W4\OS\Support\ValidationError;

final class RepositoryPublicationToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function readJsonFile(string $path): array
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

        if (!is_array($data)) {
            throw new ValidationError(sprintf(
                'JSON invalido en %s: la raiz debe ser un objeto o arreglo JSON',
                $path
            ));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function readRepositoryBundleManifest(string $bundleDir): array
    {
        $manifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'repository-manifest.json';
        $manifest = $this->readJsonFile($manifestPath);

        if (($manifest['repository_bundle_schema_version'] ?? null) !== 1) {
            throw new ValidationError('repository-manifest.json: repository_bundle_schema_version debe ser 1');
        }

        if (($manifest['kind'] ?? null) !== 'update-repository-bundle') {
            throw new ValidationError('repository-manifest.json: kind debe ser update-repository-bundle');
        }

        $repositorySnapshot = $manifest['repository_snapshot'] ?? null;
        if (!is_array($repositorySnapshot)) {
            throw new ValidationError('repository-manifest.json: repository_snapshot debe ser un objeto');
        }

        foreach (['id', 'channel'] as $field) {
            $value = $repositorySnapshot[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf('repository-manifest.json: repository_snapshot.%s debe ser un string no vacio', $field));
            }
        }

        $targetVersion = $manifest['target_version'] ?? null;
        if (!is_string($targetVersion) || $targetVersion === '') {
            throw new ValidationError('repository-manifest.json: target_version debe ser un string no vacio');
        }

        return $manifest;
    }

    public function determineDefaultOutputDir(string $rootDir, array $bundleManifest, string $signingProfile = 'prod'): string
    {
        $snapshotId = $this->requireRepositorySnapshotField($bundleManifest, 'id');
        $suffix = match ($signingProfile) {
            'prod' => '-signed-prod',
            'lab' => '-signed-auto',
            default => '-signed',
        };

        return $rootDir
            . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'update'
            . DIRECTORY_SEPARATOR . 'repository-output'
            . DIRECTORY_SEPARATOR . $snapshotId . $suffix;
    }

    /**
     * @return array<string, string>
     */
    public function parseRepoEnv(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer repo.env: %s', $path));
        }

        $result = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match('/^([A-Z0-9_]+)="(.*)"$/', $line, $matches)) {
                throw new ValidationError(sprintf('repo.env contiene una linea no soportada: %s', $line));
            }

            $result[$matches[1]] = stripcslashes($matches[2]);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $bundleManifest
     * @return array<string, mixed>
     */
    public function validatePublishedRepository(string $outputDir, array $bundleManifest): array
    {
        if (!is_dir($outputDir)) {
            throw new ValidationError(sprintf('No existe el directorio del repositorio publicado: %s', $outputDir));
        }

        $channel = $this->requireRepositorySnapshotField($bundleManifest, 'channel');
        $snapshotId = $this->requireRepositorySnapshotField($bundleManifest, 'id');
        $targetVersion = $this->requireNonEmptyString($bundleManifest, 'target_version');

        $requiredFiles = [
            'repo.env',
            'dists' . DIRECTORY_SEPARATOR . $channel . DIRECTORY_SEPARATOR . 'Release',
            'dists' . DIRECTORY_SEPARATOR . $channel . DIRECTORY_SEPARATOR . 'InRelease',
            'dists' . DIRECTORY_SEPARATOR . $channel . DIRECTORY_SEPARATOR . 'Release.gpg',
            'keyrings' . DIRECTORY_SEPARATOR . 'w4-update-archive-keyring.gpg',
        ];

        foreach ($requiredFiles as $relativePath) {
            $absolutePath = $outputDir . DIRECTORY_SEPARATOR . $relativePath;
            if (!is_file($absolutePath)) {
                throw new ValidationError(sprintf('Falta el artefacto publicado requerido: %s', $absolutePath));
            }
        }

        $repoEnv = $this->parseRepoEnv($outputDir . DIRECTORY_SEPARATOR . 'repo.env');
        $this->requireEnvValue($repoEnv, 'W4_REPOSITORY_SNAPSHOT_ID', $snapshotId);
        $this->requireEnvValue($repoEnv, 'W4_REPOSITORY_CHANNEL', $channel);
        $this->requireEnvValue($repoEnv, 'W4_REPOSITORY_TARGET_VERSION', $targetVersion);
        $this->requireEnvValue($repoEnv, 'W4_REPOSITORY_SIGNING_MODE', 'gpg');
        $this->requireEnvValue($repoEnv, 'W4_REPOSITORY_KEYRING_RELATIVE_PATH', 'keyrings/w4-update-archive-keyring.gpg');
        $this->requireEnvValue($repoEnv, 'W4_UPDATE_APT_SOURCE_MODE_DEFAULT', 'dists');

        foreach ([
            'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE',
            'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL',
            'W4_UPDATE_APT_SOURCE_LINE_SIGNED_TEMPLATE',
            'W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL',
        ] as $field) {
            $value = $repoEnv[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf('repo.env: falta %s', $field));
            }

            if (!str_contains($value, 'signed-by=')) {
                throw new ValidationError(sprintf('repo.env: %s no esta alineado con signed-by=', $field));
            }
        }

        $signedLocal = $repoEnv['W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL'];
        if (str_contains($signedLocal, '[trusted=yes]')) {
            throw new ValidationError('repo.env: W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL no debe usar trusted=yes');
        }

        if (!str_contains($signedLocal, 'keyrings/w4-update-archive-keyring.gpg')) {
            throw new ValidationError('repo.env: la source firmada local no apunta al keyring esperado');
        }

        if (!str_contains($signedLocal, ' file:')) {
            throw new ValidationError('repo.env: la source firmada local no expone una ruta file: valida');
        }

        return [
            'snapshot_id' => $snapshotId,
            'channel' => $channel,
            'target_version' => $targetVersion,
            'output_dir' => $outputDir,
            'repo_env' => [
                'signing_mode' => $repoEnv['W4_REPOSITORY_SIGNING_MODE'],
                'keyring_relative_path' => $repoEnv['W4_REPOSITORY_KEYRING_RELATIVE_PATH'],
                'default_source_mode' => $repoEnv['W4_UPDATE_APT_SOURCE_MODE_DEFAULT'],
                'signed_source_line_local' => $signedLocal,
            ],
            'verified_artifacts' => array_values(array_map(
                static fn (string $relativePath): string => str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
                $requiredFiles
            )),
        ];
    }

    /**
     * @param array<string, mixed> $publicationRecord
     */
    public function writePublicationRecord(string $path, array $publicationRecord): void
    {
        $json = json_encode($publicationRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new ValidationError(sprintf('No se pudo serializar el manifiesto de publicacion: %s', $path));
        }

        $written = file_put_contents($path, $json . PHP_EOL);
        if ($written === false) {
            throw new ValidationError(sprintf('No se pudo escribir el manifiesto de publicacion: %s', $path));
        }
    }

    /**
     * @param array<string, mixed> $bundleManifest
     */
    private function requireRepositorySnapshotField(array $bundleManifest, string $field): string
    {
        $repositorySnapshot = $bundleManifest['repository_snapshot'] ?? null;
        if (!is_array($repositorySnapshot)) {
            throw new ValidationError('repository-manifest.json: repository_snapshot debe ser un objeto');
        }

        $value = $repositorySnapshot[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('repository-manifest.json: repository_snapshot.%s debe ser un string no vacio', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requireNonEmptyString(array $data, string $field): string
    {
        $value = $data[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('repository-manifest.json: %s debe ser un string no vacio', $field));
        }

        return $value;
    }

    /**
     * @param array<string, string> $repoEnv
     */
    private function requireEnvValue(array $repoEnv, string $field, string $expectedValue): void
    {
        $value = $repoEnv[$field] ?? null;
        if ($value !== $expectedValue) {
            throw new ValidationError(sprintf(
                'repo.env: %s debe ser %s y actualmente es %s',
                $field,
                $expectedValue,
                is_string($value) ? $value : '(ausente)'
            ));
        }
    }
}
