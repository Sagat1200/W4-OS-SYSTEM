<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/scripts/run_native_dbus_failure_check.php';

final class NativeDbusFailureCheckTest extends TestCase
{
    private function evidence(): array
    {
        $response = static fn (string $id, string $method): array => [
            'request_id' => $id, 'error' => null, 'result' => ['method' => $method],
        ];
        return [
            'native_extension' => true,
            'owner_before' => "(':1.3',)",
            'owner_after' => "(':1.8',)",
            'before' => $response('failure-before', 'system.capabilities'),
            'after' => $response('failure-after', 'system.capabilities'),
            'runtime' => $response('failure-runtime', 'system.dbus-runtime'),
            'parent' => [
                'service_observation' => ['startup_report' => [
                    'status' => 'native-runtime-wait-loop-failed',
                    'ownership_driver' => ['error_message' => 'Argument must be of type string'],
                ]],
                'auto_reactivation' => [
                    'termination_class' => 'wait-loop-failed',
                    'attempt' => 1,
                    'launched' => true,
                    'cooldown_backoff_base_ms' => 1000,
                    'failure_backoff_multiplier' => 3,
                    'cooldown_backoff_ms' => 3000,
                ],
            ],
            'admission' => [
                'attempt_count' => 1, 'last_attempt_epoch_ms' => 100, 'cooldown_until_epoch_ms' => 3100,
            ],
            'supervisor' => [
                'attempt_count' => 0,
                'previous_attempt_count' => 1,
                'cooldown_until_epoch_ms' => 0,
                'last_reset_trigger' => 'reactivation-stability-achieved',
                'termination_class' => 'wait-loop-failed',
                'failure_backoff_multiplier' => 3,
            ],
        ];
    }

    public function testValidatesCompleteEvidenceAndRejectsEachMissingComponent(): void
    {
        self::assertTrue(\validateNativeDbusFailureEvidence($this->evidence())['validated']);
        foreach (array_keys($this->evidence()) as $key) {
            $evidence = $this->evidence();
            unset($evidence[$key]);
            self::assertFalse(\validateNativeDbusFailureEvidence($evidence)['validated'], $key);
        }
    }

    public function testRejectsUnchangedOwnerDespiteSuccessfulSupervisorReset(): void
    {
        $evidence = $this->evidence();
        $evidence['owner_after'] = $evidence['owner_before'];
        $result = \validateNativeDbusFailureEvidence($evidence);
        self::assertTrue($result['checks']['supervisor_reset']);
        self::assertFalse($result['checks']['owner_replaced']);
        self::assertFalse($result['validated']);
    }

    public function testRejectsWrongResponseAndWrongPenalty(): void
    {
        $evidence = $this->evidence();
        $evidence['after']['error'] = ['code' => 'failed'];
        $evidence['runtime']['request_id'] = 'stale';
        $evidence['parent']['auto_reactivation']['cooldown_backoff_ms'] = 1000;
        $result = \validateNativeDbusFailureEvidence($evidence);
        self::assertFalse($result['validated']);
        self::assertFalse($result['checks']['capabilities_recovered']);
        self::assertFalse($result['checks']['runtime_recovered']);
        self::assertFalse($result['checks']['penalty']);
    }

    public function testDecodesLargeGVariantWithoutRegexBacktracking(): void
    {
        $payload = ['request_id' => 'decode', 'error' => null, 'data' => str_repeat("a'\\b\n", 10000)];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $variant = "('" . strtr($json, ["\\" => "\\\\", "'" => "\\'", "\n" => "\\n"]) . "',)";
        self::assertSame($payload, \nativeDbusFailureDecode(['exit_code' => 0, 'stdout' => $variant]));
        self::assertSame([], \nativeDbusFailureDecode(['exit_code' => 1, 'stdout' => $variant]));
        self::assertSame([], \nativeDbusFailureDecode(['exit_code' => 0, 'stdout' => "('invalid json',)"]));
        self::assertSame([], \nativeDbusFailureDecode(['exit_code' => 0, 'stdout' => '{}']));
    }
}
