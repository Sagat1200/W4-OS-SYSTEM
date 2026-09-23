<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class InstallationScriptsIntegrationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPrepareInstallationBundleGeneratesDerivedArtifactsInCustomBundleDir(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared-home-bundle';
        $inventoryPath = $this->fixturePath('build/install-inventory/virtualbox-live-home.json');

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--disk-inventory',
                $inventoryPath,
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('/dev/sda', $payload['selected_disk']);
        self::assertSame($bundleDir, $payload['bundle_dir']);

        $derivedProfile = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'installation-profile.derived.json');
        self::assertSame(
            'VBOX_HARDDISK_VB85d286f4-23d1ffff',
            $derivedProfile['target']['disk_selector']['serial']
        );
        self::assertSame(
            'pci-0000:00:0d.0-ata-1.0',
            $derivedProfile['target']['disk_selector']['by_path']
        );

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'CHECK_ONLY_PREPARATION.txt');
    }

    public function testPrepareInstallationBundleRejectsExplicitReadOnlyDisk(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared-home-readonly';
        $inventoryPath = $this->tempDir . DIRECTORY_SEPARATOR . 'readonly-inventory.json';
        $inventory = $this->decodeJsonFile($this->fixturePath('build/install-inventory/virtualbox-live-home.json'));
        $inventory['disks'][0]['read_only'] = true;
        self::assertNotFalse(file_put_contents($inventoryPath, json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--disk-inventory',
                $inventoryPath,
                '--device',
                '/dev/sda',
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('solo lectura', $result['stderr']);
    }

    public function testPrepareInstallationBundleRejectsScalarInventoryJson(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared-home-invalid-json';
        $inventoryPath = $this->tempDir . DIRECTORY_SEPARATOR . 'invalid-inventory.json';
        self::assertNotFalse(file_put_contents($inventoryPath, '"valor-escalar"' . PHP_EOL));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--disk-inventory',
                $inventoryPath,
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('la raiz debe ser un objeto o arreglo JSON', $result['stderr']);
    }

    public function testGenerateInstallationExecutorEmitsCheckOnlyScriptAndUpdatesBundleManifest(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-home-bundle';
        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle temporal');

        copy(
            $this->fixturePath('build/install/w4-os-home/installation-plan.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json'
        );
        copy(
            $this->fixturePath('build/install/w4-os-home/installation-bundle.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json'
        );

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_installation_executor.php'),
            [
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);

        $applyScriptPath = $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh';
        $applyScript = file_get_contents($applyScriptPath);
        self::assertNotFalse($applyScript);
        self::assertStringContainsString('Modo por defecto: check-only', $applyScript);
        self::assertStringContainsString('W4_INSTALL_EXECUTE=1', $applyScript);
        self::assertStringContainsString("SIZE_TOLERANCE_BYTES='1048576'", $applyScript);
        self::assertStringContainsString("TARGET_DISK='/dev/sda'", $applyScript);
        self::assertStringContainsString('export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"', $applyScript);
        self::assertStringContainsString('echo "[w4-install] $*" >&2', $applyScript);
        self::assertStringContainsString('mount --bind /sys/firmware/efi/efivars "${TARGET_ROOT}/sys/firmware/efi/efivars"', $applyScript);
        self::assertStringContainsString('mount --bind /dev/pts "${TARGET_ROOT}/dev/pts"', $applyScript);
        self::assertStringNotContainsString('mount --bind /run "${TARGET_ROOT}/run"', $applyScript);
        self::assertStringContainsString('chroot_has_command() {', $applyScript);
        self::assertStringContainsString('chroot "${TARGET_ROOT}" /bin/bash -lc "command -v', $applyScript);
        self::assertStringContainsString('cp -L /etc/resolv.conf "${TARGET_ROOT}/etc/resolv.conf"', $applyScript);
        self::assertStringContainsString('No se encontraron artefactos de kernel en /boot; reinstalando paquetes linux-image', $applyScript);
        self::assertStringContainsString('preferred_kernel_package="linux-image-amd64"', $applyScript);
        self::assertStringContainsString('Asegurando soporte initramfs para cryptroot', $applyScript);
        self::assertStringContainsString('apt-get install -y cryptsetup-initramfs', $applyScript);
        self::assertStringContainsString('Reinstalando metapaquete ${preferred_kernel_package}', $applyScript);
        self::assertStringContainsString("dpkg-query -W -f='\\\${db:Status-Abbrev} \\\${Package}\\n' 'linux-image-[0-9]*'", $applyScript);
        self::assertStringContainsString("grep -v -- '-unsigned$' || true", $applyScript);
        self::assertStringContainsString('Reinstalando paquetes kernel especificos: ${kernel_package_names[*]}', $applyScript);
        self::assertStringContainsString('la reinstalacion del kernel no genero initrd.img en /boot y mkinitramfs no existe en el sistema destino', $applyScript);
        self::assertStringContainsString("find \"\${TARGET_ROOT}/boot\" -maxdepth 1 -type f -name 'vmlinuz-*' -printf '%f\\n' | sed 's/^vmlinuz-//' | sort", $applyScript);
        self::assertStringContainsString('Generando initrd manual para ${kernel_version}', $applyScript);
        self::assertStringContainsString('chroot "${TARGET_ROOT}" mkinitramfs -o "/boot/initrd.img-${kernel_version}" "${kernel_version}"', $applyScript);
        self::assertStringContainsString('la reinstalacion del kernel no genero vmlinuz en /boot', $applyScript);
        self::assertStringContainsString('la reinstalacion del kernel no genero initrd.img en /boot', $applyScript);
        self::assertStringContainsString('grub-install no esta disponible; instalando paquetes EFI requeridos', $applyScript);
        self::assertStringContainsString('apt-get install -y grub-efi-amd64 grub-efi-amd64-bin grub2-common shim-signed efibootmgr', $applyScript);
        self::assertStringContainsString('grub-install sigue sin estar disponible en el sistema destino', $applyScript);
        self::assertStringContainsString('grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id="W4 OS" --recheck', $applyScript);
        self::assertStringContainsString('grub-install --target=x86_64-efi --efi-directory=/boot/efi --removable --recheck', $applyScript);
        self::assertStringContainsString('update-initramfs devolvio un error; se conserva el initrd ya generado en /boot', $applyScript);
        self::assertStringContainsString('BOOTX64.EFI', $applyScript);
        self::assertStringContainsString('mkdir -p "${TARGET_ROOT}/boot"', $applyScript);
        self::assertStringContainsString('mkdir -p "${TARGET_ROOT}/boot/efi"', $applyScript);
        self::assertStringContainsString('mount "${BOOT_PART}" "${TARGET_ROOT}/boot"', $applyScript);
        self::assertStringContainsString("CONSOLE_KEYMAP_VALUE='la-latin1'", $applyScript);
        self::assertStringContainsString('XKBMODEL="pc105"', $applyScript);
        self::assertStringContainsString('XKBLAYOUT="${KEYBOARD_VALUE}"', $applyScript);
        self::assertStringContainsString('KEYMAP=${CONSOLE_KEYMAP_VALUE}', $applyScript);
        self::assertStringContainsString('cat > "${TARGET_ROOT}/etc/vconsole.conf" <<EOF', $applyScript);
        self::assertStringContainsString('cat > "${TARGET_ROOT}/etc/initramfs-tools/conf.d/w4-keyboard" <<EOF', $applyScript);
        self::assertStringContainsString('chroot "${TARGET_ROOT}" setupcon --save-only', $applyScript);
        self::assertStringContainsString('setupcon devolvio un error; se conserva la configuracion escrita en /etc/default/keyboard', $applyScript);
        $verifyScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh');
        self::assertNotFalse($verifyScript);

        self::assertStringContainsString('PASSPHRASE_FILE="${W4_DISK_PASSPHRASE_FILE:-${SCRIPT_DIR}/runtime/disk-passphrase.txt}"', $verifyScript);
        self::assertStringContainsString('mount_target_if_needed() {', $verifyScript);
        self::assertStringContainsString('cryptsetup open "${ROOT_PART}" "${CRYPT_NAME}" --key-file "${PASSPHRASE_FILE}"', $verifyScript);
        self::assertStringContainsString('mount -o subvol="${ROOT_SUBVOLUME}" "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}"', $verifyScript);
        self::assertStringContainsString('Verificacion local completada para ${TARGET_ROOT}', $verifyScript);
        self::assertStringContainsString('mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"', $applyScript);
        $bootMkdirPosition = strpos($applyScript, 'mkdir -p "${TARGET_ROOT}/boot"');
        $bootMountPosition = strpos($applyScript, 'mount "${BOOT_PART}" "${TARGET_ROOT}/boot"');
        $efiMkdirPosition = strpos($applyScript, 'mkdir -p "${TARGET_ROOT}/boot/efi"');
        $efiMountPosition = strpos($applyScript, 'mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"');
        self::assertIsInt($bootMkdirPosition);
        self::assertIsInt($bootMountPosition);
        self::assertIsInt($efiMkdirPosition);
        self::assertIsInt($efiMountPosition);
        self::assertLessThan($bootMountPosition, $bootMkdirPosition);
        self::assertLessThan($efiMountPosition, $efiMkdirPosition);

        $bundleManifest = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertContains('apply-installation.sh', $bundleManifest['generated_artifacts']);
        self::assertContains('verify-installation.sh', $bundleManifest['generated_artifacts']);
        self::assertContains('installation-executor.json', $bundleManifest['generated_artifacts']);
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'INSTALLATION_EXECUTOR_README.txt');

        self::assertStringContainsString('falta /boot/grub/grub.cfg', $verifyScript);
        self::assertStringContainsString('falta la ruta UEFI de fallback BOOTX64.EFI', $verifyScript);
    }

    public function testGenerateInstallationExecutorUsesRootSubvolumeByMountpoint(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-root-subvolume';
        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle temporal');

        $plan = $this->decodeJsonFile($this->fixturePath('build/install/w4-os-home/installation-plan.json'));
        $plan['storage']['btrfs']['subvolumes'] = [
            ['name' => '@home', 'mountpoint' => '/home'],
            ['name' => '@', 'mountpoint' => '/'],
            ['name' => '@log', 'mountpoint' => '/var/log'],
            ['name' => '@cache', 'mountpoint' => '/var/cache'],
            ['name' => '@data', 'mountpoint' => '/var/lib/w4'],
        ];

        self::assertNotFalse(file_put_contents(
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json',
            json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
        ));

        copy(
            $this->fixturePath('build/install/w4-os-home/installation-bundle.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json'
        );

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_installation_executor.php'),
            [
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $verifyScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh');
        self::assertNotFalse($verifyScript);
        self::assertStringContainsString("ROOT_SUBVOLUME='@'", $verifyScript);
        self::assertStringNotContainsString("ROOT_SUBVOLUME='@home'", $verifyScript);
    }

    public function testGenerateInstallationExecutorRejectsScalarPlanJson(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-invalid-plan';
        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle temporal');

        self::assertNotFalse(file_put_contents($bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json', '"valor-escalar"' . PHP_EOL));
        copy(
            $this->fixturePath('build/install/w4-os-home/installation-bundle.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json'
        );

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_installation_executor.php'),
            [
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('la raiz debe ser un objeto o arreglo JSON', $result['stderr']);
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(string $scriptPath, array $arguments): array
    {
        $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, sprintf('No se pudo ejecutar %s', $scriptPath));

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer el archivo JSON %s', $path));

        return $this->decodeJson($raw);
    }

    private function fixturePath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
