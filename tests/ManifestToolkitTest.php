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
            ['w4-linux-base', 'w4-os-business', 'w4-os-home', 'w4-os-server'],
            array_keys($manifests)
        );
        self::assertSame('resolved-profile', $resolved['kind']);
        self::assertSame('w4-os-home', $resolved['id']);
        self::assertContains('w4-base-meta', $resolved['required_meta_packages']);
        self::assertContains('w4-desktop-meta', $resolved['required_meta_packages']);
        self::assertContains('w4-home-meta', $resolved['required_meta_packages']);
        self::assertContains('apt', $resolved['required_packages']);
        self::assertContains('apparmor', $resolved['required_packages']);
        self::assertContains('btrfs-progs', $resolved['required_packages']);
        self::assertContains('php-cli', $resolved['required_packages']);
        self::assertContains('ufw', $resolved['required_packages']);
        self::assertContains('os-prober', $resolved['required_packages']);
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
        self::assertSame('stable', $buildInput['upstream']['track']);
        self::assertSame('trixie', $buildInput['upstream']['codename']);
        self::assertContains('apparmor', $buildInput['packages']['required']);
        self::assertContains('btrfs-progs', $buildInput['packages']['required']);
        self::assertContains('firefox-esr', $buildInput['packages']['required']);
        self::assertContains('php-cli', $buildInput['packages']['required']);
        self::assertContains('ufw', $buildInput['packages']['required']);
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

    public function testServerProfileResolvesHeadlessWithoutBusinessInheritance(): void
    {
        $toolkit = new ManifestToolkit($this->rootDir);

        $manifests = $toolkit->loadManifests();
        $toolkit->validateAll($manifests);
        $resolved = $toolkit->resolveProfile($manifests, 'w4-os-server');

        self::assertSame('w4-os-server', $resolved['id']);
        self::assertSame('w4-linux-base', $resolved['inherits']);
        self::assertContains('w4-base-meta', $resolved['required_meta_packages']);
        self::assertContains('w4-server-meta', $resolved['required_meta_packages']);
        self::assertNotContains('w4-desktop-meta', $resolved['required_meta_packages']);
        self::assertContains('openssh-server', $resolved['required_packages']);
        self::assertContains('cryptsetup-initramfs', $resolved['required_packages']);
        self::assertContains('network-manager', $resolved['required_packages']);
        self::assertContains('ufw', $resolved['required_packages']);
        self::assertContains('gdisk', $resolved['required_packages']);
        self::assertContains('parted', $resolved['required_packages']);
        self::assertContains('dosfstools', $resolved['required_packages']);
        self::assertContains('e2fsprogs', $resolved['required_packages']);
        self::assertContains('squashfs-tools', $resolved['required_packages']);
        self::assertNotContains('os-prober', $resolved['required_packages']);
        self::assertNotContains('pipewire', $resolved['required_packages']);
        self::assertNotContains('xdg-desktop-portal', $resolved['required_packages']);
        self::assertNotContains('flatpak', $resolved['recommended_packages']);
        self::assertNotContains('fwupd', $resolved['recommended_packages']);
        self::assertNotContains('snapper', $resolved['recommended_packages']);
        self::assertContains('headless-default', $resolved['features']);
        self::assertContains('ssh-administration', $resolved['features']);
        self::assertContains('self-contained-live-installer', $resolved['features']);
    }

    public function testHomeAndBusinessKeepDesktopCompositionExplicitly(): void
    {
        $toolkit = new ManifestToolkit($this->rootDir);

        $manifests = $toolkit->loadManifests();
        $toolkit->validateAll($manifests);

        foreach (['w4-os-home', 'w4-os-business'] as $profileId) {
            $resolved = $toolkit->resolveProfile($manifests, $profileId);

            self::assertContains('w4-desktop-meta', $resolved['required_meta_packages']);
            self::assertContains('os-prober', $resolved['required_packages']);
            self::assertContains('pipewire', $resolved['required_packages']);
            self::assertContains('xdg-desktop-portal', $resolved['required_packages']);
            self::assertNotContains('w4-server-meta', $resolved['required_meta_packages']);
            self::assertNotContains('self-contained-live-installer', $resolved['features']);
        }
    }
}
