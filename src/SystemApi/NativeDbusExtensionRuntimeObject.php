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
        $exportDefinition = $this->createExportDefinitionPayload();

        return [
            'kind' => 'system-api-dbus-native-runtime-object',
            'bus_name' => $this->serviceDefinition['bus_name'] ?? null,
            'object_path' => $this->serviceDefinition['object_path'] ?? null,
            'interface' => $this->serviceDefinition['interface'] ?? null,
            'service_lifecycle' => $this->serviceDefinition['service_lifecycle'] ?? null,
            'api_version' => $this->ApiVersion,
            'binding_status' => $this->BindingStatus,
            'supported_methods' => array_column($exportDefinition['methods'], 'name'),
            'binding_properties' => array_column($exportDefinition['properties'], 'name'),
            'introspection_xml_available' => true,
            'dispatch_mode' => 'system-api-toolkit',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createExportDefinitionPayload(): array
    {
        $interface = $this->serviceDefinition['interface'] ?? 'org.w4.System1.Manager';
        $objectPath = $this->serviceDefinition['object_path'] ?? '/org/w4/System1';
        $methods = [
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
        ];
        $properties = [
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
        ];

        return [
            'interface' => $interface,
            'object_path' => $objectPath,
            'methods' => $methods,
            'properties' => $properties,
            'introspection_xml' => $this->createIntrospectionXml($objectPath, $interface, $methods, $properties),
        ];
    }

    /**
     * @param list<array{name: string, input: list<array{name: string, type: string}>, output: list<array{name: string, type: string}>, callable: string}> $methods
     * @param list<array{name: string, type: string, access: string}> $properties
     */
    public function createIntrospectionXml(
        ?string $objectPath = null,
        ?string $interface = null,
        array $methods = [],
        array $properties = []
    ): string {
        $resolvedObjectPath = $objectPath ?? ($this->serviceDefinition['object_path'] ?? '/org/w4/System1');
        $resolvedInterface = $interface ?? ($this->serviceDefinition['interface'] ?? 'org.w4.System1.Manager');
        $resolvedMethods = $methods === [] ? $this->defaultExportMethods() : $methods;
        $resolvedProperties = $properties === [] ? $this->defaultExportProperties() : $properties;

        $methodXml = '';
        foreach ($resolvedMethods as $method) {
            $methodXml .= sprintf("    <method name=\"%s\">\n", $method['name']);

            foreach ($method['input'] as $input) {
                $methodXml .= sprintf(
                    "      <arg name=\"%s\" type=\"%s\" direction=\"in\"/>\n",
                    $input['name'],
                    $input['type']
                );
            }

            foreach ($method['output'] as $output) {
                $methodXml .= sprintf(
                    "      <arg name=\"%s\" type=\"%s\" direction=\"out\"/>\n",
                    $output['name'],
                    $output['type']
                );
            }

            $methodXml .= "    </method>\n";
        }

        $propertyXml = '';
        foreach ($resolvedProperties as $property) {
            $propertyXml .= sprintf(
                "    <property name=\"%s\" type=\"%s\" access=\"%s\"/>\n",
                $property['name'],
                $property['type'],
                $property['access']
            );
        }

        return sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE node PUBLIC \"-//freedesktop//DTD D-BUS Object Introspection 1.0//EN\"\n \"http://www.freedesktop.org/standards/dbus/1.0/introspect.dtd\">\n<node name=\"%s\">\n  <interface name=\"%s\">\n    <annotation name=\"org.freedesktop.DBus.DocString\"\n     value=\"W4 System API public manager interface. The runtime adapter is still pending; this contract freezes the binding surface.\"/>\n%s%s  </interface>\n</node>\n",
            $resolvedObjectPath,
            $resolvedInterface,
            $methodXml,
            $propertyXml
        );
    }

    /**
     * @return list<array{name: string, input: list<array{name: string, type: string}>, output: list<array{name: string, type: string}>, callable: string}>
     */
    private function defaultExportMethods(): array
    {
        return [
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
        ];
    }

    /**
     * @return list<array{name: string, type: string, access: string}>
     */
    private function defaultExportProperties(): array
    {
        return [
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
