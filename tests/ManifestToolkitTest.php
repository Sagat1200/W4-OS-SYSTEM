<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Manifest\ManifestToolkit;

final class ManifestToolkitTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
    }

    public function testLoadValidateAndResolveHomeProfileFromRepositoryFixtures(): void
    {
        $toolkit = new ManifestToolkit($this->rootDir);

        $manifests = $toolkit->loadManifests();
        $toolkit->validateAll($manifests);
        $resolved = $toolkit->resolveProfile($manifests, 'w4-os-home');

        self::assertSame(
            ['w4-linux-base', 'w4-os-business', 'w4-os-home'],
            array_keys($manifests)
        );
        self::assertSame('resolved-profile', $resolved['kind']);
        self::assertSame('w4-os-home', $resolved['id']);
        self::assertContains('w4-base-meta', $resolved['required_meta_packages']);
        self::assertContains('w4-home-meta', $resolved['required_meta_packages']);
        self::assertContains('apt', $resolved['required_packages']);
        self::assertContains('firefox-esr', $resolved['required_packages']);
        self::assertContains('vlc', $resolved['recommended_packages']);
        self::assertContains('home-onboarding', $resolved['features']);
    }

    public function testCreateBuildInputMergesBaseAndEditionPackages(): void
    {
        $toolkit = new ManifestToolkit($this->rootDir);

        $manifests = $toolkit->loadManifests();
        $toolkit->validateAll($manifests);
        $buildInput = $toolkit->createBuildInput($manifests, 'w4-os-home', 'stable', 'iso');

        self::assertSame('build-input', $buildInput['kind']);
        self::assertSame('stable', $buildInput['target']['release_channel']);
        self::assertSame('iso', $buildInput['target']['image_format']);
        self::assertContains('w4-main', $buildInput['repositories']);
        self::assertContains('firefox-esr', $buildInput['packages']['required']);
        self::assertContains('snapper', $buildInput['packages']['recommended']);
        self::assertSame(
            count($buildInput['packages']['required']),
            $buildInput['counts']['required_packages']
        );
        self::assertSame(
            'w4-os-home.profile.json',
            $buildInput['source_manifests']['profile']
        );
    }
}
