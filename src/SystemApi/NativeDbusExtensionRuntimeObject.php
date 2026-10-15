<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class NativeDbusExtensionRuntimeObject
{
    private SystemApiToolkit $toolkit;

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    public function __construct(
        private readonly array $serviceDefinition,
        string $rootDir
    ) {
        $this->toolkit = new SystemApiToolkit($rootDir);
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
            'supported_methods' => [
                'DescribeSpecification',
                'GetCapabilities',
                'Call',
            ],
            'dispatch_mode' => 'system-api-toolkit',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCapabilities(?string $requestId = null): array
    {
        return $this->toolkit->createCapabilitiesResponse($requestId);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function call(array $request = []): array
    {
        $normalizedRequest = $request;
        if (!isset($normalizedRequest['api_version']) || !is_string($normalizedRequest['api_version'])) {
            $normalizedRequest['api_version'] = '1.0';
        }
        if (!isset($normalizedRequest['request_id']) || !is_string($normalizedRequest['request_id']) || trim($normalizedRequest['request_id']) === '') {
            $normalizedRequest['request_id'] = 'native-runtime-call';
        }
        if (!isset($normalizedRequest['params']) || !is_array($normalizedRequest['params'])) {
            $normalizedRequest['params'] = [];
        }

        return $this->toolkit->respond($normalizedRequest);
    }
}
