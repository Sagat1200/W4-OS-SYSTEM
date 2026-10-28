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

    private function rejectionEvidence(): array
    {
        $evidence = $this->evidence();
        $evidence['owner_after'] = $evidence['owner_before'];
        $evidence['parent_running'] = true;
        $evidence['supervisor_absent'] = true;
        foreach (\nativeDbusInvalidArgumentCases() as $label => $case) {
            $evidence['rejections'][$label] = [
                'api_version' => '1.0',
                'request_id' => 'invalid-request',
                'result' => null,
                'error' => ['code' => 'VALIDATION_ERROR', 'retryable' => false],
            ];
        }
        return $evidence;
    }

    public function testRejectionRequiresEveryMalformedCallResponse(): void
    {
        self::assertCount(10, \nativeDbusInvalidArgumentCases());
        self::assertTrue(\validateNativeDbusRejectionEvidence($this->rejectionEvidence())['validated']);
        foreach (\nativeDbusInvalidArgumentCases() as $label => $case) {
            $evidence = $this->rejectionEvidence();
            unset($evidence['rejections'][$label]);
            self::assertFalse(\validateNativeDbusRejectionEvidence($evidence)['validated'], $label);
        }
    }

    public function testRejectionRequiresContinuityRatherThanSuccessfulRecovery(): void
    {
        foreach ([
            ['owner_after', "(':1.99',)"],
            ['parent_running', false],
            ['supervisor_absent', false],
            ['after', []],
            ['runtime', []],
        ] as [$key, $value]) {
            $evidence = $this->rejectionEvidence();
            $evidence[$key] = $value;
            self::assertFalse(\validateNativeDbusRejectionEvidence($evidence)['validated'], $key);
        }
        $evidence = $this->rejectionEvidence();
        $evidence['rejections']['Call-array']['error']['code'] = 'INTERNAL_ERROR';
        self::assertFalse(\validateNativeDbusRejectionEvidence($evidence)['validated']);
    }

    private function cooldownEvidence(): array
    {
        $state = [
            'attempt_count' => 1, 'max_attempts' => 3,
            'last_attempt_epoch_ms' => 1000, 'cooldown_until_epoch_ms' => 11000,
        ];
        return [
            'native_extension' => true,
            'before' => ['request_id' => 'failure-before', 'error' => null, 'result' => ['method' => 'system.capabilities']],
            'owner_before' => "(':1.3',)",
            'owner_replacement' => "(':1.8',)",
            'first' => ['auto_reactivation' => ['attempted' => true, 'launched' => true, 'attempt' => 1]],
            'state_before' => $state, 'state_after' => $state,
            'probe_command' => ['exit_code' => 3],
            'probe' => [
                'status' => 'native-runtime-service-not-running',
                'startup_report' => ['status' => 'native-runtime-service-loop-exited'],
                'auto_reactivation' => [
                    'reason' => 'native-runtime-auto-reactivation-cooldown-active',
                    'attempted' => false, 'attempt' => 1, 'max_attempts' => 3,
                    'cooldown_until_epoch_ms' => 11000, 'cooldown_remaining_ms' => 4000,
                ],
            ],
            'owner_check' => ['exit_code' => 0, 'stdout' => '(false,)'],
        ];
    }

    public function testCooldownRequiresAllIndependentEvidence(): void
    {
        self::assertTrue(\validateNativeDbusCooldownEvidence($this->cooldownEvidence())['validated']);
        foreach (array_keys($this->cooldownEvidence()) as $key) {
            $evidence = $this->cooldownEvidence();
            unset($evidence[$key]);
            self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated'], $key);
        }
    }

    public function testCooldownRejectsBudgetExhaustionAndExpiredDeadlines(): void
    {
        foreach ([
            ['reason', 'native-runtime-auto-reactivation-budget-exhausted'],
            ['attempted', true],
            ['attempt', 2],
            ['cooldown_remaining_ms', 0],
            ['cooldown_remaining_ms', 10001],
            ['cooldown_remaining_ms', '4000'],
            ['cooldown_until_epoch_ms', 12000],
        ] as [$key, $value]) {
            $evidence = $this->cooldownEvidence();
            $evidence['probe']['auto_reactivation'][$key] = $value;
            self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated'], $key);
        }
    }

    public function testCooldownRejectsStateChangesAndFailedBusProbes(): void
    {
        $evidence = $this->cooldownEvidence();
        $evidence['state_after']['attempt_count'] = 2;
        self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated']);
        $evidence = $this->cooldownEvidence();
        $evidence['owner_check']['exit_code'] = 1;
        self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated']);
        $evidence = $this->cooldownEvidence();
        $evidence['owner_check']['stdout'] = '(true,)';
        self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated']);
        $evidence = $this->cooldownEvidence();
        $evidence['owner_replacement'] = $evidence['owner_before'];
        self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated']);
        $evidence = $this->cooldownEvidence();
        $evidence['error'] = 'Incomplete probe';
        self::assertFalse(\validateNativeDbusCooldownEvidence($evidence)['validated']);
    }

    private function daemonLossEvidence(): array
    {
        return [
            'native_extension' => true,
            'before' => ['request_id' => 'failure-before', 'error' => null, 'result' => ['method' => 'system.capabilities']],
            'owner_before' => "(':1.3',)",
            'daemon_pid' => 4242,
            'daemon_dead' => true,
            'owner_check_after' => ['exit_code' => 2],
            'capabilities_after_command' => ['exit_code' => 2],
            'runtime_after_command' => ['exit_code' => 2],
        ];
    }

    public function testDaemonLossRequiresAllEvidenceKeys(): void
    {
        self::assertTrue(\validateNativeDbusDaemonLossEvidence($this->daemonLossEvidence())['validated']);
        foreach (array_keys($this->daemonLossEvidence()) as $key) {
            $evidence = $this->daemonLossEvidence();
            unset($evidence[$key]);
            self::assertFalse(\validateNativeDbusDaemonLossEvidence($evidence)['validated'], $key);
        }
    }

    public function testDaemonLossRejectsHealthyBusAndServiceCalls(): void
    {
        $evidence = $this->daemonLossEvidence();
        $evidence['daemon_dead'] = false;
        self::assertFalse(\validateNativeDbusDaemonLossEvidence($evidence)['validated']);

        $evidence = $this->daemonLossEvidence();
        $evidence['owner_check_after']['exit_code'] = 0;
        self::assertFalse(\validateNativeDbusDaemonLossEvidence($evidence)['validated']);

        $evidence = $this->daemonLossEvidence();
        $evidence['capabilities_after_command']['exit_code'] = 0;
        self::assertFalse(\validateNativeDbusDaemonLossEvidence($evidence)['validated']);
    }

    private function runtimeKillEvidence(): array
    {
        return [
            'native_extension' => true,
            'before' => ['request_id' => 'failure-before', 'error' => null, 'result' => ['method' => 'system.capabilities']],
            'owner_before' => "(':1.3',)",
            'parent_pid' => 4242,
            'runtime_kill_sent' => true,
            'runtime_running_after_kill' => false,
            'runtime_dead' => true,
            'owner_check_after' => ['exit_code' => 0, 'stdout' => '(false,)'],
            'capabilities_after_command' => ['exit_code' => 2],
            'runtime_after_command' => ['exit_code' => 2],
        ];
    }

    public function testRuntimeKillRequiresAllEvidenceKeys(): void
    {
        self::assertTrue(\validateNativeDbusRuntimeKillEvidence($this->runtimeKillEvidence())['validated']);
        foreach (array_keys($this->runtimeKillEvidence()) as $key) {
            $evidence = $this->runtimeKillEvidence();
            unset($evidence[$key]);
            self::assertFalse(\validateNativeDbusRuntimeKillEvidence($evidence)['validated'], $key);
        }
    }

    public function testRuntimeKillRejectsSurvivingOwnerOrSuccessfulCalls(): void
    {
        $evidence = $this->runtimeKillEvidence();
        $evidence['runtime_dead'] = false;
        self::assertFalse(\validateNativeDbusRuntimeKillEvidence($evidence)['validated']);

        $evidence = $this->runtimeKillEvidence();
        $evidence['owner_check_after']['stdout'] = '(true,)';
        self::assertFalse(\validateNativeDbusRuntimeKillEvidence($evidence)['validated']);

        $evidence = $this->runtimeKillEvidence();
        $evidence['capabilities_after_command']['exit_code'] = 0;
        self::assertFalse(\validateNativeDbusRuntimeKillEvidence($evidence)['validated']);
    }
}
