<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Webhook;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentAppPaymentStatusCollectionTransfer;
use Generated\Shared\Transfer\PaymentAppPaymentStatusTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentPartiallyCapturedTransfer;
use Generated\Shared\Transfer\PaymentPartiallyRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use Spryker\Zed\SalesPaymentDetail\Business\SalesPaymentDetailFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Merchant\MerchantOnboardingHandlerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Webhook\StripeEventDetailsExtractorInterface;
use SprykerEco\Zed\Stripe\Business\Webhook\WebhookHandler;
use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\Event;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Webhook
 * @group WebhookHandlerTest
 */
class WebhookHandlerTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const TRANSACTION_ID = 'pi_test_abc123';

    // -------------------------------------------------------------------------
    // payment_intent.amount_capturable_updated → PaymentAuthorizedTransfer
    // -------------------------------------------------------------------------

    public function testAmountCapturableUpdatedSavesAuthorizedStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentAuthorizedTransfer::class));

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED,
            ['status' => 'requires_capture', 'metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testAmountCapturableUpdatedSkipsWhenStatusIsNotRequiresCapture(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED,
            ['status' => 'requires_payment_method', 'metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testAmountCapturableUpdatedSkipsWhenOrderReferenceIsMissing(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED,
            ['status' => 'requires_capture', 'metadata' => []],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // payment_intent.succeeded → PaymentCapturedTransfer (full) or PaymentPartiallyCapturedTransfer
    // -------------------------------------------------------------------------

    public function testPaymentSucceededSavesCapturedStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCapturedTransfer::class));

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_SUCCEEDED,
            [
                'metadata' => ['orderReference' => static::ORDER_REFERENCE],
                'amount' => 10000,
                'amount_received' => 10000,
            ],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testPaymentSucceededSavesPartiallyCapturedStatusWhenAmountReceivedLessThanAmount(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentPartiallyCapturedTransfer::class));

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_SUCCEEDED,
            [
                'metadata' => ['orderReference' => static::ORDER_REFERENCE],
                'amount' => 10000,
                'amount_received' => 8000, // less than authorized amount
            ],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // payment_intent.payment_failed → PaymentCaptureFailedTransfer (unless NEW state)
    // -------------------------------------------------------------------------

    public function testPaymentFailedSavesCaptureFailedStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCaptureFailedTransfer::class));

        // Payment is NOT in NEW state — status collection has authorized status
        $statusCollection = new PaymentAppPaymentStatusCollectionTransfer();
        $statusCollection->setPaymentAppPaymentStates(new ArrayObject([
            (new PaymentAppPaymentStatusTransfer())->setStatus(SharedStripeConfig::PAYMENT_STATUS_AUTHORIZED),
        ]));
        $paymentAppFacadeMock->method('getPaymentAppPaymentStatusCollection')->willReturn($statusCollection);

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_PAYMENT_FAILED,
            ['metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    /**
     * 3DS retry: when payment_intent.payment_failed arrives and payment is still in NEW state,
     * the customer can retry — do NOT update status to capture_failed.
     */
    public function testPaymentFailedDoesNotWriteStatusWhenPaymentIsInNewState(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        // Only NEW status in the collection
        $statusCollection = new PaymentAppPaymentStatusCollectionTransfer();
        $statusCollection->setPaymentAppPaymentStates(new ArrayObject([
            (new PaymentAppPaymentStatusTransfer())->setStatus(SharedStripeConfig::PAYMENT_STATUS_NEW),
        ]));
        $paymentAppFacadeMock->method('getPaymentAppPaymentStatusCollection')->willReturn($statusCollection);

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_PAYMENT_FAILED,
            ['metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // payment_intent.canceled → PaymentCanceledTransfer
    // -------------------------------------------------------------------------

    public function testPaymentIntentCanceledSavesCanceledStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCanceledTransfer::class));

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_CANCELED,
            ['metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // charge.refunded → PaymentRefundedTransfer (full) or PaymentPartiallyRefundedTransfer
    // -------------------------------------------------------------------------

    public function testChargeRefundedSavesFullRefundedStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentRefundedTransfer::class));

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByTransactionId')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        // Full refund: amount_refunded == amount
        $payload = $this->buildChargePayload(Event::CHARGE_REFUNDED, [
            'payment_intent' => static::TRANSACTION_ID,
            'amount' => 10000,
            'amount_refunded' => 10000,
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testChargeRefundedSavesPartiallyRefundedStatusWhenAmountRefundedLessThanAmount(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentPartiallyRefundedTransfer::class));

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByTransactionId')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        // Partial refund: amount_refunded < amount
        $payload = $this->buildChargePayload(Event::CHARGE_REFUNDED, [
            'payment_intent' => static::TRANSACTION_ID,
            'amount' => 10000,
            'amount_refunded' => 6000,
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // charge.refund.updated → PaymentRefundFailedTransfer (when status=failed)
    // -------------------------------------------------------------------------

    public function testRefundUpdatedSavesRefundFailedStatusWhenRefundFailed(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentRefundFailedTransfer::class));

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByTransactionId')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        $payload = $this->buildRefundPayload(Event::CHARGE_REFUND_UPDATED, [
            'payment_intent' => static::TRANSACTION_ID,
            'status' => 'failed',
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testRefundUpdatedDoesNotSaveStatusWhenRefundSucceeded(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByTransactionId')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        $payload = $this->buildRefundPayload(Event::CHARGE_REFUND_UPDATED, [
            'payment_intent' => static::TRANSACTION_ID,
            'status' => 'succeeded',
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // charge.failed (captured=true) → PaymentCaptureFailedTransfer
    // -------------------------------------------------------------------------

    public function testChargeFailedWithCapturedChargeSavesCaptureFailedStatus(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock
            ->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCaptureFailedTransfer::class));

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByTransactionId')->willReturn(
            (new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            paymentReader: $paymentReaderMock,
        );

        $payload = $this->buildChargePayload(Event::CHARGE_FAILED, [
            'payment_intent' => static::TRANSACTION_ID,
            'captured' => true,
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testChargeFailedWithUncapturedChargeIsIgnored(): void
    {
        // Arrange — charge.failed before capture is handled by payment_intent.payment_failed instead
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = $this->buildChargePayload(Event::CHARGE_FAILED, [
            'payment_intent' => static::TRANSACTION_ID,
            'captured' => false,
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // Unknown / unhandled event types
    // -------------------------------------------------------------------------

    public function testUnknownEventTypeIsHandledSuccessfullyWithoutStatusWrite(): void
    {
        // Arrange
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $handler = $this->createWebhookHandler(paymentAppFacade: $paymentAppFacadeMock);

        $payload = (string)json_encode([
            'object' => 'event',
            'type' => 'some.unknown.event',
            'data' => ['object' => []],
        ]);

        // Act
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // Signature verification
    // -------------------------------------------------------------------------

    public function testInvalidSignatureReturnsUnsuccessfulResponse(): void
    {
        // Arrange — real webhook secret configured, so signature check is active
        $configMock = $this->createStripeConfigMock('whsec_test_secret');
        $handler = $this->createWebhookHandler(config: $configMock);

        // Act — random payload with wrong signature
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())
                ->setRawPayload('{"object":"event","type":"test"}')
                ->setSignatureHeader('t=123,v1=invalidsig'),
        );

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertNotNull($response->getMessage());
    }

    public function testEmptyWebhookSecretSkipsSignatureVerification(): void
    {
        // Arrange — no webhook secret configured (local dev mode)
        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->once())->method('savePaymentAppPaymentStatus');

        $handler = $this->createWebhookHandler(
            paymentAppFacade: $paymentAppFacadeMock,
            config: $this->createStripeConfigMock(''),
        );

        $payload = $this->buildPaymentIntentPayload(
            Event::PAYMENT_INTENT_CANCELED,
            ['metadata' => ['orderReference' => static::ORDER_REFERENCE]],
        );

        // Act — signature header is irrelevant when secret is empty
        $response = $handler->processWebhook(
            (new StripeWebhookPayloadTransfer())->setRawPayload($payload)->setSignatureHeader(''),
        );

        // Assert
        $this->assertTrue($response->getIsSuccessful());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a minimal Stripe PaymentIntent event payload JSON.
     *
     * @param array<string, mixed> $paymentIntentData
     */
    protected function buildPaymentIntentPayload(string $eventType, array $paymentIntentData): string
    {
        $defaults = [
            'id' => static::TRANSACTION_ID,
            'object' => 'payment_intent',
            'status' => 'requires_capture',
            'amount' => 10000,
            'amount_received' => 10000,
            'amount_capturable' => 10000,
            'currency' => 'eur',
            'capture_method' => 'manual',
            'livemode' => false,
            'metadata' => ['orderReference' => static::ORDER_REFERENCE],
            'last_payment_error' => null,
            'cancellation_reason' => null,
            'canceled_at' => null,
            'created' => 1700000000,
            'customer' => null,
            'payment_method' => null,
            'latest_charge' => null,
            'description' => null,
        ];

        return (string)json_encode([
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => array_merge($defaults, $paymentIntentData),
            ],
        ]);
    }

    /**
     * Builds a minimal Stripe Charge event payload JSON.
     *
     * @param array<string, mixed> $chargeData
     */
    protected function buildChargePayload(string $eventType, array $chargeData): string
    {
        $defaults = [
            'id' => 'ch_test_123',
            'object' => 'charge',
            'status' => 'failed',
            'amount' => 10000,
            'amount_captured' => 0,
            'amount_refunded' => 0,
            'currency' => 'eur',
            'captured' => false,
            'livemode' => false,
            'created' => 1700000000,
            'payment_intent' => static::TRANSACTION_ID,
            'payment_method' => null,
            'customer' => null,
            'description' => null,
            'failure_code' => null,
            'failure_message' => null,
            'metadata' => [],
            'outcome' => null,
            'payment_method_details' => null,
        ];

        return (string)json_encode([
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => array_merge($defaults, $chargeData),
            ],
        ]);
    }

    /**
     * Builds a minimal Stripe Refund event payload JSON.
     *
     * @param array<string, mixed> $refundData
     */
    protected function buildRefundPayload(string $eventType, array $refundData): string
    {
        $defaults = [
            'id' => 're_test_123',
            'object' => 'refund',
            'status' => 'succeeded',
            'amount' => 10000,
            'currency' => 'eur',
            'charge' => 'ch_test_123',
            'payment_intent' => static::TRANSACTION_ID,
            'reason' => null,
            'failure_reason' => null,
            'created' => 1700000000,
        ];

        return (string)json_encode([
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => array_merge($defaults, $refundData),
            ],
        ]);
    }

    protected function createStripeConfigMock(string $webhookSecret): StripeConfig
    {
        $configMock = $this->createMock(StripeConfig::class);
        $configMock->method('getWebhookSecret')->willReturn($webhookSecret);

        return $configMock;
    }

    protected function createWebhookHandler(
        ?PaymentAppFacadeInterface $paymentAppFacade = null,
        ?PaymentReaderInterface $paymentReader = null,
        ?StripeConfig $config = null,
    ): WebhookHandler {
        if ($config === null) {
            $config = $this->createStripeConfigMock('');
        }

        if ($paymentReader === null) {
            $paymentReader = $this->createMock(PaymentReaderInterface::class);
        }

        if ($paymentAppFacade === null) {
            $paymentAppFacade = $this->createMock(PaymentAppFacadeInterface::class);
        }

        $merchantOnboardingHandlerMock = $this->createMock(MerchantOnboardingHandlerInterface::class);
        $salesPaymentDetailFacadeMock = $this->createMock(SalesPaymentDetailFacadeInterface::class);
        $eventDetailsExtractorMock = $this->createMock(StripeEventDetailsExtractorInterface::class);
        $eventDetailsExtractorMock->method('extractPaymentIntentDetails')->willReturn([]);
        $eventDetailsExtractorMock->method('extractChargeDetails')->willReturn([]);
        $eventDetailsExtractorMock->method('extractRefundDetails')->willReturn([]);

        return new WebhookHandler(
            $config,
            $paymentAppFacade,
            $paymentReader,
            $merchantOnboardingHandlerMock,
            $salesPaymentDetailFacadeMock,
            $eventDetailsExtractorMock,
        );
    }
}
