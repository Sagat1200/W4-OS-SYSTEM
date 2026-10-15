<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class NativeDbusExtensionRuntimeObject
{
    /**
     * @param array<string, mixed> $serviceDefinition
     */
    public function __construct(
        private readonly array $serviceDefinition
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function describeSpecification(): array
    {
        return [
            'kind' => 'system-api-dbus-native-runtime-object',
            'bus_name' => $this->serviceDefinition['bus_name'] ?? null,
            'object_path' => $this->serviceDefinition['object_path'] ?? null,
            'interface' => $this->serviceDefinition['interface'] ?? null,
            'service_lifecycle' => $this->serviceDefinition['service_lifecycle'] ?? null,
            'dispatch_mode' => 'runtime-placeholder',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCapabilities(): array
    {
        return [
            'kind' => 'system-api-dbus-native-runtime-capabilities',
            'supported_methods' => [
                'DescribeSpecification',
                'GetCapabilities',
                'Call',
            ],
            'dispatch_mode' => 'runtime-placeholder',
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function call(array $request = []): array
    {
        return [
            'status' => 'native-runtime-placeholder-dispatch',
            'request' => $request,
            'service_definition' => $this->describeSpecification(),
        ];
    }
}
