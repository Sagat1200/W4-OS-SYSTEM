<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install-inventory';

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

try {
    $arguments = $argv ?? [];
    $distribution = 'Ubuntu';
    $outputPath = null;
    $inventoryId = 'wsl-live-disk-inventory';
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
            case '--distribution':
                $distribution = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            case '--id':
                $inventoryId = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $inventoryId . '.json';
    }

    $collectorPath = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_disk_inventory.php';
    if (!is_file($collectorPath)) {
        throw new ValidationError(sprintf('No existe el colector de inventario: %s', $collectorPath));
    }

    $outputDirectory = dirname($outputPath);
    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputDirectory));
    }

    $runCommand = sprintf(
        'php %s --wsl-distribution %s --id %s --output %s',
        escapeshellarg($collectorPath),
        escapeshellarg($distribution),
        escapeshellarg($inventoryId),
        escapeshellarg($outputPath)
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'collector_path_windows' => $collectorPath,
            'output_path_windows' => $outputPath,
            'run_command' => $runCommand,
        ]);
        exit(0);
    }

    $executionOutput = runWindowsCommand($runCommand);

    printJson([
        'status' => 'ok',
        'distribution' => $distribution,
        'inventory_id' => $inventoryId,
        'output_path_windows' => $outputPath,
        'execution_output' => $executionOutput,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
