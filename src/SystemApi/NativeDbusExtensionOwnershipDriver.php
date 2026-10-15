<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Throwable;

final class NativeDbusExtensionOwnershipDriver
{
    /**
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, string> $environment
     * @return array<string, mixed>
     */
    public function materializeOwnership(array $serviceDefinition, array $environment = []): array
    {
        $runtimeClassName = $this->resolveRuntimeClassName($serviceDefinition, $environment);
        if ($runtimeClassName === null || !class_exists($runtimeClassName)) {
            return $this->failedLifecycle(
                'native-dbus-extension-class-unavailable',
                [
                    'runtime_class_name' => $runtimeClassName,
                ]
            );
        }

        try {
            [$connection, $constructorMode] = $this->instantiateConnection($runtimeClassName);
        } catch (Throwable $exception) {
            return $this->failedLifecycle(
                'native-dbus-extension-constructor-failed',
                [
                    'runtime_class_name' => $runtimeClassName,
                    'error_message' => $exception->getMessage(),
                ]
            );
        }

        $driver = [
            'mode' => 'native-dbus-extension-driver',
            'driver_class' => self::class,
            'runtime_class_name' => $runtimeClassName,
            'constructor_mode' => $constructorMode,
        ];

        try {
            $requestNameResult = $this->invokeRequestName($connection, $serviceDefinition);
            if ($requestNameResult === false) {
                return $this->failedLifecycle(
                    'native-dbus-extension-request-name-failed',
                    $driver + ['request_name_result' => false]
                );
            }
            $driver['request_name_result'] = $this->normalizeResultValue($requestNameResult);
        } catch (Throwable $exception) {
            return $this->failedLifecycle(
                'native-dbus-extension-request-name-failed',
                $driver + ['error_message' => $exception->getMessage()]
            );
        }

        try {
            $exportedObject = new NativeDbusExtensionRuntimeObject($serviceDefinition);
            $registerObjectResult = $this->invokeRegisterObject($connection, $serviceDefinition, $exportedObject);
            if ($registerObjectResult === false) {
                return [
                    'request_name_state' => 'owned',
                    'register_object_state' => 'failed',
                    'wait_loop_state' => 'not-attempted',
                    'blocking_reason' => 'native-dbus-extension-register-object-failed',
                    'driver_status' => 'register-object-failed',
                    'driver' => $driver + [
                        'exported_object_class' => NativeDbusExtensionRuntimeObject::class,
                        'register_object_result' => false,
                    ],
                ];
            }
            $driver['exported_object_class'] = NativeDbusExtensionRuntimeObject::class;
            $driver['register_object_result'] = $this->normalizeResultValue($registerObjectResult);
        } catch (Throwable $exception) {
            return [
                'request_name_state' => 'owned',
                'register_object_state' => 'failed',
                'wait_loop_state' => 'not-attempted',
                'blocking_reason' => 'native-dbus-extension-register-object-failed',
                'driver_status' => 'register-object-failed',
                'driver' => $driver + ['error_message' => $exception->getMessage()],
            ];
        }

        try {
            $waitLoopResult = $this->invokeWaitLoop($connection);
            if ($waitLoopResult === false) {
                return [
                    'request_name_state' => 'owned',
                    'register_object_state' => 'registered',
                    'wait_loop_state' => 'failed',
                    'blocking_reason' => 'native-dbus-extension-wait-loop-failed',
                    'driver_status' => 'wait-loop-failed',
                    'driver' => $driver + ['wait_loop_result' => false],
                ];
            }
            $driver['wait_loop_result'] = $this->normalizeResultValue($waitLoopResult);
        } catch (Throwable $exception) {
            return [
                'request_name_state' => 'owned',
                'register_object_state' => 'registered',
                'wait_loop_state' => 'failed',
                'blocking_reason' => 'native-dbus-extension-wait-loop-failed',
                'driver_status' => 'wait-loop-failed',
                'driver' => $driver + ['error_message' => $exception->getMessage()],
            ];
        }

        return [
            'request_name_state' => 'owned',
            'register_object_state' => 'registered',
            'wait_loop_state' => 'running',
            'driver_status' => 'ownership-materialized',
            'driver' => $driver,
        ];
    }

    private function resolveRuntimeClassName(array $serviceDefinition, array $environment): ?string
    {
        $override = $environment['W4_SYSTEM_API_NATIVE_DBUS_CLASS'] ?? null;
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        $runtimeBackend = is_array($serviceDefinition['runtime_backend'] ?? null)
            ? $serviceDefinition['runtime_backend']
            : [];
        $nativeRuntimeApi = is_array($runtimeBackend['native_runtime_api'] ?? null)
            ? $runtimeBackend['native_runtime_api']
            : [];
        $className = $nativeRuntimeApi['class_name'] ?? null;

        return is_string($className) && trim($className) !== '' ? trim($className) : null;
    }

    /**
     * @return array{0:object,1:string}
     */
    private function instantiateConnection(string $runtimeClassName): array
    {
        $runtimeClass = new ReflectionClass($runtimeClassName);
        $constructor = $runtimeClass->getConstructor();
        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return [$runtimeClass->newInstance(), 'default-constructor'];
        }

        $busSystemConstant = defined($runtimeClassName . '::BUS_SYSTEM')
            ? constant($runtimeClassName . '::BUS_SYSTEM')
            : null;
        if ($busSystemConstant === null) {
            throw new RuntimeException('Dbus::BUS_SYSTEM no esta disponible para el driver nativo');
        }

        if ($constructor->getNumberOfRequiredParameters() === 1) {
            return [$runtimeClass->newInstance($busSystemConstant), 'bus-system-constructor'];
        }

        throw new RuntimeException('La firma del constructor D-Bus no es compatible con el driver nativo');
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    private function invokeRequestName(object $connection, array $serviceDefinition): mixed
    {
        $method = new ReflectionMethod($connection, 'requestName');
        $arguments = [];

        foreach ($method->getParameters() as $index => $parameter) {
            if ($index === 0) {
                $arguments[] = $serviceDefinition['bus_name'] ?? 'org.w4.System1';
                continue;
            }

            $arguments[] = $this->defaultValueForParameter($parameter, 0);
        }

        return $method->invokeArgs($connection, $arguments);
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    private function invokeRegisterObject(
        object $connection,
        array $serviceDefinition,
        NativeDbusExtensionRuntimeObject $exportedObject
    ): mixed {
        $method = new ReflectionMethod($connection, 'registerObject');
        $arguments = [];

        foreach ($method->getParameters() as $index => $parameter) {
            if ($index === 0) {
                $arguments[] = $serviceDefinition['object_path'] ?? '/org/w4/System1';
                continue;
            }

            $arguments[] = $this->resolveRegisterObjectParameterValue($parameter, $serviceDefinition, $exportedObject);
        }

        return $method->invokeArgs($connection, $arguments);
    }

    private function invokeWaitLoop(object $connection): mixed
    {
        $method = new ReflectionMethod($connection, 'waitLoop');

        return $method->invoke($connection);
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    private function resolveRegisterObjectParameterValue(
        ReflectionParameter $parameter,
        array $serviceDefinition,
        NativeDbusExtensionRuntimeObject $exportedObject
    ): mixed {
        $parameterType = $parameter->getType();
        if ($parameterType instanceof ReflectionNamedType && !$parameterType->isBuiltin()) {
            return $exportedObject;
        }

        $parameterName = strtolower($parameter->getName());
        if (str_contains($parameterName, 'interface')) {
            return $serviceDefinition['interface'] ?? 'org.w4.System1.Manager';
        }

        if (
            str_contains($parameterName, 'object')
            || str_contains($parameterName, 'export')
            || str_contains($parameterName, 'handler')
            || str_contains($parameterName, 'service')
        ) {
            return $exportedObject;
        }

        return $this->defaultValueForParameter($parameter, $exportedObject);
    }

    private function defaultValueForParameter(ReflectionParameter $parameter, mixed $fallback): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $parameterType = $parameter->getType();
        if (!$parameterType instanceof ReflectionNamedType) {
            return $fallback;
        }

        if ($parameterType->allowsNull()) {
            return null;
        }

        return match ($parameterType->getName()) {
            'int' => 0,
            'float' => 0.0,
            'string' => '',
            'bool' => false,
            'array' => [],
            default => $fallback,
        };
    }

    /**
     * @param array<string, mixed> $driver
     * @return array<string, mixed>
     */
    private function failedLifecycle(string $blockingReason, array $driver): array
    {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => $blockingReason,
            'driver_status' => 'ownership-failed',
            'driver' => $driver + [
                'mode' => 'native-dbus-extension-driver',
                'driver_class' => self::class,
            ],
        ];
    }

    private function normalizeResultValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_object($value)) {
            return get_class($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        return gettype($value);
    }
}
