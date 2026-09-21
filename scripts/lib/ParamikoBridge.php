<?php

declare(strict_types=1);

/**
 * @param list<string> $arguments
 */
function runParamikoBridge(string $pythonSource, array $arguments): int
{
    $tempPath = tempnam(sys_get_temp_dir(), 'w4-paramiko-');
    if ($tempPath === false) {
        fwrite(STDERR, "ERROR: No se pudo crear un archivo temporal para el bridge Paramiko.\n");

        return 1;
    }

    $pythonPath = $tempPath . '.py';
    if (!rename($tempPath, $pythonPath)) {
        @unlink($tempPath);
        fwrite(STDERR, "ERROR: No se pudo preparar el archivo temporal del bridge Paramiko.\n");

        return 1;
    }

    $pythonSource = str_replace("\r\n", "\n", $pythonSource);

    if (file_put_contents($pythonPath, $pythonSource) === false) {
        @unlink($pythonPath);
        fwrite(STDERR, "ERROR: No se pudo escribir el bridge Paramiko temporal.\n");

        return 1;
    }

    $pythonLauncher = getenv('W4_PARAMIKO_PYTHON');
    if ($pythonLauncher === false || trim($pythonLauncher) === '') {
        $candidates = [
            'C:\\Python313\\python.exe',
            'C:\\Users\\user\\AppData\\Local\\Programs\\Python\\Python314\\python.exe',
            'python',
        ];

        $resolvedLauncher = null;
        foreach ($candidates as $candidate) {
            if ($candidate === 'python' || is_file($candidate)) {
                $resolvedLauncher = $candidate;
                break;
            }
        }

        if ($resolvedLauncher === null) {
            @unlink($pythonPath);
            fwrite(STDERR, "ERROR: No se encontro un interprete Python compatible para el bridge Paramiko.\n");

            return 1;
        }

        $resolvedPython = $resolvedLauncher;
    } else {
        $resolvedPython = $pythonLauncher;
    }

    $previousDontWriteByteCode = getenv('PYTHONDONTWRITEBYTECODE');
    $previousProjectRoot = getenv('W4_PARAMIKO_PROJECT_ROOT');
    putenv('PYTHONDONTWRITEBYTECODE=1');
    putenv('W4_PARAMIKO_PROJECT_ROOT=' . dirname(__DIR__, 2));

    $command = escapeshellarg($resolvedPython) . ' -B ' . escapeshellarg($pythonPath);
    foreach ($arguments as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($previousDontWriteByteCode === false) {
        putenv('PYTHONDONTWRITEBYTECODE');
    } else {
        putenv('PYTHONDONTWRITEBYTECODE=' . $previousDontWriteByteCode);
    }

    if ($previousProjectRoot === false) {
        putenv('W4_PARAMIKO_PROJECT_ROOT');
    } else {
        putenv('W4_PARAMIKO_PROJECT_ROOT=' . $previousProjectRoot);
    }

    @unlink($pythonPath);

    if ($output !== []) {
        fwrite($exitCode === 0 ? STDOUT : STDERR, implode(PHP_EOL, $output) . PHP_EOL);
    }

    return is_int($exitCode) ? $exitCode : 1;
}
