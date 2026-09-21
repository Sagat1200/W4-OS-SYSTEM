<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/UpdateToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'executors';

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
    $offlineScript = $toolkit->renderOfflineExecutorScript($plan, $rootDir);
    $healthCheckScript = $toolkit->renderHealthCheckScript($plan);
    $reconcileScript = $toolkit->renderReconcileScript($plan, $rootDir);

    $manifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'update-executor.json';
    $offlinePath = $bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh';
    $healthCheckPath = $bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh';
    $reconcilePath = $bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh';
    $readmePath = $bundleDir . DIRECTORY_SEPARATOR . 'UPDATE_EXECUTOR_README.txt';

    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($manifestJson === false) {
        throw new ValidationError('No se pudo serializar update-executor.json');
    }

    if (file_put_contents($manifestPath, $manifestJson . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    if (file_put_contents($offlinePath, $offlineScript . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $offlinePath));
    }

    if (file_put_contents($healthCheckPath, $healthCheckScript . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $healthCheckPath));
    }

    if (file_put_contents($reconcilePath, $reconcileScript . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $reconcilePath));
    }

    $readme = <<<TEXT
W4 OS Update Executor

Archivos generados:
- run-update-offline.sh
- run-health-checks.sh
- reconcile-after-reboot.sh
- update-executor.json

Uso previsto:
1. preparar primero el store durable con prepare_update_operation.php
2. ejecutar run-update-offline.sh hasta dejar la operacion en pending_health
3. tras el reinicio, ejecutar run-health-checks.sh
4. ejecutar reconcile-after-reboot.sh para confirmar o fallar la operacion

Variables utiles:
- W4_UPDATE_STORE_DIR: carpeta del store durable
- W4_UPDATE_ENGINE_ROOT: raiz del repo cuando el bundle se mueve fuera del arbol local
- W4_UPDATE_EXECUTE: usar 1 para habilitar apt-get y snapshot reales
- W4_UPDATE_APPLY_MODE: estrategia de aplicacion, por ahora `live-apt`
- W4_UPDATE_APT_SOURCE_MODE: `auto` por defecto; prioriza `W4_UPDATE_APT_SOURCE_LINE_DISTS` y cae a `W4_UPDATE_APT_SOURCE_LINE`
- W4_UPDATE_APT_SOURCE_LINE: source line APT temporal plana para compatibilidad de laboratorio
- W4_UPDATE_APT_SOURCE_LINE_DISTS: source line APT estilo `dists/<channel>` para el repo W4 alineado con APT real
- W4_UPDATE_APT_SOURCE_FILE: archivo `.list` alternativo para instalar temporalmente antes de `apt-get update`
- W4_UPDATE_FAIL_STAGE: inyeccion de fallo para laboratorio
- W4_UPDATE_OBSERVED_STAGE: estado observado tras reinicio
TEXT;

    if (file_put_contents($readmePath, $readme . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $readmePath));
    }

    printJson([
        'status' => 'ok',
        'bundle_dir' => $bundleDir,
        'operation_id' => $plan['operation_id'],
        'generated_artifacts' => $manifest['generated_artifacts'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
