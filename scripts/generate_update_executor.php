<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;

require_once __DIR__ . '/lib/UpdateToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'executors';

function writeTextArtifact(string $path, string $contents): void
{
    $normalized = str_replace("\r\n", "\n", $contents);
    $normalized = str_replace("\r", "\n", $normalized);
    $normalized = rtrim($normalized, "\n") . "\n";

    if (file_put_contents($path, $normalized) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $path));
    }
}

function adaptEmbeddedStoreScript(string $script): string
{
    return str_replace(
        'STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"',
        'STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}}"',
        $script
    );
}

function renderExecutorEnv(): string
{
    return <<<'BASH'
#!/usr/bin/env bash
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
export W4_UPDATE_STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}}"
export W4_UPDATE_HEALTH_RESULT_PATH="${W4_UPDATE_HEALTH_RESULT_PATH:-${W4_UPDATE_STORE_DIR}/health-check-results.json}"
BASH;
}

try {
    $arguments = $argv ?? [];
    $planPath = null;
    $bundleDir = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--plan':
                $planPath = $value;
                break;

            case '--bundle-dir':
                $bundleDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($planPath === null) {
        throw new ValidationError('Debe indicar un archivo usando --plan');
    }

    $toolkit = new UpdateToolkit();
    $plan = $toolkit->readJsonFile($planPath);
    $toolkit->validateUpdatePlan($plan, $planPath);

    if ($bundleDir === null) {
        $bundleDir = $defaultOutputRoot . DIRECTORY_SEPARATOR . $plan['operation_id'];
    }

    if (!is_dir($bundleDir) && !mkdir($bundleDir, 0777, true) && !is_dir($bundleDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta del ejecutor: %s', $bundleDir));
    }

    $manifest = $toolkit->createUpdateExecutorManifest($plan);
    $offlineScript = adaptEmbeddedStoreScript($toolkit->renderOfflineExecutorScript($plan, $rootDir));
    $repoLauncherScript = $toolkit->renderRepositoryAwareLauncherScript();
    $healthCheckScript = adaptEmbeddedStoreScript($toolkit->renderHealthCheckScript($plan));
    $reconcileScript = adaptEmbeddedStoreScript($toolkit->renderReconcileScript($plan, $rootDir));
    $bundledReconcileOperationScript = $toolkit->renderBundledReconcileOperationScript();

    $manifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'update-executor.json';
    $envPath = $bundleDir . DIRECTORY_SEPARATOR . 'executor.env';
    $offlinePath = $bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh';
    $repoLauncherPath = $bundleDir . DIRECTORY_SEPARATOR . 'run-update-with-repo-env.sh';
    $healthCheckPath = $bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh';
    $reconcilePath = $bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh';
    $bundledReconcileOperationPath = $bundleDir . DIRECTORY_SEPARATOR . 'reconcile-update-operation.php';
    $readmePath = $bundleDir . DIRECTORY_SEPARATOR . 'UPDATE_EXECUTOR_README.txt';

    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($manifestJson === false) {
        throw new ValidationError('No se pudo serializar update-executor.json');
    }

    if (file_put_contents($manifestPath, $manifestJson . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    writeTextArtifact($envPath, renderExecutorEnv());
    writeTextArtifact($offlinePath, $offlineScript);
    writeTextArtifact($repoLauncherPath, $repoLauncherScript);
    writeTextArtifact($healthCheckPath, $healthCheckScript);
    writeTextArtifact($reconcilePath, $reconcileScript);
    writeTextArtifact($bundledReconcileOperationPath, $bundledReconcileOperationScript);

    $readme = <<<TEXT
W4 OS Update Executor

Archivos generados:
- executor.env
- run-update-offline.sh
- run-update-with-repo-env.sh
- run-health-checks.sh
- reconcile-after-reboot.sh
- reconcile-update-operation.php
- update-executor.json

Uso previsto:
1. preparar primero el store durable con prepare_update_operation.php
2. source executor.env antes de ejecutar los scripts del bundle
3. copiar tambien el repositorio APT reconstruido al sistema objetivo
4. ejecutar preferentemente run-update-with-repo-env.sh --repo-dir /ruta/al/repositorio hasta dejar la operacion en pending_health
5. tras el reinicio, ejecutar run-health-checks.sh
6. ejecutar reconcile-after-reboot.sh para confirmar o fallar la operacion usando el helper autocontenido del bundle

Variables utiles:
- W4_UPDATE_STORE_DIR: carpeta del store durable
- W4_UPDATE_ENGINE_ROOT: raiz del repo cuando el bundle se mueve fuera del arbol local
- W4_UPDATE_REPOSITORY_DIR: directorio real del repo APT copiado al sistema objetivo
- W4_UPDATE_REPOSITORY_ENV_FILE: ruta alternativa al repo.env del repositorio copiado
- W4_UPDATE_EXECUTE: usar 1 para habilitar apt-get y snapshot reales
- W4_UPDATE_APPLY_MODE: estrategia de aplicacion, por ahora `live-apt`
- W4_UPDATE_APT_SOURCE_MODE: `auto` por defecto; prioriza `W4_UPDATE_APT_SOURCE_LINE_DISTS` y cae a `W4_UPDATE_APT_SOURCE_LINE`
- W4_UPDATE_APT_SOURCE_LINE: source line APT temporal plana para compatibilidad de laboratorio
- W4_UPDATE_APT_SOURCE_LINE_DISTS: source line APT estilo `dists/<channel>` para el repo W4 alineado con APT real
- W4_UPDATE_APT_SOURCE_FILE: archivo `.list` alternativo para instalar temporalmente antes de `apt-get update`
- W4_UPDATE_FAIL_STAGE: inyeccion de fallo para laboratorio
- W4_UPDATE_OBSERVED_STAGE: estado observado tras reinicio
TEXT;

    writeTextArtifact($readmePath, $readme);

    $metadataToolkit = new ArtifactMetadataToolkit();

    printJson($metadataToolkit->createStatusPayload([
        'bundle_dir' => $bundleDir,
        'operation_id' => $plan['operation_id'],
        'generated_artifacts' => $manifest['generated_artifacts'],
    ]));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
