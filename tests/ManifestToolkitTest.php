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
        self::assertContains('w4-desktop-gnome-meta', $resolved['required_meta_packages']);
        self::assertContains('w4-home-meta', $resolved['required_meta_packages']);
        self::assertContains('apt', $resolved['required_packages']);
        self::assertContains('apparmor', $resolved['required_packages']);
        self::assertContains('btrfs-progs', $resolved['required_packages']);
        self::assertContains('gdm3', $resolved['required_packages']);
        self::assertContains('at-spi2-core', $resolved['required_packages']);
        self::assertContains('gnome-accessibility-themes', $resolved['required_packages']);
        self::assertContains('gnome-session', $resolved['required_packages']);
        self::assertContains('gnome-shell', $resolved['required_packages']);
        self::assertContains('gnome-software', $resolved['required_packages']);
        self::assertContains('libatk-adaptor', $resolved['required_packages']);
        self::assertContains('nautilus', $resolved['required_packages']);
        self::assertContains('orca', $resolved['required_packages']);
        self::assertContains('php-cli', $resolved['required_packages']);
        self::assertContains('speech-dispatcher', $resolved['required_packages']);
        self::assertContains('ufw', $resolved['required_packages']);
        self::assertContains('os-prober', $resolved['required_packages']);
        self::assertContains('firefox-esr', $resolved['required_packages']);
        self::assertContains('evince', $resolved['required_packages']);
        self::assertContains('vlc', $resolved['required_packages']);
        self::assertContains('accessibility-baseline', $resolved['features']);
        self::assertContains('gnome-gdm-default-route', $resolved['features']);
        self::assertContains('home-onboarding', $resolved['features']);
        self::assertContains('reversible-branding-defaults', $resolved['features']);
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
        self::assertContains('at-spi2-core', $buildInput['packages']['required']);
        self::assertContains('btrfs-progs', $buildInput['packages']['required']);
        self::assertContains('evince', $buildInput['packages']['required']);
        self::assertContains('firefox-esr', $buildInput['packages']['required']);
        self::assertContains('gdm3', $buildInput['packages']['required']);
        self::assertContains('gnome-accessibility-themes', $buildInput['packages']['required']);
        self::assertContains('gnome-session', $buildInput['packages']['required']);
        self::assertContains('gnome-shell', $buildInput['packages']['required']);
        self::assertContains('libatk-adaptor', $buildInput['packages']['required']);
        self::assertContains('orca', $buildInput['packages']['required']);
        self::assertContains('speech-dispatcher', $buildInput['packages']['required']);
        self::assertContains('vlc', $buildInput['packages']['required']);
        self::assertContains('xdg-desktop-portal-gnome', $buildInput['packages']['required']);
        self::assertContains('php-cli', $buildInput['packages']['required']);
        self::assertContains('ufw', $buildInput['packages']['required']);
        self::assertContains('snapper', $buildInput['packages']['recommended']);
        self::assertNotContains('evince', $buildInput['packages']['recommended']);
        self::assertNotContains('vlc', $buildInput['packages']['recommended']);
        self::assertContains('accessibility-baseline', $buildInput['features']);
        self::assertContains('gnome-gdm-default-route', $buildInput['features']);
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

        $home = $toolkit->resolveProfile($manifests, 'w4-os-home');
        self::assertContains('w4-desktop-gnome-meta', $home['required_meta_packages']);
        self::assertContains('gdm3', $home['required_packages']);
        self::assertContains('gnome-shell', $home['required_packages']);
        self::assertContains('gnome-session', $home['required_packages']);
        self::assertContains('nautilus', $home['required_packages']);

        $business = $toolkit->resolveProfile($manifests, 'w4-os-business');
        self::assertNotContains('w4-desktop-gnome-meta', $business['required_meta_packages']);
        self::assertNotContains('gdm3', $business['required_packages']);
        self::assertNotContains('gnome-shell', $business['required_packages']);
    }
}
