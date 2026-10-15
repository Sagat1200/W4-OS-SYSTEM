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
            $requestNameSignature = $this->createRequestNameSignatureReport($connection);
            $requestNameInvocationPlan = $this->createRequestNameInvocationPlan($connection, $serviceDefinition);
            $requestNameResult = $this->invokeRequestName($connection, $requestNameInvocationPlan['arguments']);
            if ($requestNameResult === false) {
                return $this->failedLifecycle(
                    'native-dbus-extension-request-name-failed',
                    $driver + [
                        'request_name_signature' => $requestNameSignature,
                        'request_name_invocation_plan' => $requestNameInvocationPlan['report'],
                        'request_name_result' => false,
                    ]
                );
            }
            $driver['request_name_signature'] = $requestNameSignature;
            $driver['request_name_invocation_plan'] = $requestNameInvocationPlan['report'];
            $driver['request_name_result'] = $this->normalizeResultValue($requestNameResult);
        } catch (Throwable $exception) {
            return $this->failedLifecycle(
                'native-dbus-extension-request-name-failed',
                $driver + [
                    'request_name_signature' => $requestNameSignature ?? null,
                    'request_name_invocation_plan' => $requestNameInvocationPlan['report'] ?? null,
                    'error_message' => $exception->getMessage(),
                ]
            );
        }

        try {
            $exportedObject = new NativeDbusExtensionRuntimeObject(
                $serviceDefinition,
                $this->resolveRuntimeRootDir($environment)
            );
            $registerObjectHandoff = $exportedObject->createRegisterObjectHandoffPayload();
            $registerObjectSignature = $this->createRegisterObjectSignatureReport($connection, $registerObjectHandoff);
            $registerObjectInvocationPlan = $this->createRegisterObjectInvocationPlan(
                $connection,
                $serviceDefinition,
                $exportedObject,
                $registerObjectHandoff
            );
            $registerObjectResult = $this->invokeRegisterObject($connection, $registerObjectInvocationPlan['arguments']);
            if ($registerObjectResult === false) {
                return [
                    'request_name_state' => 'owned',
                    'register_object_state' => 'failed',
                    'wait_loop_state' => 'not-attempted',
                    'blocking_reason' => 'native-dbus-extension-register-object-failed',
                    'driver_status' => 'register-object-failed',
                    'driver' => $driver + [
                        'exported_object_class' => NativeDbusExtensionRuntimeObject::class,
                        'register_object_handoff_channels' => array_keys($registerObjectHandoff['channels'] ?? []),
                        'register_object_signature' => $registerObjectSignature,
                        'register_object_invocation_plan' => $registerObjectInvocationPlan['report'],
                        'register_object_result' => false,
                    ],
                ];
            }
            $driver['exported_object_class'] = NativeDbusExtensionRuntimeObject::class;
            $driver['register_object_handoff_channels'] = array_keys($registerObjectHandoff['channels'] ?? []);
            $driver['register_object_signature'] = $registerObjectSignature;
            $driver['register_object_invocation_plan'] = $registerObjectInvocationPlan['report'];
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
            $waitLoopSignature = $this->createWaitLoopSignatureReport($connection);
            $waitLoopInvocationPlan = $this->createWaitLoopInvocationPlan($connection);
            $waitLoopResult = $this->invokeWaitLoop($connection, $waitLoopInvocationPlan['arguments']);
            if ($waitLoopResult === false) {
                return [
                    'request_name_state' => 'owned',
                    'register_object_state' => 'registered',
                    'wait_loop_state' => 'failed',
                    'blocking_reason' => 'native-dbus-extension-wait-loop-failed',
                    'driver_status' => 'wait-loop-failed',
                    'driver' => $driver + [
                        'wait_loop_signature' => $waitLoopSignature,
                        'wait_loop_invocation_plan' => $waitLoopInvocationPlan['report'],
                        'wait_loop_result' => false,
                    ],
                ];
            }
            $driver['wait_loop_signature'] = $waitLoopSignature;
            $driver['wait_loop_invocation_plan'] = $waitLoopInvocationPlan['report'];
            $driver['wait_loop_result'] = $this->normalizeResultValue($waitLoopResult);
        } catch (Throwable $exception) {
            return [
                'request_name_state' => 'owned',
                'register_object_state' => 'registered',
                'wait_loop_state' => 'failed',
                'blocking_reason' => 'native-dbus-extension-wait-loop-failed',
                'driver_status' => 'wait-loop-failed',
                'driver' => $driver + [
                    'wait_loop_signature' => $waitLoopSignature ?? null,
                    'wait_loop_invocation_plan' => $waitLoopInvocationPlan['report'] ?? null,
                    'error_message' => $exception->getMessage(),
                ],
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

    private function resolveRuntimeRootDir(array $environment): string
    {
        $override = $environment['W4_SYSTEM_API_ROOT_DIR'] ?? null;
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        $currentDirectory = getcwd();
        if (is_string($currentDirectory) && trim($currentDirectory) !== '') {
            return trim($currentDirectory);
        }

        return dirname(__DIR__, 2);
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
    private function invokeRequestName(object $connection, array $arguments): mixed
    {
        $method = new ReflectionMethod($connection, 'requestName');

        return $method->invokeArgs($connection, $arguments);
    }

    /**
     * @return array<string, mixed>
     */
    private function createRequestNameSignatureReport(object $connection): array
    {
        $method = new ReflectionMethod($connection, 'requestName');
        $parameters = [];
        $unresolvedParameters = [];

        foreach ($method->getParameters() as $index => $parameter) {
            $classification = $this->classifyRequestNameParameter($parameter, $index);
            $parameters[] = [
                'position' => $index,
                'name' => $parameter->getName(),
                'type' => $this->describeParameterType($parameter),
                'allows_null' => $parameter->allowsNull(),
                'has_default' => $parameter->isDefaultValueAvailable(),
                'resolution_source' => $classification['resolution_source'],
                'resolved_channel' => $classification['resolved_channel'],
                'compatible' => $classification['compatible'],
            ];

            if ($classification['compatible'] !== true) {
                $unresolvedParameters[] = $parameter->getName();
            }
        }

        return [
            'method_name' => 'requestName',
            'parameter_count' => $method->getNumberOfParameters(),
            'compatible' => $unresolvedParameters === [],
            'unresolved_parameters' => $unresolvedParameters,
            'parameters' => $parameters,
        ];
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @return array{arguments: list<mixed>, report: array<string, mixed>}
     */
    private function createRequestNameInvocationPlan(object $connection, array $serviceDefinition): array
    {
        $method = new ReflectionMethod($connection, 'requestName');
        $arguments = [];
        $parameterBindings = [];

        foreach ($method->getParameters() as $index => $parameter) {
            $classification = $this->classifyRequestNameParameter($parameter, $index);
            $value = $index === 0
                ? ($serviceDefinition['bus_name'] ?? 'org.w4.System1')
                : $this->defaultValueForParameter($parameter, 0);
            $arguments[] = $value;
            $valueSummary = $this->summarizeInvocationValue($value, $classification['resolved_channel']);
            $parameterBindings[] = [
                'position' => $index,
                'name' => $parameter->getName(),
                'resolved_channel' => $classification['resolved_channel'],
                'resolution_source' => $classification['resolution_source'],
                'value_kind' => $valueSummary['value_kind'],
                'value_preview' => $valueSummary['value_preview'],
            ];
        }

        return [
            'arguments' => $arguments,
            'report' => [
                'method_name' => 'requestName',
                'parameter_count' => $method->getNumberOfParameters(),
                'parameter_bindings' => $parameterBindings,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $registerObjectHandoff
     * @return array<string, mixed>
     */
    private function createRegisterObjectSignatureReport(object $connection, array $registerObjectHandoff): array
    {
        $method = new ReflectionMethod($connection, 'registerObject');
        $parameters = [];
        $resolvedChannels = [];
        $unresolvedParameters = [];

        foreach ($method->getParameters() as $index => $parameter) {
            $classification = $this->classifyRegisterObjectParameter($parameter, $index);
            $resolvedChannel = $classification['resolved_channel'];
            $parameters[] = [
                'position' => $index,
                'name' => $parameter->getName(),
                'type' => $this->describeParameterType($parameter),
                'allows_null' => $parameter->allowsNull(),
                'has_default' => $parameter->isDefaultValueAvailable(),
                'resolution_source' => $classification['resolution_source'],
                'resolved_channel' => $resolvedChannel,
                'compatible' => $classification['compatible'],
            ];

            if (is_string($resolvedChannel) && $resolvedChannel !== '') {
                $resolvedChannels[] = $resolvedChannel;
            }

            if ($classification['compatible'] !== true) {
                $unresolvedParameters[] = $parameter->getName();
            }
        }

        $uniqueResolvedChannels = array_values(array_unique($resolvedChannels));

        return [
            'method_name' => 'registerObject',
            'parameter_count' => $method->getNumberOfParameters(),
            'resolved_channels' => $uniqueResolvedChannels,
            'preferred_parameter_group_match' => $this->resolvePreferredParameterGroupMatch(
                $uniqueResolvedChannels,
                $registerObjectHandoff
            ),
            'compatible' => $unresolvedParameters === [],
            'unresolved_parameters' => $unresolvedParameters,
            'parameters' => $parameters,
        ];
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     * @param array<string, mixed> $registerObjectHandoff
     * @return array{arguments: list<mixed>, report: array<string, mixed>}
     */
    private function createRegisterObjectInvocationPlan(
        object $connection,
        array $serviceDefinition,
        NativeDbusExtensionRuntimeObject $exportedObject,
        array $registerObjectHandoff
    ): array {
        $method = new ReflectionMethod($connection, 'registerObject');
        $arguments = [];
        $parameterBindings = [];

        foreach ($method->getParameters() as $index => $parameter) {
            if ($index === 0) {
                $value = $serviceDefinition['object_path'] ?? '/org/w4/System1';
                $arguments[] = $value;
                $parameterBindings[] = [
                    'position' => 0,
                    'name' => $parameter->getName(),
                    'resolved_channel' => 'object_path',
                    'value_kind' => 'string',
                    'value_preview' => $value,
                ];
                continue;
            }

            $classification = $this->classifyRegisterObjectParameter($parameter);
            $value = $this->resolveRegisterObjectParameterValue(
                $parameter,
                $serviceDefinition,
                $exportedObject,
                $registerObjectHandoff
            );
            $arguments[] = $value;
            $valueSummary = $this->summarizeInvocationValue($value, $classification['resolved_channel']);
            $parameterBindings[] = [
                'position' => $index,
                'name' => $parameter->getName(),
                'resolved_channel' => $classification['resolved_channel'],
                'resolution_source' => $classification['resolution_source'],
                'value_kind' => $valueSummary['value_kind'],
                'value_preview' => $valueSummary['value_preview'],
            ];
        }

        return [
            'arguments' => $arguments,
            'report' => [
                'method_name' => 'registerObject',
                'parameter_count' => $method->getNumberOfParameters(),
                'parameter_bindings' => $parameterBindings,
            ],
        ];
    }

    /**
     * @param list<mixed> $arguments
     */
    private function invokeRegisterObject(
        object $connection,
        array $arguments
    ): mixed {
        $method = new ReflectionMethod($connection, 'registerObject');

        return $method->invokeArgs($connection, $arguments);
    }

    private function invokeWaitLoop(object $connection, array $arguments = []): mixed
    {
        $method = new ReflectionMethod($connection, 'waitLoop');

        return $method->invokeArgs($connection, $arguments);
    }

    /**
     * @return array<string, mixed>
     */
    private function createWaitLoopSignatureReport(object $connection): array
    {
        $method = new ReflectionMethod($connection, 'waitLoop');
        $parameters = [];
        $unresolvedParameters = [];

        foreach ($method->getParameters() as $index => $parameter) {
            $classification = $this->classifyWaitLoopParameter($parameter);
            $parameters[] = [
                'position' => $index,
                'name' => $parameter->getName(),
                'type' => $this->describeParameterType($parameter),
                'allows_null' => $parameter->allowsNull(),
                'has_default' => $parameter->isDefaultValueAvailable(),
                'resolution_source' => $classification['resolution_source'],
                'resolved_channel' => $classification['resolved_channel'],
                'compatible' => $classification['compatible'],
            ];

            if ($classification['compatible'] !== true) {
                $unresolvedParameters[] = $parameter->getName();
            }
        }

        return [
            'method_name' => 'waitLoop',
            'parameter_count' => $method->getNumberOfParameters(),
            'compatible' => $unresolvedParameters === [],
            'unresolved_parameters' => $unresolvedParameters,
            'parameters' => $parameters,
        ];
    }

    /**
     * @return array{arguments: list<mixed>, report: array<string, mixed>}
     */
    private function createWaitLoopInvocationPlan(object $connection): array
    {
        $method = new ReflectionMethod($connection, 'waitLoop');

        return [
            'arguments' => [],
            'report' => [
                'method_name' => 'waitLoop',
                'parameter_count' => $method->getNumberOfParameters(),
                'parameter_bindings' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    private function resolveRegisterObjectParameterValue(
        ReflectionParameter $parameter,
        array $serviceDefinition,
        NativeDbusExtensionRuntimeObject $exportedObject,
        array $registerObjectHandoff
    ): mixed {
        $classification = $this->classifyRegisterObjectParameter($parameter);
        $parameterType = $parameter->getType();

        if ($classification['resolved_channel'] === 'exported_object') {
            return $exportedObject;
        }

        if ($classification['resolved_channel'] === 'introspection_xml') {
            return $registerObjectHandoff['channels']['introspection_xml'] ?? '';
        }

        if ($classification['resolved_channel'] === 'interface') {
            return $registerObjectHandoff['channels']['interface']
                ?? ($serviceDefinition['interface'] ?? 'org.w4.System1.Manager');
        }

        if ($classification['resolved_channel'] === 'definition') {
            return $registerObjectHandoff['channels']['definition'] ?? [];
        }

        if ($classification['resolved_channel'] === 'methods') {
            return $registerObjectHandoff['channels']['methods'] ?? [];
        }

        if ($classification['resolved_channel'] === 'properties') {
            return $registerObjectHandoff['channels']['properties'] ?? [];
        }

        return $this->defaultValueForParameter($parameter, $exportedObject);
    }

    /**
     * @return array{resolved_channel: string, resolution_source: string, compatible: bool}
     */
    private function classifyRegisterObjectParameter(ReflectionParameter $parameter, int $index = -1): array
    {
        if ($index === 0) {
            return [
                'resolved_channel' => 'object_path',
                'resolution_source' => 'register-object-path',
                'compatible' => true,
            ];
        }

        $parameterType = $parameter->getType();
        if ($parameterType instanceof ReflectionNamedType && !$parameterType->isBuiltin()) {
            return [
                'resolved_channel' => 'exported_object',
                'resolution_source' => 'object-type-handoff',
                'compatible' => true,
            ];
        }

        $parameterName = strtolower($parameter->getName());
        if (
            str_contains($parameterName, 'introspection')
            || str_contains($parameterName, 'xml')
            || str_contains($parameterName, 'node')
        ) {
            return [
                'resolved_channel' => 'introspection_xml',
                'resolution_source' => 'parameter-name-handoff',
                'compatible' => true,
            ];
        }

        if (str_contains($parameterName, 'interface')) {
            return [
                'resolved_channel' => 'interface',
                'resolution_source' => 'parameter-name-handoff',
                'compatible' => true,
            ];
        }

        if (
            $parameterType instanceof ReflectionNamedType
            && $parameterType->getName() === 'array'
        ) {
            if (
                str_contains($parameterName, 'definition')
                || str_contains($parameterName, 'descriptor')
                || str_contains($parameterName, 'metadata')
                || str_contains($parameterName, 'contract')
            ) {
                return [
                    'resolved_channel' => 'definition',
                    'resolution_source' => 'parameter-name-handoff',
                    'compatible' => true,
                ];
            }

            if (str_contains($parameterName, 'methods')) {
                return [
                    'resolved_channel' => 'methods',
                    'resolution_source' => 'parameter-name-handoff',
                    'compatible' => true,
                ];
            }

            if (str_contains($parameterName, 'properties')) {
                return [
                    'resolved_channel' => 'properties',
                    'resolution_source' => 'parameter-name-handoff',
                    'compatible' => true,
                ];
            }
        }

        if (
            str_contains($parameterName, 'object')
            || str_contains($parameterName, 'export')
            || str_contains($parameterName, 'handler')
            || str_contains($parameterName, 'service')
        ) {
            return [
                'resolved_channel' => 'exported_object',
                'resolution_source' => 'parameter-name-handoff',
                'compatible' => true,
            ];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return [
                'resolved_channel' => 'default-value',
                'resolution_source' => 'parameter-default',
                'compatible' => true,
            ];
        }

        if ($parameter->allowsNull()) {
            return [
                'resolved_channel' => 'null-default',
                'resolution_source' => 'nullable-parameter',
                'compatible' => true,
            ];
        }

        return [
            'resolved_channel' => 'fallback',
            'resolution_source' => 'generic-fallback',
            'compatible' => false,
        ];
    }

    /**
     * @return array{resolved_channel: string, resolution_source: string, compatible: bool}
     */
    private function classifyRequestNameParameter(ReflectionParameter $parameter, int $index): array
    {
        if ($index === 0) {
            return [
                'resolved_channel' => 'bus_name',
                'resolution_source' => 'request-name-bus-name',
                'compatible' => true,
            ];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return [
                'resolved_channel' => 'default-value',
                'resolution_source' => 'parameter-default',
                'compatible' => true,
            ];
        }

        if ($parameter->allowsNull()) {
            return [
                'resolved_channel' => 'null-default',
                'resolution_source' => 'nullable-parameter',
                'compatible' => true,
            ];
        }

        $parameterType = $parameter->getType();
        if ($parameterType instanceof ReflectionNamedType && $parameterType->isBuiltin()) {
            return [
                'resolved_channel' => 'builtin-default',
                'resolution_source' => 'generic-builtin-default',
                'compatible' => true,
            ];
        }

        return [
            'resolved_channel' => 'fallback',
            'resolution_source' => 'generic-fallback',
            'compatible' => false,
        ];
    }

    /**
     * @return array{resolved_channel: string, resolution_source: string, compatible: bool}
     */
    private function classifyWaitLoopParameter(ReflectionParameter $parameter): array
    {
        if ($parameter->isDefaultValueAvailable()) {
            return [
                'resolved_channel' => 'default-value',
                'resolution_source' => 'parameter-default',
                'compatible' => true,
            ];
        }

        if ($parameter->allowsNull()) {
            return [
                'resolved_channel' => 'null-default',
                'resolution_source' => 'nullable-parameter',
                'compatible' => true,
            ];
        }

        return [
            'resolved_channel' => 'fallback',
            'resolution_source' => 'generic-fallback',
            'compatible' => false,
        ];
    }

    private function describeParameterType(ReflectionParameter $parameter): ?string
    {
        $parameterType = $parameter->getType();
        if (!$parameterType instanceof ReflectionNamedType) {
            return null;
        }

        return $parameterType->getName();
    }

    /**
     * @param list<string> $resolvedChannels
     * @param array<string, mixed> $registerObjectHandoff
     * @return list<string>|null
     */
    private function resolvePreferredParameterGroupMatch(array $resolvedChannels, array $registerObjectHandoff): ?array
    {
        $normalizedResolved = $resolvedChannels;
        sort($normalizedResolved);

        foreach (($registerObjectHandoff['preferred_parameter_groups'] ?? []) as $group) {
            if (!is_array($group)) {
                continue;
            }

            $normalizedGroup = array_values(array_filter($group, static fn (mixed $value): bool => is_string($value)));
            sort($normalizedGroup);

            if ($normalizedGroup === $normalizedResolved) {
                return array_values($group);
            }
        }

        return null;
    }

    /**
     * @return array{value_kind: string, value_preview: mixed}
     */
    private function summarizeInvocationValue(mixed $value, string $resolvedChannel): array
    {
        if (is_object($value)) {
            return [
                'value_kind' => 'object',
                'value_preview' => get_class($value),
            ];
        }

        if (is_string($value)) {
            if ($resolvedChannel === 'introspection_xml') {
                return [
                    'value_kind' => 'string',
                    'value_preview' => [
                        'kind' => 'dbus-introspection-xml',
                        'length' => strlen($value),
                    ],
                ];
            }

            return [
                'value_kind' => 'string',
                'value_preview' => $value,
            ];
        }

        if (is_array($value)) {
            if ($resolvedChannel === 'definition') {
                return [
                    'value_kind' => 'array',
                    'value_preview' => [
                        'kind' => 'export-definition',
                        'method_count' => count($value['methods'] ?? []),
                        'property_count' => count($value['properties'] ?? []),
                    ],
                ];
            }

            if ($resolvedChannel === 'methods') {
                return [
                    'value_kind' => 'array',
                    'value_preview' => [
                        'kind' => 'methods-list',
                        'count' => count($value),
                    ],
                ];
            }

            if ($resolvedChannel === 'properties') {
                return [
                    'value_kind' => 'array',
                    'value_preview' => [
                        'kind' => 'properties-list',
                        'count' => count($value),
                    ],
                ];
            }
        }

        return [
            'value_kind' => gettype($value),
            'value_preview' => $this->normalizeResultValue($value),
        ];
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
