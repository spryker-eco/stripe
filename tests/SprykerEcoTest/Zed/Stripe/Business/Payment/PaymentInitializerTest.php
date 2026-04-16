<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentInitializer;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentInitializerTest
 */
class PaymentInitializerTest extends Unit
{
    protected const PUBLISHABLE_KEY = 'pk_test_abc123';

    protected const CLIENT_SECRET = 'pi_test_secret';

    protected const TRANSACTION_ID = 'pi_test_123';

    public function testInitializePaymentCallsStripeIntentsCreateAndSetsPublishableKey(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();

        $expectedResponse = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId(static::TRANSACTION_ID)
            ->setClientSecret(static::CLIENT_SECRET);

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (StripeIntentRequestTransfer $request) use ($quoteTransfer): bool {
                return $request->getQuote() === $quoteTransfer;
            }))
            ->willReturn($expectedResponse);

        $configMock = $this->createMock(StripeConfig::class);
        $configMock->method('getPublishableKey')->willReturn(static::PUBLISHABLE_KEY);

        $initializer = new PaymentInitializer($stripeIntentsMock, $configMock);

        // Act
        $response = $initializer->initializePayment($quoteTransfer);

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame(static::PUBLISHABLE_KEY, $response->getPublishableKey());
        $this->assertSame(static::TRANSACTION_ID, $response->getTransactionId());
    }

    public function testInitializePaymentSetsPublishableKeyEvenOnFailedStripeResponse(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();

        $failedResponse = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(false)
            ->setMessage('Stripe API error');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('create')->willReturn($failedResponse);

        $configMock = $this->createMock(StripeConfig::class);
        $configMock->method('getPublishableKey')->willReturn(static::PUBLISHABLE_KEY);

        $initializer = new PaymentInitializer($stripeIntentsMock, $configMock);

        // Act
        $response = $initializer->initializePayment($quoteTransfer);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(static::PUBLISHABLE_KEY, $response->getPublishableKey());
    }
}
