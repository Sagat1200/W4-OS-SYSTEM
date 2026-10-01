<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Installer\EditionPolicyToolkit;
use W4\OS\Support\ValidationError;

final class EditionPolicyToolkitTest extends TestCase
{
    public function testNormalizeForProfileUsesEditionDefaultsWhenPolicyIsMissing(): void
    {
        $toolkit = new EditionPolicyToolkit();

        $policy = $toolkit->normalizeForProfile(
            'w4-os-home',
            ['edition' => 'home'],
            [],
            'edition-policy.json'
        );

        self::assertSame('edition-policy.json', $policy['path']);
        self::assertSame('Home', $policy['branding']['edition']);
        self::assertSame('w4-home', $policy['branding']['hostname_prefix']);
        self::assertSame('graphical.target', $policy['boot']['default_target']);
        self::assertFalse($policy['ssh']['enabled']);
        self::assertSame('disabled', $policy['ssh']['authentication']);
        self::assertSame('ufw', $policy['firewall']['backend']);
        self::assertSame('deny', $policy['firewall']['incoming']);
        self::assertSame('allow', $policy['firewall']['outgoing']);
    }

    public function testNormalizeFromPlanPreservesServerPolicyAndCompactView(): void
    {
        $toolkit = new EditionPolicyToolkit();

        $normalized = $toolkit->normalizeFromPlan([
            'profile_id' => 'w4-os-server',
            'installation_profile' => [
                'edition' => 'server',
            ],
            'edition_policy' => [
                'profile_id' => 'w4-os-server',
                'path' => 'edition-policy.json',
                'branding' => [
                    'edition' => 'Server',
                    'hostname_prefix' => 'w4-server',
                ],
                'boot' => [
                    'default_target' => 'multi-user.target',
                    'firmware' => 'uefi',
                ],
                'ssh' => [
                    'enabled' => true,
                    'root_login' => false,
                    'authentication' => 'publickey',
                ],
                'firewall' => [
                    'backend' => 'ufw',
                    'incoming' => 'deny',
                    'outgoing' => 'allow',
                ],
            ],
        ]);

        $compact = $toolkit->compactRuntimeView($normalized, '../edition-policy.json');

        self::assertSame('w4-server', $normalized['branding']['hostname_prefix']);
        self::assertTrue($normalized['ssh']['enabled']);
        self::assertSame('publickey', $normalized['ssh']['authentication']);
        self::assertSame('../edition-policy.json', $compact['path']);
        self::assertSame('multi-user.target', $compact['default_target']);
        self::assertSame('w4-server', $compact['hostname_prefix']);
        self::assertTrue($compact['ssh_enabled']);
    }

    public function testNormalizeForProfileRejectsPolicyFromAnotherProfile(): void
    {
        $toolkit = new EditionPolicyToolkit();

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('La politica de edicion no corresponde al profile_id w4-os-home');

        $toolkit->normalizeForProfile(
            'w4-os-home',
            ['edition' => 'home'],
            [
                'profile_id' => 'w4-os-business',
            ]
        );
    }
}
