<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$vendorAutoload = $rootDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    spl_autoload_register(static function (string $className) use ($rootDir): void {
        $prefix = 'W4\\OS\\';
        if (!str_starts_with($className, $prefix)) {
            return;
        }

        $relativeClass = substr($className, strlen($prefix));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        $classPath = $rootDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($classPath)) {
            require_once $classPath;
        }
    });
}

if (!class_exists('ValidationError', false)) {
    class_alias(\W4\OS\Support\ValidationError::class, 'ValidationError');
}

if (!class_exists('ManifestToolkit', false)) {
    class_alias(\W4\OS\Manifest\ManifestToolkit::class, 'ManifestToolkit');
}

if (!class_exists('InstallerToolkit', false)) {
    class_alias(\W4\OS\Installer\InstallerToolkit::class, 'InstallerToolkit');
}

if (!class_exists('UpdateToolkit', false)) {
    class_alias(\W4\OS\Update\UpdateToolkit::class, 'UpdateToolkit');
}

if (!function_exists('printJson')) {
    /**
     * @param array<string, mixed> $data
     */
    function printJson(array $data): void
    {
        \W4\OS\Support\JsonPrinter::print($data);
    }
}
