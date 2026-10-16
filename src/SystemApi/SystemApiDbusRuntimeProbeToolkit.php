<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class SystemApiDbusRuntimeProbeToolkit
{
    /**
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createReport(?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();

        $hasDbusExtension = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION')
            ?? (extension_loaded('dbus') || extension_loaded('php_dbus') || extension_loaded('php-dbus'));

        $hasBusctl = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_BUSCTL')
            ?? $this->commandLooksAvailable('busctl');

        $hasGdbus = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_GDBUS')
            ?? $this->commandLooksAvailable('gdbus');

        $hasDbusDaemon = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON')
            ?? $this->commandLooksAvailable('dbus-daemon');

        $hasPecl = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_PECL')
            ?? $this->commandLooksAvailable('pecl');

        $hasSystemBusSocket = $this->resolveBoolOverride($env, 'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET')
            ?? $this->resolveSystemBusSocketPresence($env);

        $phpBinary = $env['W4_SYSTEM_API_PROBE_PHP_BINARY'] ?? PHP_BINARY;
        $phpVersion = $env['W4_SYSTEM_API_PROBE_PHP_VERSION'] ?? PHP_VERSION;
        $osFamily = $env['W4_SYSTEM_API_PROBE_OS_FAMILY'] ?? PHP_OS_FAMILY;
        $nativeDbusExtensionInstallation = $this->resolveNativeDbusExtensionInstallation($env, $hasPecl, $hasDbusExtension);

        $missingCapabilities = [];
        if (!$hasDbusExtension) {
            $missingCapabilities[] = 'php-dbus-runtime-adapter';
        }

        if (!$hasDbusDaemon) {
            $missingCapabilities[] = 'dbus-daemon';
        }

        if (!$hasSystemBusSocket) {
            $missingCapabilities[] = 'system-bus-socket';
        }

        if (!$hasBusctl && !$hasGdbus) {
            $missingCapabilities[] = 'dbus-client-tooling';
        }

        $ready = $missingCapabilities === [];

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-diagnosis',
            'ready' => $ready,
            'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'environment' => [
                'os_family' => $osFamily,
                'php_binary' => $phpBinary,
                'php_version' => $phpVersion,
            ],
            'probes' => [
                'php_dbus_extension' => $hasDbusExtension,
                'pecl' => $hasPecl,
                'busctl' => $hasBusctl,
                'gdbus' => $hasGdbus,
                'dbus_daemon' => $hasDbusDaemon,
                'system_bus_socket' => $hasSystemBusSocket,
            ],
            'native_dbus_extension_installation' => $nativeDbusExtensionInstallation,
            'missing_capabilities' => $missingCapabilities,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function captureEnvironment(): array
    {
        $environment = getenv();
        if (!is_array($environment)) {
            return [];
        }

        $captured = [];
        foreach ($environment as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $captured[$key] = $value;
            }
        }

        return $captured;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveSystemBusSocketPresence(array $environment): bool
    {
        $override = $this->resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET');
        if ($override !== null) {
            return $override;
        }

        $socketPath = $environment['W4_SYSTEM_API_PROBE_SYSTEM_BUS_SOCKET'] ?? '/run/dbus/system_bus_socket';

        return PHP_OS_FAMILY !== 'Windows' && is_string($socketPath) && $socketPath !== '' && file_exists($socketPath);
    }

    private function commandLooksAvailable(string $command): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $resolved = shell_exec(sprintf('where %s 2>NUL', escapeshellarg($command)));
        } else {
            $resolved = shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($command)));
        }

        return is_string($resolved) && trim($resolved) !== '';
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveBoolOverride(array $environment, string $key): ?bool
    {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    /**
     * @param array<string, string> $environment
     * @return array<string, mixed>
     */
    private function resolveNativeDbusExtensionInstallation(
        array $environment,
        bool $hasPecl,
        bool $extensionLoaded
    ): array {
        $explicitState = $this->resolveOptionalEnumOverride(
            $environment,
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_STATE',
            ['unknown', 'not-attempted', 'buildable', 'build-failed', 'unsupported-stack', 'installed']
        );
        $explicitBuildPhase = $this->resolveOptionalEnumOverride(
            $environment,
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_PHASE',
            ['unknown', 'pecl-download', 'phpize', 'configure', 'make']
        );
        $explicitReason = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_REASON');
        $explicitLogPath = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_LOG_PATH');
        $inferredEvidence = $this->inferNativeDbusExtensionInstallationFromStructuredEvidence($environment);
        if ($inferredEvidence === []) {
            $inferredEvidence = $this->inferNativeDbusExtensionInstallationFromEvidence($environment);
        }
        $state = $explicitState
            ?? (is_string($inferredEvidence['state'] ?? null) ? $inferredEvidence['state'] : null)
            ?? ($extensionLoaded ? 'installed' : 'unknown');
        $reason = $explicitReason
            ?? (is_string($inferredEvidence['reason'] ?? null) ? $inferredEvidence['reason'] : null);
        $logPath = $explicitLogPath
            ?? (is_string($inferredEvidence['log_path'] ?? null) ? $inferredEvidence['log_path'] : null);
        $inferredBuildPhase = is_string($inferredEvidence['build_phase'] ?? null)
            && trim((string) $inferredEvidence['build_phase']) !== ''
            ? trim((string) $inferredEvidence['build_phase'])
            : null;
        $buildPhase = $explicitBuildPhase
            ?? $inferredBuildPhase
            ?? $this->inferNativeDbusBuildPhaseFromReason($reason, $state)
            ?? 'unknown';

        $blockingReason = match ($state) {
            'build-failed' => 'native-dbus-extension-build-failed',
            'unsupported-stack' => 'native-dbus-extension-unsupported-stack',
            default => null,
        };

        $nextAction = match ($state) {
            'build-failed' => $this->resolveNativeDbusBuildFailureAction($buildPhase),
            'unsupported-stack' => 'migrate-to-modern-linux-host',
            'buildable', 'not-attempted', 'unknown' => $extensionLoaded ? 'observe-runtime-backend' : 'install-native-dbus-extension',
            'installed' => 'observe-runtime-backend',
            default => $extensionLoaded ? 'observe-runtime-backend' : 'install-native-dbus-extension',
        };
        $peclAvailableForInstallation = $hasPecl
            || in_array($state, ['buildable', 'build-failed', 'unsupported-stack'], true);

        return [
            'state' => $state,
            'pecl_available' => $peclAvailableForInstallation,
            'extension_loaded' => $extensionLoaded,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
            'build_phase' => $buildPhase,
            'reason' => $reason,
            'log_path' => $logPath,
        ];
    }

    /**
     * @param array<string, string> $environment
     * @param list<string> $allowedValues
     */
    private function resolveOptionalEnumOverride(array $environment, string $key, array $allowedValues): ?string
    {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowedValues, true) ? $normalized : null;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveStringOverride(array $environment, string $key): ?string
    {
        $value = $environment[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, string> $environment
     * @return array<string, string>
     */
    private function inferNativeDbusExtensionInstallationFromStructuredEvidence(array $environment): array
    {
        $evidencePath = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_JSON_PATH');
        if ($evidencePath === null || !is_file($evidencePath)) {
            return [];
        }

        $raw = file_get_contents($evidencePath);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $state = is_string($payload['state'] ?? null) ? trim((string) $payload['state']) : null;
        if ($state === null || $state === '') {
            return [];
        }

        return [
            'state' => $state,
            'build_phase' => is_string($payload['build_phase'] ?? null) && trim((string) $payload['build_phase']) !== ''
                ? trim((string) $payload['build_phase'])
                : '',
            'reason' => is_string($payload['reason'] ?? null) ? trim((string) $payload['reason']) : '',
            'log_path' => is_string($payload['log_path'] ?? null)
                ? trim((string) $payload['log_path'])
                : $evidencePath,
        ];
    }

    /**
     * @param array<string, string> $environment
     * @return array<string, string>
     */
    private function inferNativeDbusExtensionInstallationFromEvidence(array $environment): array
    {
        $evidencePath = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_EVIDENCE_PATH');
        if ($evidencePath === null || !is_file($evidencePath)) {
            return [];
        }

        $raw = file_get_contents($evidencePath);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $normalized = strtolower($raw);
        $reason = null;
        $state = null;
        $buildPhase = 'unknown';

        if (
            str_contains($normalized, 'failed to download pecl/dbus')
            || str_contains($normalized, 'latest release is beta')
            || str_contains($normalized, 'no releases available for package')
        ) {
            $state = 'build-failed';
            $buildPhase = 'pecl-download';
            $reason = str_contains($normalized, 'latest release is beta')
                ? 'pecl-release-is-beta'
                : 'native-dbus-extension-download-failed';
        } elseif (
            str_contains($normalized, 'configure: error')
            || str_contains($normalized, 'cannot find xml2-config')
            || str_contains($normalized, 'libxml-2.0')
            || str_contains($normalized, 'phpize failed')
        ) {
            $state = 'build-failed';
            $buildPhase = str_contains($normalized, 'phpize failed') ? 'phpize' : 'configure';
            $reason = str_contains($normalized, 'libxml-2.0')
                ? 'libxml-2.0'
                : (str_contains($normalized, 'cannot find xml2-config')
                    ? 'xml2-config-missing'
                    : 'native-dbus-extension-configure-failed');
        } elseif (
            str_contains($normalized, 'zend_object_store_get_object')
            || str_contains($normalized, 'make: ***')
            || str_contains($normalized, 'compilacion sigue fallando')
            || str_contains($normalized, 'no compila')
        ) {
            $state = 'build-failed';
            $buildPhase = 'make';
            $reason = str_contains($normalized, 'zend_object_store_get_object')
                ? 'zend_object_store_get_object'
                : (str_contains($normalized, 'make: *** [makefile:192: dbus.lo] error 1')
                    ? 'make: *** [Makefile:192: dbus.lo] Error 1'
                    : 'native-dbus-extension-build-failed');
        } elseif (
            str_contains($normalized, 'unsupported stack')
            || str_contains($normalized, 'stack no soportado')
        ) {
            $state = 'unsupported-stack';
            $reason = 'native-dbus-extension-unsupported-stack';
        }

        if ($state === null) {
            return [];
        }

        return [
            'state' => $state,
            'build_phase' => $buildPhase,
            'reason' => $reason ?? '',
            'log_path' => $evidencePath,
        ];
    }

    private function inferNativeDbusBuildPhaseFromReason(?string $reason, string $state): ?string
    {
        if ($state !== 'build-failed' || $reason === null || trim($reason) === '') {
            return null;
        }

        $normalized = strtolower(trim($reason));

        if (
            str_contains($normalized, 'pecl-release-is-beta')
            || str_contains($normalized, 'download')
        ) {
            return 'pecl-download';
        }

        if (
            str_contains($normalized, 'libxml-2.0')
            || str_contains($normalized, 'xml2-config')
            || str_contains($normalized, 'configure')
        ) {
            return 'configure';
        }

        if (
            str_contains($normalized, 'phpize')
        ) {
            return 'phpize';
        }

        if (
            str_contains($normalized, 'zend_object_store_get_object')
            || str_contains($normalized, 'make:')
            || str_contains($normalized, 'dbus.lo')
            || str_contains($normalized, 'build-failed')
        ) {
            return 'make';
        }

        return null;
    }

    private function resolveNativeDbusBuildFailureAction(string $buildPhase): string
    {
        return match ($buildPhase) {
            'pecl-download' => 'inspect-native-dbus-pecl-download',
            'phpize', 'configure' => 'inspect-native-dbus-build-configure',
            'make' => 'inspect-native-dbus-build-make',
            default => 'inspect-native-dbus-build-stack',
        };
    }
}
