<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class NativeDbusExtensionRuntimeObject
{
    public readonly string $ApiVersion;
    public readonly string $BindingStatus;

    private SystemApiManager $manager;
    private SystemApiToolkit $toolkit;

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    public function __construct(
        private readonly array $serviceDefinition,
        string $rootDir
    ) {
        $this->ApiVersion = '1.0';
        $this->BindingStatus = 'native-runtime-object-exported';
        $this->manager = new SystemApiManager($rootDir);
        $this->toolkit = new SystemApiToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createSpecificationPayload(): array
    {
        return [
            'kind' => 'system-api-dbus-native-runtime-object',
            'bus_name' => $this->serviceDefinition['bus_name'] ?? null,
            'object_path' => $this->serviceDefinition['object_path'] ?? null,
            'interface' => $this->serviceDefinition['interface'] ?? null,
            'service_lifecycle' => $this->serviceDefinition['service_lifecycle'] ?? null,
            'api_version' => $this->ApiVersion,
            'binding_status' => $this->BindingStatus,
            'supported_methods' => array_column($this->createExportDefinitionPayload()['methods'], 'name'),
            'binding_properties' => array_column($this->createExportDefinitionPayload()['properties'], 'name'),
            'dispatch_mode' => 'system-api-toolkit',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createExportDefinitionPayload(): array
    {
        return [
            'interface' => $this->serviceDefinition['interface'] ?? 'org.w4.System1.Manager',
            'object_path' => $this->serviceDefinition['object_path'] ?? '/org/w4/System1',
            'methods' => [
                [
                    'name' => 'Call',
                    'input' => [
                        ['name' => 'request_json', 'type' => 's'],
                    ],
                    'output' => [
                        ['name' => 'response_json', 'type' => 's'],
                    ],
                    'callable' => 'Call',
                ],
                [
                    'name' => 'GetCapabilities',
                    'input' => [
                        ['name' => 'request_id', 'type' => 's'],
                    ],
                    'output' => [
                        ['name' => 'response_json', 'type' => 's'],
                    ],
                    'callable' => 'GetCapabilities',
                ],
            ],
            'properties' => [
                [
                    'name' => 'ApiVersion',
                    'type' => 's',
                    'access' => 'read',
                ],
                [
                    'name' => 'BindingStatus',
                    'type' => 's',
                    'access' => 'read',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCapabilitiesPayload(?string $requestId = null): array
    {
        return $this->toolkit->createCapabilitiesResponse($requestId);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function dispatchCallPayload(array $request = []): array
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

    public function DescribeSpecification(): string
    {
        return $this->encodeJson($this->createSpecificationPayload());
    }

    public function GetCapabilities(string $requestId = ''): string
    {
        $normalizedRequestId = trim($requestId) === '' ? null : $requestId;

        return $this->manager->getCapabilities($normalizedRequestId);
    }

    public function Call(string $requestJson = '{}'): string
    {
        return $this->manager->call($requestJson);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeJson(array $payload): string
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new \RuntimeException('No se pudo serializar la especificacion del runtime D-Bus nativo');
        }

        return $encoded . PHP_EOL;
    }
}
