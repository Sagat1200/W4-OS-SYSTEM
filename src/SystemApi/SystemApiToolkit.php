<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ValidationError;
use W4\OS\W4ctl\W4ctlApplication;

final class SystemApiToolkit
{
    private const API_VERSION = '1.0';
    private const PROPOSED_BUS_NAME = 'org.w4.System1';
    private const PROPOSED_OBJECT_PATH = '/org/w4/System1';
    private const PROPOSED_INTERFACE = 'org.w4.System1.Manager';

    private W4ctlApplication $w4ctl;

    public function __construct(string $rootDir)
    {
        $this->w4ctl = new W4ctlApplication($rootDir);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function respond(array $request): array
    {
        $requestId = $this->extractRequestId($request);

        try {
            return $this->execute($request, $requestId);
        } catch (ValidationError $exception) {
            return $this->createErrorResponse(
                $requestId,
                $this->resolveErrorCode($exception),
                $exception->getMessage()
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createCapabilitiesResponse(?string $requestId = null): array
    {
        $resolvedRequestId = $requestId !== null && trim($requestId) !== ''
            ? trim($requestId)
            : 'system-api-capabilities';

        $payload = [
            'kind' => 'system-api-capabilities',
            'transport' => [
                'default' => 'local-cli-json',
                'tcp_listener_enabled_by_default' => false,
            ],
            'binding' => [
                'status' => 'local-manager-ready',
                'dbus' => [
                    'bus' => 'system',
                    'name' => self::PROPOSED_BUS_NAME,
                    'object_path' => self::PROPOSED_OBJECT_PATH,
                    'interface' => self::PROPOSED_INTERFACE,
                    'methods' => [
                        'Call',
                        'GetCapabilities',
                    ],
                ],
                'local_manager' => [
                    'script' => 'scripts/system_api_manager.php',
                    'commands' => [
                        'call',
                        'get-capabilities',
                    ],
                ],
            ],
            'methods' => [
                [
                    'name' => 'system.capabilities',
                    'resource_kind' => 'system-api-capabilities',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'profiles.list',
                    'resource_kind' => 'profile-collection',
                    'mutates_state' => false,
                ],
                [
                    'name' => 'profiles.get',
                    'resource_kind' => 'edition-profile',
                    'mutates_state' => false,
                    'params' => ['profile_id'],
                ],
                [
                    'name' => 'builds.validate',
                    'resource_kind' => 'build-validation',
                    'mutates_state' => false,
                    'params' => ['profile_id', 'channel?', 'format?'],
                ],
                [
                    'name' => 'live.inspect',
                    'resource_kind' => 'live-inspection',
                    'mutates_state' => false,
                    'params' => ['profile_id', 'live_output_dir?', 'live_manifest?'],
                ],
                [
                    'name' => 'artifacts.manifest',
                    'resource_kind' => 'artifact-manifest',
                    'mutates_state' => false,
                    'params' => ['artifact', 'profile_id?', 'manifest?'],
                ],
                [
                    'name' => 'updates.plan',
                    'resource_kind' => 'update-plan',
                    'mutates_state' => false,
                    'params' => ['request_path', 'operation_id?', 'output_path?'],
                ],
            ],
        ];

        return $this->createSuccessResponse(
            requestId: $resolvedRequestId,
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: 'system-api',
            data: $payload
        );
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function execute(array $request, string $requestId): array
    {
        $apiVersion = $request['api_version'] ?? null;
        if (!is_string($apiVersion) || trim($apiVersion) !== self::API_VERSION) {
            throw new ValidationError('api_version debe ser 1.0');
        }

        $method = $request['method'] ?? null;
        if (!is_string($method) || trim($method) === '') {
            throw new ValidationError('method debe ser un string no vacio');
        }

        $method = trim($method);
        $params = $request['params'] ?? [];
        if (!is_array($params)) {
            throw new ValidationError('params debe ser un objeto JSON');
        }

        if ($method === 'system.capabilities') {
            return $this->createCapabilitiesResponse($requestId);
        }

        [$command, $options, $resourceKind, $resourceId] = $this->translateMethod($method, $params);
        $payload = $this->w4ctl->handleCommand($command, $options);

        return $this->createSuccessResponse(
            requestId: $requestId,
            method: $method,
            resourceKind: $resourceKind,
            resourceId: $this->resolveResourceId($resourceId, $payload),
            data: $payload
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array{0:string,1:array<string, string>,2:string,3:?string}
     */
    private function translateMethod(string $method, array $params): array
    {
        return match ($method) {
            'profiles.list' => ['profile:list', [], 'profile-collection', 'profiles'],
            'profiles.get' => [
                'profile:show',
                ['profile' => $this->requireString($params, 'profile_id')],
                'edition-profile',
                $this->requireString($params, 'profile_id'),
            ],
            'builds.validate' => [
                'build:validate',
                $this->filterOptions([
                    'profile' => $this->requireString($params, 'profile_id'),
                    'channel' => $this->optionalString($params, 'channel') ?? 'testing',
                    'format' => $this->optionalString($params, 'format') ?? 'iso',
                ]),
                'build-validation',
                $this->requireString($params, 'profile_id'),
            ],
            'live.inspect' => [
                'live:inspect',
                $this->filterOptions([
                    'profile' => $this->requireString($params, 'profile_id'),
                    'live-output-dir' => $this->optionalString($params, 'live_output_dir'),
                    'live-manifest' => $this->optionalString($params, 'live_manifest'),
                ]),
                'live-inspection',
                $this->requireString($params, 'profile_id'),
            ],
            'artifacts.manifest' => [
                'artifact:manifest',
                $this->filterOptions([
                    'artifact' => $this->requireString($params, 'artifact'),
                    'profile' => $this->optionalString($params, 'profile_id'),
                    'manifest' => $this->optionalString($params, 'manifest'),
                ]),
                'artifact-manifest',
                $this->optionalString($params, 'profile_id') ?? $this->requireString($params, 'artifact'),
            ],
            'updates.plan' => [
                'update:plan',
                $this->filterOptions([
                    'request' => $this->requireString($params, 'request_path'),
                    'operation-id' => $this->optionalString($params, 'operation_id'),
                    'output' => $this->optionalString($params, 'output_path'),
                ]),
                'update-plan',
                $this->optionalString($params, 'operation_id'),
            ],
            default => throw new ValidationError(sprintf('Metodo no soportado: %s', $method)),
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function createSuccessResponse(
        string $requestId,
        string $method,
        string $resourceKind,
        ?string $resourceId,
        array $data
    ): array {
        $observedAt = gmdate('c');

        return [
            'api_version' => self::API_VERSION,
            'request_id' => $requestId,
            'result' => [
                'operation_id' => null,
                'method' => $method,
                'resource' => [
                    'id' => $resourceId,
                    'kind' => $resourceKind,
                    'revision' => $this->buildRevision($data),
                    'observed_at' => $observedAt,
                    'provider' => 'local-w4ctl-facade',
                ],
                'capabilities' => [
                    'read-state',
                    'local-cli-json',
                    'w4ctl-contract-reuse',
                ],
                'data' => $data,
                'links' => [
                    'self' => '/system-api/' . str_replace('.', '/', $method),
                ],
            ],
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createErrorResponse(string $requestId, string $code, string $message): array
    {
        return [
            'api_version' => self::API_VERSION,
            'request_id' => $requestId,
            'result' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'retryable' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $request
     */
    private function extractRequestId(array $request): string
    {
        $requestId = $request['request_id'] ?? null;
        if (!is_string($requestId) || trim($requestId) === '') {
            return 'invalid-request';
        }

        return trim($requestId);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function requireString(array $params, string $field): string
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('params.%s debe ser un string no vacio', $field));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function optionalString(array $params, string $field): ?string
    {
        $value = $params[$field] ?? null;
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('params.%s debe ser un string no vacio cuando se usa', $field));
        }

        return trim($value);
    }

    /**
     * @param array<string, ?string> $options
     * @return array<string, string>
     */
    private function filterOptions(array $options): array
    {
        $filtered = [];
        foreach ($options as $key => $value) {
            if ($value !== null && $value !== '') {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildRevision(array $data): string
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = 'unserializable';
        }

        return sha1($encoded);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveResourceId(?string $resourceId, array $payload): ?string
    {
        if ($resourceId !== null && $resourceId !== '') {
            return $resourceId;
        }

        $payloadResourceId = $payload['operation_id'] ?? $payload['profile_id'] ?? null;
        if (!is_string($payloadResourceId) || $payloadResourceId === '') {
            return null;
        }

        return $payloadResourceId;
    }

    private function resolveErrorCode(ValidationError $exception): string
    {
        if (str_starts_with($exception->getMessage(), 'Metodo no soportado:')) {
            return 'NOT_SUPPORTED';
        }

        return 'VALIDATION_ERROR';
    }
}
