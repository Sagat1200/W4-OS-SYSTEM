<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Installer\InstallerToolkit;
use W4\OS\Support\ValidationError;

final class InstallerToolkitTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
    }

    public function testCreateInstallationPlanFromRealVirtualBoxInventory(): void
    {
        $toolkit = new InstallerToolkit();
        $buildInput = $this->readJson('build/inputs/w4-os-home.build-input.json');
        $installationProfile = $this->homeProfileBoundToVirtualBoxInventory();
        $inventory = $this->readJson('build/install-inventory/virtualbox-live-home.json');

        $toolkit->validateBuildInput($buildInput, 'build/inputs/w4-os-home.build-input.json');
        $toolkit->validateInstallationProfile($installationProfile, 'installer-profiles/w4-os-home.vm-install.json');
        $toolkit->validateDiskInventory($inventory, 'build/install-inventory/virtualbox-live-home.json');

        $plan = $toolkit->createInstallationPlan($installationProfile, $buildInput, $inventory);

        self::assertSame('installation-plan', $plan['kind']);
        self::assertSame('/dev/sda', $plan['plan_binding']['selected_disk']['device']);
        self::assertSame(
            'VBOX_HARDDISK_VB85d286f4-23d1ffff',
            $plan['plan_binding']['selected_disk']['serial']
        );
        self::assertSame(
            'pci-0000:00:0d.0-ata-1.0',
            $plan['plan_binding']['selected_disk']['by_path']
        );
        self::assertTrue($plan['summary']['destructive']);
        self::assertSame(5, $plan['summary']['subvolume_count']);
        self::assertGreaterThan(8 * 1073741824, $plan['summary']['encrypted_root_bytes']);
        self::assertContains('first-boot', $plan['summary']['requires_post_install_validation']);
    }

    public function testCreateInstallationPlanRejectsAmbiguousSelector(): void
    {
        $toolkit = new InstallerToolkit();
        $buildInput = $this->readJson('build/inputs/w4-os-home.build-input.json');
        $installationProfile = $this->homeProfileBoundToVirtualBoxInventory();
        $inventory = $this->readJson('build/install-inventory/virtualbox-live-home.json');

        $inventory['disks'][] = [
            'device' => '/dev/sdb',
            'serial' => 'VBOX_HARDDISK_VB85d286f4-23d1ffff',
            'wwid' => null,
            'by_path' => 'pci-0000:00:0d.0-ata-1.0',
            'size_bytes' => 35218731520,
            'is_installation_media' => false,
            'has_partitions' => false,
            'has_filesystem_signatures' => false,
            'read_only' => false,
        ];

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('ambiguo');

        $toolkit->createInstallationPlan($installationProfile, $buildInput, $inventory);
    }

    public function testValidateDiskInventoryRequiresReadOnlyFlag(): void
    {
        $toolkit = new InstallerToolkit();
        $inventory = $this->readJson('build/install-inventory/virtualbox-live-home.json');

        unset($inventory['disks'][0]['read_only']);

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('read_only debe ser booleano');

        $toolkit->validateDiskInventory($inventory, 'build/install-inventory/virtualbox-live-home.json');
    }

    public function testCreateInstallationPlanRejectsReadOnlyDisk(): void
    {
        $toolkit = new InstallerToolkit();
        $buildInput = $this->readJson('build/inputs/w4-os-home.build-input.json');
        $installationProfile = $this->homeProfileBoundToVirtualBoxInventory();
        $inventory = $this->readJson('build/install-inventory/virtualbox-live-home.json');

        $inventory['disks'][0]['read_only'] = true;

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('solo lectura');

        $toolkit->createInstallationPlan($installationProfile, $buildInput, $inventory);
    }

    public function testReadJsonFileRejectsScalarJsonRoot(): void
    {
        $toolkit = new InstallerToolkit();
        $jsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-installer-scalar-' . bin2hex(random_bytes(6)) . '.json';

        self::assertNotFalse(file_put_contents($jsonPath, '"valor-escalar"' . PHP_EOL));

        try {
            $this->expectException(ValidationError::class);
            $this->expectExceptionMessage('la raiz debe ser un objeto o arreglo JSON');

            $toolkit->readJsonFile($jsonPath);
        } finally {
            @unlink($jsonPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function homeProfileBoundToVirtualBoxInventory(): array
    {
        $profile = $this->readJson('installer-profiles/w4-os-home.vm-install.json');

        $profile['target']['disk_selector'] = [
            'serial' => 'VBOX_HARDDISK_VB85d286f4-23d1ffff',
            'by_path' => 'pci-0000:00:0d.0-ata-1.0',
        ];
        $profile['id'] = 'w4-os-home-vm-empty-disk-virtualbox';
        $profile['name'] = 'W4 OS Home VM Empty Disk VirtualBox';

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $relativePath): array
    {
        $path = $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer fixture %s', $relativePath));

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
