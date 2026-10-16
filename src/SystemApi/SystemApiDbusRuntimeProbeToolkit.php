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
        $state = $this->resolveEnumOverride(
            $environment,
            'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_STATE',
            ['unknown', 'not-attempted', 'buildable', 'build-failed', 'unsupported-stack', 'installed'],
            $extensionLoaded ? 'installed' : 'unknown'
        );
        $reason = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_REASON');
        $logPath = $this->resolveStringOverride($environment, 'W4_SYSTEM_API_PROBE_DBUS_EXTENSION_BUILD_LOG_PATH');

        $blockingReason = match ($state) {
            'build-failed' => 'native-dbus-extension-build-failed',
            'unsupported-stack' => 'native-dbus-extension-unsupported-stack',
            default => null,
        };

        $nextAction = match ($state) {
            'build-failed' => 'inspect-native-dbus-build-stack',
            'unsupported-stack' => 'migrate-to-modern-linux-host',
            'buildable', 'not-attempted', 'unknown' => $extensionLoaded ? 'observe-runtime-backend' : 'install-native-dbus-extension',
            'installed' => 'observe-runtime-backend',
            default => $extensionLoaded ? 'observe-runtime-backend' : 'install-native-dbus-extension',
        };

        return [
            'state' => $state,
            'pecl_available' => $hasPecl,
            'extension_loaded' => $extensionLoaded,
            'blocking_reason' => $blockingReason,
            'next_action' => $nextAction,
            'reason' => $reason,
            'log_path' => $logPath,
        ];
    }

    /**
     * @param array<string, string> $environment
     * @param list<string> $allowedValues
     */
    private function resolveEnumOverride(array $environment, string $key, array $allowedValues, string $default): string
    {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowedValues, true) ? $normalized : $default;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveStringOverride(array $environment, string $key): ?string
    {
        $value = $environment[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
