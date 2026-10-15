<?php

declare(strict_types=1);

namespace W4\OS\Tests\Fixtures;

final class NativeRuntimeOwnershipDriverStub
{
    /**
     * @param array<string, mixed> $serviceDefinition
     * @return array<string, mixed>
     */
    public function materializeOwnership(array $serviceDefinition): array
    {
        return [
            'request_name_state' => 'owned',
            'register_object_state' => 'registered',
            'wait_loop_state' => 'running',
            'driver_status' => 'ownership-materialized',
            'driver' => [
                'mode' => 'native-driver',
                'class_name' => self::class,
                'bus_name' => $serviceDefinition['bus_name'] ?? null,
                'object_path' => $serviceDefinition['object_path'] ?? null,
            ],
        ];
    }
}
