<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Merchant;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantAppOnboardingStatusChangedTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use Spryker\Zed\MerchantApp\Business\MerchantAppOnboarding\MerchantAppOnboardingStatusInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandler;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use Stripe\Event;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Merchant
 * @group MerchantOnboardingHandlerTest
 */
class MerchantOnboardingHandlerTest extends Unit
{
    protected const string MERCHANT_REFERENCE = 'MER-001';

    protected const string ACCOUNT_ID = 'acct_test_xyz';

    public function testHandleAccountUpdatedSkipsWhenObjectIsNotAccount(): void
    {
        // Arrange
        $merchantAppFacadeMock = $this->createMock(MerchantAppFacadeInterface::class);
        $merchantAppFacadeMock->expects($this->never())->method('handleMerchantAppOnboardingStatusChanged');

        $event = $this->buildEvent(['object' => 'payment_intent']);

        // Act
        $response = $this->createHandler($merchantAppFacadeMock)->handleAccountUpdated(
            new StripeWebhookProcessResponseTransfer(),
            $event,
        );

        $this->assertTrue($response->getIsSuccessful());
    }

    public function testHandleAccountUpdatedSkipsWhenMerchantReferenceIsMissing(): void
    {
        // Arrange
        $merchantAppFacadeMock = $this->createMock(MerchantAppFacadeInterface::class);
        $merchantAppFacadeMock->expects($this->never())->method('handleMerchantAppOnboardingStatusChanged');

        $event = $this->buildAccountEvent([], []);

        // Act
        $response = $this->createHandler($merchantAppFacadeMock)->handleAccountUpdated(
            new StripeWebhookProcessResponseTransfer(),
            $event,
        );

        $this->assertTrue($response->getIsSuccessful());
    }

    public function testHandleAccountUpdatedSetsMerchantReferenceAppIdentifierStatusAndType(): void
    {
        // Arrange — fully enabled account
        $capturedTransfer = null;
        $merchantAppFacadeMock = $this->createMock(MerchantAppFacadeInterface::class);
        $merchantAppFacadeMock
            ->expects($this->once())
            ->method('handleMerchantAppOnboardingStatusChanged')
            ->with($this->callback(function (MerchantAppOnboardingStatusChangedTransfer $transfer) use (&$capturedTransfer): bool {
                $capturedTransfer = $transfer;

                return true;
            }));

        $event = $this->buildAccountEvent(
            ['merchantReference' => static::MERCHANT_REFERENCE],
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
        );

        // Act
        $this->createHandler($merchantAppFacadeMock)->handleAccountUpdated(
            new StripeWebhookProcessResponseTransfer(),
            $event,
        );

        // Assert
        $this->assertInstanceOf(MerchantAppOnboardingStatusChangedTransfer::class, $capturedTransfer);
        $this->assertSame(static::MERCHANT_REFERENCE, $capturedTransfer->getMerchantReference());
        $this->assertSame(SharedStripeConfig::PAYMENT_PROVIDER_NAME, $capturedTransfer->getAppIdentifier());
        $this->assertSame(SharedStripeConfig::ONBOARDING_TYPE, $capturedTransfer->getType());
        $this->assertNotNull($capturedTransfer->getStatus());
    }

    public function testResolveStatusReturnsRejectedWhenDisabledReasonContainsRejected(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::REJECTED,
            ['charges_enabled' => true, 'payouts_enabled' => true],
            ['disabled_reason' => 'rejected.fraud'],
        );
    }

    public function testResolveStatusReturnsPendingWhenPendingVerificationIsNotEmpty(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::PENDING,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
            ['pending_verification' => ['document']],
        );
    }

    public function testResolveStatusReturnsPendingWhenTransfersCapabilityIsNotActive(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::PENDING,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'pending']],
            [],
        );
    }

    public function testResolveStatusReturnsRestrictedWhenPastDueIsNotEmpty(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::RESTRICTED,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
            ['past_due' => ['verification.document']],
        );
    }

    public function testResolveStatusReturnsCompletedWhenFullyEnabledWithNoEventuallyDue(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::COMPLETED,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
            [],
        );
    }

    public function testResolveStatusReturnsEnabledWhenEventuallyDueIsNotEmpty(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::ENABLED,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
            ['eventually_due' => ['business_profile.url']],
        );
    }

    public function testResolveStatusReturnsRestrictedWhenChargesDisabled(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::RESTRICTED,
            ['charges_enabled' => false, 'payouts_enabled' => false],
            [],
        );
    }

    public function testResolveStatusReturnsRestrictedWhenCurrentlyDueIsNotEmpty(): void
    {
        $this->assertResolvedStatus(
            MerchantAppOnboardingStatusInterface::RESTRICTED,
            ['charges_enabled' => true, 'payouts_enabled' => true, 'capabilities' => ['transfers' => 'active']],
            ['currently_due' => ['verification.document']],
        );
    }

    /**
     * @param array<string, mixed> $accountFields
     * @param array<string, mixed> $requirements
     */
    protected function assertResolvedStatus(string $expectedStatus, array $accountFields, array $requirements): void
    {
        $capturedStatus = null;
        $merchantAppFacadeMock = $this->createMock(MerchantAppFacadeInterface::class);
        $merchantAppFacadeMock
            ->method('handleMerchantAppOnboardingStatusChanged')
            ->with($this->callback(function (MerchantAppOnboardingStatusChangedTransfer $transfer) use (&$capturedStatus): bool {
                $capturedStatus = $transfer->getStatus();

                return true;
            }));

        $event = $this->buildAccountEvent(
            ['merchantReference' => static::MERCHANT_REFERENCE],
            array_merge($accountFields, $requirements ? ['requirements' => $requirements] : []),
        );

        $this->createHandler($merchantAppFacadeMock)->handleAccountUpdated(
            new StripeWebhookProcessResponseTransfer(),
            $event,
        );

        $this->assertSame($expectedStatus, $capturedStatus);
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $accountFields
     */
    protected function buildAccountEvent(array $metadata, array $accountFields): Event
    {
        $defaults = [
            'id' => static::ACCOUNT_ID,
            'object' => 'account',
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'details_submitted' => false,
            'capabilities' => [],
            'requirements' => [],
            'metadata' => $metadata,
        ];

        return $this->buildEvent(array_merge($defaults, $accountFields));
    }

    /**
     * @param array<string, mixed> $objectData
     */
    protected function buildEvent(array $objectData): Event
    {
        /** @var \Stripe\Event $event */
        $event = Event::constructFrom([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => Event::ACCOUNT_UPDATED,
            'data' => [
                'object' => $objectData,
            ],
        ]);

        return $event;
    }

    protected function createHandler(?MerchantAppFacadeInterface $merchantAppFacade = null): MerchantOnboardingHandler
    {
        return new MerchantOnboardingHandler(
            $merchantAppFacade ?? $this->createMock(MerchantAppFacadeInterface::class),
            $this->createMock(StripeEntityManagerInterface::class),
        );
    }
}
