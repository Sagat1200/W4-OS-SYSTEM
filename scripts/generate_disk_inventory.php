<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/InstallerToolkit.php';

function quoteForShell(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function quoteForWindowsCommand(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
}

function quoteWslDistribution(string $value): string
{
    if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
        return $value;
    }

    return quoteForWindowsCommand($value);
}

/**
 * @return string
 */
function runInventoryCommand(string $command, ?string $wslDistribution): string
{
    $output = [];
    $exitCode = 0;

    if ($wslDistribution !== null) {
        $wrappedCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            quoteWslDistribution($wslDistribution),
            quoteForWindowsCommand($command)
        );
        exec($wrappedCommand . ' 2>&1', $output, $exitCode);
    } else {
        exec($command . ' 2>&1', $output, $exitCode);
    }

    if ($exitCode !== 0) {
        throw new ValidationError(trim(implode(PHP_EOL, $output)) ?: sprintf('Fallo al ejecutar: %s', $command));
    }

    return trim(implode(PHP_EOL, $output));
}

/**
 * @return array<string, mixed>
 */
function readJsonFromCommand(string $command, ?string $wslDistribution): array
{
    $raw = runInventoryCommand($command, $wslDistribution);

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Salida JSON invalida para "%s": %s', $command, $exception->getMessage()));
    }

    return $data;
}

/**
 * @return array<string, string>
 */
function readUdevProperties(string $devicePath, ?string $wslDistribution): array
{
    $properties = [];
    $command = sprintf('udevadm info --query=property --name=%s', quoteForShell($devicePath));
    $output = runInventoryCommand($command, $wslDistribution);
    $lines = preg_split('/\r?\n/', $output) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        if ($key !== '') {
            $properties[$key] = $value;
        }
    }

    return $properties;
}

/**
 * @return array<string, mixed>
 */
function readWipefsData(string $devicePath, ?string $wslDistribution): array
{
    $command = sprintf('wipefs -J %s', quoteForShell($devicePath));
    $output = [];
    $exitCode = 0;

    if ($wslDistribution !== null) {
        $wrappedCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            quoteWslDistribution($wslDistribution),
            quoteForWindowsCommand($command)
        );
        exec($wrappedCommand . ' 2>&1', $output, $exitCode);
    } else {
        exec($command . ' 2>&1', $output, $exitCode);
    }

    if ($exitCode !== 0) {
        return ['signatures' => []];
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode(trim(implode(PHP_EOL, $output)), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return ['signatures' => []];
    }

    return $data;
}

/**
 * @param array<string, mixed> $device
 * @return list<array<string, mixed>>
 */
function deviceChildren(array $device): array
{
    $children = $device['children'] ?? [];
    if (!is_array($children)) {
        return [];
    }

    /** @var list<array<string, mixed>> $children */
    return array_values(array_filter($children, 'is_array'));
}

/**
 * @param array<string, mixed> $device
 */
function hasFilesystemSignatures(array $device, string $devicePath, ?string $wslDistribution): bool
{
    if (is_string($device['fstype'] ?? null) && $device['fstype'] !== '') {
        return true;
    }

    foreach (deviceChildren($device) as $child) {
        if (is_string($child['fstype'] ?? null) && $child['fstype'] !== '') {
            return true;
        }
    }

    $wipefs = readWipefsData($devicePath, $wslDistribution);
    $signatures = $wipefs['signatures'] ?? [];
    return is_array($signatures) && $signatures !== [];
}

/**
 * @param array<string, mixed> $device
 * @param array<string, string> $udev
 */
function isInstallationMedia(array $device, string $devicePath, array $udev): bool
{
    $type = (string) ($device['type'] ?? '');
    if ($type === 'rom') {
        return true;
    }

    if (($udev['ID_CDROM'] ?? null) === '1') {
        return true;
    }

    $mountpoints = [];
    if (is_array($device['mountpoints'] ?? null)) {
        $mountpoints = $device['mountpoints'];
    } elseif (is_string($device['mountpoint'] ?? null) && $device['mountpoint'] !== '') {
        $mountpoints = [$device['mountpoint']];
    }

    foreach ($mountpoints as $mountpoint) {
        if (!is_string($mountpoint) || $mountpoint === '') {
            continue;
        }

        if (str_starts_with($mountpoint, '/cdrom')
            || str_starts_with($mountpoint, '/media')
            || str_starts_with($mountpoint, '/run/live')
            || str_starts_with($mountpoint, '/lib/live')
        ) {
            return true;
        }
    }

    return str_starts_with($devicePath, '/dev/sr');
}

/**
 * @param array<string, mixed> $device
 * @return array<string, mixed>
 */
function buildDiskRecord(array $device, ?string $wslDistribution): array
{
    $devicePath = (string) ($device['path'] ?? '');
    if ($devicePath === '') {
        throw new ValidationError('lsblk no devolvio path para uno de los discos');
    }

    $udev = readUdevProperties($devicePath, $wslDistribution);
    $children = deviceChildren($device);

    $serial = (string) (
        $udev['ID_SERIAL_SHORT']
        ?? $udev['ID_SERIAL']
        ?? $device['serial']
        ?? ''
    );
    $wwid = (string) (
        $udev['ID_WWN']
        ?? $udev['ID_WWN_WITH_EXTENSION']
        ?? $device['wwn']
        ?? ''
    );
    $byPath = (string) ($udev['ID_PATH'] ?? '');

    return [
        'device' => $devicePath,
        'serial' => $serial !== '' ? $serial : null,
        'wwid' => $wwid !== '' ? $wwid : null,
        'by_path' => $byPath !== '' ? $byPath : null,
        'size_bytes' => (int) ($device['size'] ?? 0),
        'is_installation_media' => isInstallationMedia($device, $devicePath, $udev),
        'has_partitions' => $children !== [],
        'has_filesystem_signatures' => hasFilesystemSignatures($device, $devicePath, $wslDistribution),
        'model' => (string) ($device['model'] ?? ''),
        'transport' => (string) ($device['tran'] ?? ''),
        'read_only' => ((string) ($device['ro'] ?? '0')) === '1',
        'removable' => ((string) ($device['rm'] ?? '0')) === '1',
    ];
}

/**
 * @return array<string, mixed>
 */
function buildInventory(string $inventoryId, ?string $wslDistribution): array
{
    $lsblk = readJsonFromCommand(
        'lsblk -J -b -o NAME,PATH,TYPE,SIZE,SERIAL,WWN,MODEL,TRAN,RM,RO,FSTYPE,MOUNTPOINT',
        $wslDistribution
    );
    $blockDevices = $lsblk['blockdevices'] ?? null;
    if (!is_array($blockDevices)) {
        throw new ValidationError('lsblk no devolvio blockdevices');
    }

    $disks = [];

    foreach ($blockDevices as $device) {
        if (!is_array($device)) {
            continue;
        }

        $type = (string) ($device['type'] ?? '');
        if (!in_array($type, ['disk', 'rom'], true)) {
            continue;
        }

        $disks[] = buildDiskRecord($device, $wslDistribution);
    }

    if ($disks === []) {
        throw new ValidationError('No se detectaron discos o medios en el inventario del sistema');
    }

    return [
        'inventory_schema_version' => 1,
        'kind' => 'disk-inventory',
        'id' => $inventoryId,
        'generated_by' => 'generate_disk_inventory.php',
        'generated_at' => gmdate('c'),
        'host' => $wslDistribution === null ? php_uname('n') : $wslDistribution,
        'disks' => $disks,
    ];
}

try {
    $arguments = $argv ?? [];
    $outputPath = null;
    $inventoryId = 'linux-disk-inventory';
    $wslDistribution = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--output':
                $outputPath = $value;
                break;

            case '--id':
                $inventoryId = $value;
                break;

            case '--wsl-distribution':
                $wslDistribution = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $toolkit = new InstallerToolkit();
    $inventory = buildInventory($inventoryId, $wslDistribution);
    $toolkit->validateDiskInventory($inventory, $outputPath ?? 'stdout');

    $json = json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError('No se pudo serializar el inventario de discos');
    }

    if ($outputPath === null) {
        echo $json . PHP_EOL;
    } else {
        $outputDirectory = dirname($outputPath);
        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputDirectory));
        }

        if (file_put_contents($outputPath, $json . PHP_EOL) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el inventario: %s', $outputPath));
        }

        printJson([
            'status' => 'ok',
            'inventory_id' => $inventoryId,
            'output' => $outputPath,
            'disk_count' => count($inventory['disks']),
            'source' => $wslDistribution ?? 'local-linux',
        ]);
    }

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
