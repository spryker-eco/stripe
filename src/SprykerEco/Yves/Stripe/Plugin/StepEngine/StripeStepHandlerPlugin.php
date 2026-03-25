<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Plugin\StepEngine;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\StepEngine\Dependency\Plugin\Handler\StepHandlerPluginInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class StripeStepHandlerPlugin extends AbstractPlugin implements StepHandlerPluginInterface
{
    /**
     * Sets payment provider, method, and selection on the quote.
     * The transactionId (PaymentIntent ID) is already bound to quoteTransfer->getPayment()->getStripe()
     * by the Symfony form system via StripeSubForm::getPropertyPath().
     */
    public function addToDataClass(Request $request, AbstractTransfer $quoteTransfer): QuoteTransfer
    {
        $paymentTransfer = $quoteTransfer->getPayment();

        if ($paymentTransfer === null) {
            return $quoteTransfer;
        }

        $paymentTransfer
            ->setPaymentProvider(SharedStripeConfig::PAYMENT_PROVIDER_NAME)
            ->setPaymentMethod(SharedStripeConfig::PAYMENT_METHOD_NAME)
            ->setPaymentSelection(SharedStripeConfig::PAYMENT_METHOD_NAME);

        return $quoteTransfer;
    }
}
