<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Communication\Plugin\Payment;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentProviderTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\Stripe\Communication\Plugin\Payment\StripePaymentMethodFilterPlugin;
use SprykerEco\Zed\Stripe\StripeConfig;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Communication
 * @group Plugin
 * @group Payment
 * @group StripePaymentMethodFilterPluginTest
 */
class StripePaymentMethodFilterPluginTest extends Unit
{
    protected const string SECRET_KEY = 'sk_test_abc123';

    protected const string PUBLISHABLE_KEY = 'pk_test_abc123';

    protected const string STRIPE_PROVIDER_KEY = 'stripe';

    protected const string OTHER_PROVIDER_KEY = 'paypal';

    protected const int PAYMENT_METHOD_ID = 1;

    public function testFilterPaymentMethodsReturnsAllMethodsWhenCredentialsArePresent(): void
    {
        // Arrange
        $plugin = $this->createPlugin(secretKey: static::SECRET_KEY, publishableKey: static::PUBLISHABLE_KEY);
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            $this->buildPaymentMethod(static::STRIPE_PROVIDER_KEY, static::PAYMENT_METHOD_ID),
            $this->buildPaymentMethod(static::OTHER_PROVIDER_KEY),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(2, $result->getMethods());
    }

    public function testFilterPaymentMethodsRemovesStripeMethodWhenSecretKeyIsEmpty(): void
    {
        // Arrange
        $plugin = $this->createPlugin(secretKey: '', publishableKey: static::PUBLISHABLE_KEY);
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            $this->buildPaymentMethod(static::STRIPE_PROVIDER_KEY, static::PAYMENT_METHOD_ID),
            $this->buildPaymentMethod(static::OTHER_PROVIDER_KEY),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(1, $result->getMethods());
        $this->assertSame(static::OTHER_PROVIDER_KEY, $result->getMethods()[0]->getPaymentProvider()->getPaymentProviderKey());
    }

    public function testFilterPaymentMethodsRemovesStripeMethodWhenPublishableKeyIsEmpty(): void
    {
        // Arrange
        $plugin = $this->createPlugin(secretKey: static::SECRET_KEY, publishableKey: '');
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            $this->buildPaymentMethod(static::STRIPE_PROVIDER_KEY, static::PAYMENT_METHOD_ID),
            $this->buildPaymentMethod(static::OTHER_PROVIDER_KEY),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(1, $result->getMethods());
        $this->assertSame(static::OTHER_PROVIDER_KEY, $result->getMethods()[0]->getPaymentProvider()->getPaymentProviderKey());
    }

    public function testFilterPaymentMethodsKeepsNonStripeMethodsWhenBothCredentialsAreEmpty(): void
    {
        // Arrange
        $plugin = $this->createPlugin(secretKey: '', publishableKey: '');
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            $this->buildPaymentMethod(static::STRIPE_PROVIDER_KEY, static::PAYMENT_METHOD_ID),
            $this->buildPaymentMethod(static::OTHER_PROVIDER_KEY),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(1, $result->getMethods());
        $this->assertSame(static::OTHER_PROVIDER_KEY, $result->getMethods()[0]->getPaymentProvider()->getPaymentProviderKey());
    }

    public function testFilterPaymentMethodsRemovesUnregisteredStripeMethodRegardlessOfCredentials(): void
    {
        // Arrange — Stripe method with no idPaymentMethod (not registered in Backoffice)
        $plugin = $this->createPlugin(secretKey: static::SECRET_KEY, publishableKey: static::PUBLISHABLE_KEY);
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            $this->buildPaymentMethod(static::STRIPE_PROVIDER_KEY),
            $this->buildPaymentMethod(static::OTHER_PROVIDER_KEY),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(1, $result->getMethods());
        $this->assertSame(static::OTHER_PROVIDER_KEY, $result->getMethods()[0]->getPaymentProvider()->getPaymentProviderKey());
    }

    public function testFilterPaymentMethodsKeepsMethodWithoutPaymentProviderWhenCredentialsAreEmpty(): void
    {
        // Arrange
        $plugin = $this->createPlugin(secretKey: '', publishableKey: '');
        $paymentMethodsTransfer = $this->buildPaymentMethodsTransfer([
            new PaymentMethodTransfer(),
        ]);

        // Act
        $result = $plugin->filterPaymentMethods($paymentMethodsTransfer, new QuoteTransfer());

        // Assert
        $this->assertCount(1, $result->getMethods());
    }

    protected function createPlugin(string $secretKey, string $publishableKey): StripePaymentMethodFilterPlugin
    {
        $configMock = $this->createMock(StripeConfig::class);
        $configMock->method('getSecretKey')->willReturn($secretKey);
        $configMock->method('getPublishableKey')->willReturn($publishableKey);

        $plugin = $this->getMockBuilder(StripePaymentMethodFilterPlugin::class)
            ->onlyMethods(['getConfig'])
            ->getMock();
        $plugin->method('getConfig')->willReturn($configMock);

        return $plugin;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $methods
     */
    protected function buildPaymentMethodsTransfer(array $methods): PaymentMethodsTransfer
    {
        return (new PaymentMethodsTransfer())->setMethods(new ArrayObject($methods));
    }

    protected function buildPaymentMethod(string $providerKey, ?int $idPaymentMethod = null): PaymentMethodTransfer
    {
        $paymentProvider = (new PaymentProviderTransfer())->setPaymentProviderKey($providerKey);

        return (new PaymentMethodTransfer())
            ->setPaymentProvider($paymentProvider)
            ->setIdPaymentMethod($idPaymentMethod);
    }
}
