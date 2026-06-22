<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Payment;

use ArrayObject;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\PaymentExtension\Dependency\Plugin\PaymentMethodFilterPluginInterface;
use SprykerEco\Shared\Stripe\StripeConfig;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripePaymentMethodFilterPlugin extends AbstractPlugin implements PaymentMethodFilterPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function filterPaymentMethods(
        PaymentMethodsTransfer $paymentMethodsTransfer,
        QuoteTransfer $quoteTransfer,
    ): PaymentMethodsTransfer {
        if ($this->hasApiCredentials()) {
            return $paymentMethodsTransfer;
        }

        return $this->removeStripePaymentMethods($paymentMethodsTransfer);
    }

    protected function hasApiCredentials(): bool
    {
        return $this->getConfig()->getSecretKey() !== '' && $this->getConfig()->getPublishableKey() !== '';
    }

    protected function removeStripePaymentMethods(PaymentMethodsTransfer $paymentMethodsTransfer): PaymentMethodsTransfer
    {
        $filteredMethods = new ArrayObject();

        foreach ($paymentMethodsTransfer->getMethods() as $paymentMethodTransfer) {
            if (!$this->isStripePaymentMethod($paymentMethodTransfer)) {
                $filteredMethods->append($paymentMethodTransfer);
            }
        }

        return $paymentMethodsTransfer->setMethods($filteredMethods);
    }

    protected function isStripePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer): bool
    {
        // Infrastructural payment methods (present in OMS config but absent from spy_payment_method)
        // have no paymentProvider — match them by paymentMethodKey.
        if (strtolower((string)$paymentMethodTransfer->getPaymentMethodKey()) === strtolower(StripeConfig::PAYMENT_METHOD_NAME)) {
            return true;
        }

        $paymentProvider = $paymentMethodTransfer->getPaymentProvider();

        if ($paymentProvider === null) {
            return false;
        }

        return strtolower((string)$paymentProvider->getPaymentProviderKey()) === strtolower(StripeConfig::PAYMENT_PROVIDER_NAME);
    }
}
