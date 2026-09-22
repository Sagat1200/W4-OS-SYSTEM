<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class UpdateScriptsIntegrationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-update-cli-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateUpdatePlanCreatesPlanInCustomPath(): void
    {
        $outputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'home-lab.update-plan.json';

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-001',
                '--output',
                $outputPath,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($outputPath, $payload['output']);
        self::assertSame('w4-update-cli-001', $payload['operation_id']);

        $plan = $this->decodeJsonFile($outputPath);
        self::assertSame('update-plan', $plan['kind']);
        self::assertSame('w4-update-cli-001', $plan['operation_id']);
        self::assertSame('pending_health', $plan['health_check_manifest']['pending_state']);
    }

    public function testGenerateUpdatePlanRejectsScalarJsonAsValidationError(): void
    {
        $requestPath = $this->tempDir . DIRECTORY_SEPARATOR . 'invalid-scalar.update-request.json';
        self::assertNotFalse(file_put_contents($requestPath, '"valor-escalar"' . PHP_EOL));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $requestPath,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('ERROR:', $result['stderr']);
        self::assertStringContainsString('la raiz debe ser un objeto o arreglo JSON', $result['stderr']);
    }

    public function testPrepareUpdateOperationCreatesDurableStore(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared.update-plan.json';
        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'operation-store';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-002',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $prepare = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_update_operation.php'),
            [
                '--plan',
                $planPath,
                '--store-dir',
                $storeDir,
            ]
        );

        self::assertSame(0, $prepare['exitCode'], $prepare['stderr']);

        $payload = $this->decodeJson($prepare['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($storeDir, $payload['store_dir']);
        self::assertSame('w4-update-cli-002', $payload['operation_id']);
        self::assertSame('planned', $payload['stage']);
        self::assertSame('pre-update-w4-update-cli-002', $payload['snapshot_name']);

        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'update-plan.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'events.ndjson');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');

        $healthReport = $this->decodeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');
        self::assertSame('planned', $healthReport['stage']);
        self::assertSame('pending', $healthReport['status']);
    }

    public function testAdvanceAndReconcileUpdateOperationFlow(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'advance.update-plan.json';
        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'advance-store';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-003',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $prepare = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_update_operation.php'),
            [
                '--plan',
                $planPath,
                '--store-dir',
                $storeDir,
            ]
        );
        self::assertSame(0, $prepare['exitCode'], $prepare['stderr']);

        foreach (['downloading', 'ready', 'prepared', 'applying_offline'] as $stage) {
            $advance = $this->runPhpScript(
                $this->fixturePath('scripts/advance_update_operation.php'),
                [
                    '--store-dir',
                    $storeDir,
                    '--stage',
                    $stage,
                    '--component',
                    'phpunit-cli',
                    '--detail',
                    'stage=' . $stage,
                ]
            );
            self::assertSame(0, $advance['exitCode'], $advance['stderr']);
        }

        $pendingHealth = $this->runPhpScript(
            $this->fixturePath('scripts/reconcile_update_operation.php'),
            [
                '--store-dir',
                $storeDir,
                '--observed-stage',
                'pending_health',
                '--component',
                'phpunit-reconcile',
                '--detail',
                'boot=first',
            ]
        );
        self::assertSame(0, $pendingHealth['exitCode'], $pendingHealth['stderr']);
        $pendingPayload = $this->decodeJson($pendingHealth['stdout']);
        self::assertTrue($pendingPayload['reconciled']);
        self::assertSame('pending_health', $pendingPayload['stage']);

        $confirmed = $this->runPhpScript(
            $this->fixturePath('scripts/reconcile_update_operation.php'),
            [
                '--store-dir',
                $storeDir,
                '--observed-stage',
                'confirmed',
                '--component',
                'phpunit-reconcile',
                '--detail',
                'boot=second',
            ]
        );
        self::assertSame(0, $confirmed['exitCode'], $confirmed['stderr']);
        $confirmedPayload = $this->decodeJson($confirmed['stdout']);
        self::assertSame('confirmed', $confirmedPayload['stage']);
        self::assertSame('ok', $confirmedPayload['health_status']);
    }

    public function testGenerateUpdateExecutorWritesCoordinatorScripts(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'executor.update-plan.json';
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-bundle';

        $generatePlan = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-004',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generatePlan['exitCode'], $generatePlan['stderr']);

        $generateExecutor = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_executor.php'),
            [
                '--plan',
                $planPath,
                '--bundle-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateExecutor['exitCode'], $generateExecutor['stderr']);

        $payload = $this->decodeJson($generateExecutor['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundleDir, $payload['bundle_dir']);

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'run-update-with-repo-env.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'update-executor.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'UPDATE_EXECUTOR_README.txt');

        $offlineScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh');
        self::assertNotFalse($offlineScript);
        self::assertStringContainsString('advance_update_operation.php', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_ENGINE_ROOT', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_EXECUTE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APPLY_MODE:-live-apt', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APT_CHECK_DATE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE_DISTS', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_FILE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_FAIL_STAGE', $offlineScript);
        self::assertStringContainsString('pending_health', $offlineScript);
        self::assertStringContainsString('snapshot-manifest.json', $offlineScript);
        self::assertStringContainsString('--print-number', $offlineScript);
        self::assertStringContainsString('planned_snapshot_path', $offlineScript);
        self::assertStringContainsString('snapshot_backend', $offlineScript);
        self::assertStringContainsString('snapshot_reference', $offlineScript);
        self::assertStringContainsString('trap \'handle_error $? $LINENO\' ERR', $offlineScript);
        self::assertStringContainsString('--stage failed', $offlineScript);

        $repoLauncherScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'run-update-with-repo-env.sh');
        self::assertNotFalse($repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_REPOSITORY_DIR', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_REPOSITORY_ENV_FILE', $repoLauncherScript);
        self::assertStringContainsString('W4_REPOSITORY_CHANNEL', $repoLauncherScript);
        self::assertStringContainsString('run-update-offline.sh', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE_DEFAULT', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_APT_CHECK_DATE', $repoLauncherScript);

        $healthScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh');
        self::assertNotFalse($healthScript);
        self::assertStringContainsString('dpkg --audit', $healthScript);
        self::assertStringContainsString('health-check-results.json', $healthScript);

        $reconcileScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh');
        self::assertNotFalse($reconcileScript);
        self::assertStringContainsString('reconcile_update_operation.php', $reconcileScript);
        self::assertStringContainsString('W4_UPDATE_OBSERVED_STAGE', $reconcileScript);
        self::assertStringContainsString('run-health-checks.sh', $reconcileScript);

        $readme = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'UPDATE_EXECUTOR_README.txt');
        self::assertNotFalse($readme);
        self::assertStringContainsString('run-update-with-repo-env.sh', $readme);
        self::assertStringContainsString('W4_UPDATE_REPOSITORY_DIR', $readme);
    }

    public function testGenerateUpdateRepositoryBundleWritesLabRepoArtifacts(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-bundle';

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_repository_bundle.php'),
            [
                '--snapshot-id',
                'w4-main-2026-09-20T120000Z',
                '--channel',
                'testing',
                '--target-version',
                '1.0.1-lab',
                '--package-set',
                'both',
                '--output-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundleDir, $payload['bundle_dir']);
        self::assertSame('w4-main-2026-09-20T120000Z', $payload['snapshot_id']);
        self::assertSame('testing', $payload['channel']);
        self::assertSame(5, $payload['package_count']);

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'build-repo.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'repository-manifest.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'apt-source.list.template');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'apt-source.dists.list.template');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'apt-source.signed.list.template');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'REPOSITORY_BUNDLE_README.txt');

        $manifest = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'repository-manifest.json');
        self::assertSame('update-repository-bundle', $manifest['kind']);
        self::assertSame('w4-main-2026-09-20T120000Z', $manifest['repository_snapshot']['id']);
        self::assertSame('testing', $manifest['repository_snapshot']['channel']);
        self::assertSame('1.0.1-lab', $manifest['target_version']);
        self::assertSame('both', $manifest['package_set']);
        self::assertSame('derived-from-manifests', $manifest['package_strategy']);
        self::assertSame('w4-linux-base', $manifest['source_profiles']['base']);
        self::assertCount(5, $manifest['packages']);

        $buildScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'build-repo.sh');
        self::assertNotFalse($buildScript);
        self::assertStringContainsString('dpkg-deb --build', $buildScript);
        self::assertStringContainsString('dpkg-scanpackages --multiversion', $buildScript);
        self::assertStringContainsString('w4-base-meta', $buildScript);
        self::assertStringContainsString('w4-desktop-meta', $buildScript);
        self::assertStringContainsString('w4-home-meta', $buildScript);
        self::assertStringContainsString('w4-business-meta', $buildScript);
        self::assertStringContainsString('w4-recovery-tools', $buildScript);
        self::assertStringContainsString('btrfs-progs', $buildScript);
        self::assertStringContainsString('package-sources.json', $buildScript);
        self::assertStringContainsString('dists/${CHANNEL}/main/binary-amd64', $buildScript);
        self::assertStringContainsString('dists/${CHANNEL}/Release', $buildScript);
        self::assertStringContainsString('InRelease', $buildScript);
        self::assertStringContainsString('Release.gpg', $buildScript);
        self::assertStringContainsString('apt-source.dists.list.template', $buildScript);
        self::assertStringContainsString('apt-source.signed.list.template', $buildScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"', $buildScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL', $buildScript);
        self::assertStringContainsString('W4_UPDATE_REPO_SIGNING_MODE', $buildScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE_SIGNED_TEMPLATE', $buildScript);
        self::assertStringContainsString('deb [trusted=yes] file:__W4_REPO_ROOT__ ./', $buildScript);
        self::assertStringContainsString('deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main', $buildScript);
        self::assertStringContainsString('deb [signed-by=__W4_REPO_ROOT__/keyrings/${SIGNING_KEYRING_NAME}] file:__W4_REPO_ROOT__ ${CHANNEL} main', $buildScript);

        $signedAptSource = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'apt-source.signed.list.template');
        self::assertNotFalse($signedAptSource);
        self::assertStringContainsString('deb [signed-by=__W4_REPO_ROOT__/keyrings/w4-update-archive-keyring.gpg] file:__W4_REPO_ROOT__ testing main', $signedAptSource);
    }

    public function testSignedRepositoryRunnerSupportsCheckOnlyPreview(): void
    {
        $distribution = $this->detectWslDistribution();
        if ($distribution === null) {
            self::markTestSkipped('No hay una distribucion WSL disponible para validar el preview del runner firmado.');
        }

        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-bundle-signed-preview';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-output-signed-preview';

        $generateBundle = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_repository_bundle.php'),
            [
                '--snapshot-id',
                'w4-main-2026-09-20T180000Z',
                '--channel',
                'testing',
                '--target-version',
                '1.0.1-lab',
                '--package-set',
                'both',
                '--output-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateBundle['exitCode'], $generateBundle['stderr']);

        $preview = $this->runPhpScript(
            $this->fixturePath('scripts/run_update_repository_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundleDir,
                '--output-dir',
                $outputDir,
                '--distribution',
                $distribution,
                '--signing-mode',
                'gpg',
                '--gpg-key-id',
                'W4-Update-Lab',
                '--generate-lab-key',
                '--check-only',
            ]
        );
        self::assertSame(0, $preview['exitCode'], $preview['stderr']);

        $payload = $this->decodeJson($preview['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('gpg', $payload['signing_mode']);
        self::assertSame('W4-Update-Lab', $payload['gpg_key_id']);
        self::assertTrue($payload['generate_lab_key']);
        self::assertSame($distribution, $payload['distribution']);
        self::assertStringContainsString('export W4_UPDATE_REPO_SIGNING_MODE=gpg', $payload['run_command']);
        self::assertStringContainsString('export W4_UPDATE_REPO_GPG_KEY_ID=', $payload['run_command']);
        self::assertStringContainsString('quick-generate-key', $payload['run_command']);
        self::assertStringContainsString('bash ', $payload['run_command']);
    }

    public function testSignedRepositoryRunnerSupportsPersistentKeyImportPreview(): void
    {
        $distribution = $this->detectWslDistribution();
        if ($distribution === null) {
            self::markTestSkipped('No hay una distribucion WSL disponible para validar el preview de importacion GPG persistente.');
        }

        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-bundle-signed-import-preview';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-output-signed-import-preview';
        $secretKeyFile = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-update-prod-secret.asc';
        $ownertrustFile = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-update-prod-ownertrust.txt';

        self::assertNotFalse(file_put_contents($secretKeyFile, "-----BEGIN PGP PRIVATE KEY BLOCK-----\nplaceholder\n"));
        self::assertNotFalse(file_put_contents($ownertrustFile, "placeholder:6:\n"));

        $generateBundle = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_repository_bundle.php'),
            [
                '--snapshot-id',
                'w4-main-2026-09-21T120000Z',
                '--channel',
                'testing',
                '--target-version',
                '1.0.1-prod-preview',
                '--package-set',
                'both',
                '--output-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateBundle['exitCode'], $generateBundle['stderr']);

        $preview = $this->runPhpScript(
            $this->fixturePath('scripts/run_update_repository_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundleDir,
                '--output-dir',
                $outputDir,
                '--distribution',
                $distribution,
                '--signing-mode',
                'gpg',
                '--gpg-key-id',
                'W4-Update-Prod',
                '--gpg-secret-key-file',
                $secretKeyFile,
                '--gpg-ownertrust-file',
                $ownertrustFile,
                '--check-only',
            ]
        );
        self::assertSame(0, $preview['exitCode'], $preview['stderr']);

        $payload = $this->decodeJson($preview['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('gpg', $payload['signing_mode']);
        self::assertSame('W4-Update-Prod', $payload['gpg_key_id']);
        self::assertFalse($payload['generate_lab_key']);
        self::assertSame($distribution, $payload['distribution']);
        self::assertStringContainsString('signing-imported', (string) $payload['gpg_homedir_wsl']);
        self::assertNotEmpty($payload['gpg_secret_key_file_wsl']);
        self::assertNotEmpty($payload['gpg_ownertrust_file_wsl']);
        self::assertStringContainsString(' --import ', $payload['run_command']);
        self::assertStringContainsString('--import-ownertrust', $payload['run_command']);
        self::assertStringContainsString('export W4_UPDATE_REPO_GPG_HOMEDIR=', $payload['run_command']);
        self::assertStringContainsString('bash ', $payload['run_command']);
    }

    public function testSignedRepositoryRunnerSupportsProductionProfilePreview(): void
    {
        $distribution = $this->detectWslDistribution();
        if ($distribution === null) {
            self::markTestSkipped('No hay una distribucion WSL disponible para validar el perfil productivo del runner firmado.');
        }

        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-bundle-signed-prod-profile';

        $generateBundle = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_repository_bundle.php'),
            [
                '--snapshot-id',
                'w4-main-2026-09-21T180000Z',
                '--channel',
                'testing',
                '--target-version',
                '1.0.1-prod',
                '--package-set',
                'both',
                '--output-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateBundle['exitCode'], $generateBundle['stderr']);

        $preview = $this->runPhpScript(
            $this->fixturePath('scripts/run_update_repository_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundleDir,
                '--distribution',
                $distribution,
                '--signing-profile',
                'prod',
                '--check-only',
            ]
        );
        self::assertSame(0, $preview['exitCode'], $preview['stderr']);

        $payload = $this->decodeJson($preview['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('prod', $payload['signing_profile']);
        self::assertSame('gpg', $payload['signing_mode']);
        self::assertSame('W4-Update-Prod', $payload['gpg_key_id']);
        self::assertSame('/var/tmp/w4-os-system/signing-w4', $payload['gpg_homedir_wsl']);
        self::assertFalse($payload['generate_lab_key']);
        self::assertSame($distribution, $payload['distribution']);
        self::assertStringEndsWith('-signed-prod', basename((string) $payload['output_dir_windows']));
        self::assertStringContainsString('export W4_UPDATE_REPO_SIGNING_MODE=gpg', $payload['run_command']);
        self::assertStringContainsString('export W4_UPDATE_REPO_GPG_KEY_ID=', $payload['run_command']);
        self::assertStringContainsString('export W4_UPDATE_REPO_GPG_HOMEDIR=', $payload['run_command']);
    }

    public function testOfficialRepositoryPublisherSupportsProductionPreview(): void
    {
        $distribution = $this->detectWslDistribution();
        if ($distribution === null) {
            self::markTestSkipped('No hay una distribucion WSL disponible para validar el publisher oficial del repo firmado.');
        }

        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'repo-bundle-official-prod';

        $generateBundle = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_repository_bundle.php'),
            [
                '--snapshot-id',
                'w4-main-2026-09-21T210000Z',
                '--channel',
                'testing',
                '--target-version',
                '1.0.2-prod',
                '--package-set',
                'both',
                '--output-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateBundle['exitCode'], $generateBundle['stderr']);

        $preview = $this->runPhpScript(
            $this->fixturePath('scripts/publish_update_repository.php'),
            [
                '--bundle',
                $bundleDir,
                '--distribution',
                $distribution,
                '--check-only',
            ]
        );
        self::assertSame(0, $preview['exitCode'], $preview['stderr']);

        $payload = $this->decodeJson($preview['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('official-prod', $payload['publication_profile']);
        self::assertSame($distribution, $payload['distribution']);
        self::assertSame('w4-main-2026-09-21T210000Z', $payload['snapshot_id']);
        self::assertSame('testing', $payload['channel']);
        self::assertContains('repo.env', $payload['verification_targets']);
        self::assertContains('dists/testing/InRelease', $payload['verification_targets']);
        self::assertContains('keyrings/w4-update-archive-keyring.gpg', $payload['verification_targets']);
        self::assertSame('prod', $payload['runner_preview']['signing_profile']);
        self::assertSame('gpg', $payload['runner_preview']['signing_mode']);
        self::assertStringEndsWith('-signed-prod', basename((string) $payload['output_dir']));
    }

    public function testUpdateValidationHelperHelpMentionsUnlockRetryOptions(): void
    {
        $result = $this->runPhpScript(
            $this->fixturePath('scripts/run_update_validation_via_paramiko.php'),
            ['--help']
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('--unlock-retry-interval', $result['stdout']);
        self::assertStringContainsString('--unlock-retries', $result['stdout']);
        self::assertStringContainsString('passphrase LUKS', $result['stdout']);
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

    private function detectWslDistribution(): ?string
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return null;
        }

        $result = $this->runCommand(['wsl', '-l', '-q']);
        if ($result['exitCode'] !== 0) {
            return null;
        }

        $lines = preg_split('/\r?\n/', $result['stdout']) ?: [];
        $distros = array_values(array_filter(array_map(static fn (string $line): string => trim(str_replace("\0", '', $line)), $lines)));
        if ($distros === []) {
            return null;
        }

        foreach ($distros as $distro) {
            if (strcasecmp($distro, 'Ubuntu') === 0) {
                return $distro;
            }
        }

        return $distros[0];
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runCommand(array $command): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, sprintf('No se pudo ejecutar el comando %s', implode(' ', $command)));

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
