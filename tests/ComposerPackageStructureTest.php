<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Installer\InstallerToolkit;
use W4\OS\Manifest\ManifestToolkit;
use W4\OS\Support\ValidationError;

final class ComposerPackageStructureTest extends TestCase
{
    public function testNamespacedClassesAutoloadFromSrc(): void
    {
        self::assertTrue(class_exists(ManifestToolkit::class));
        self::assertTrue(class_exists(InstallerToolkit::class));
        self::assertTrue(class_exists(ValidationError::class));
    }

    public function testLegacyScriptBootstrapProvidesCompatibilityAliases(): void
    {
        require_once dirname(__DIR__) . '/scripts/bootstrap.php';

        self::assertTrue(class_exists('ManifestToolkit'));
        self::assertTrue(class_exists('InstallerToolkit'));
        self::assertTrue(class_exists('ValidationError'));
        self::assertTrue(function_exists('printJson'));
    }
}
