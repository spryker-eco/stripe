<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe\Plugin\StepEngine;

use Generated\Shared\Transfer\StripeCreditCardTransfer;
use Generated\Shared\Transfer\StripeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\Kernel\Transfer\AbstractTransfer;
use SprykerEco\Yves\Kernel\AbstractPlugin;
use SprykerEco\Yves\StepEngine\Dependency\Plugin\Handler\StepHandlerPluginInterface;
use SprykerEco\Shared\Stripe\StripeConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class StripeCreditCardStepHandlerPlugin extends AbstractPlugin implements StepHandlerPluginInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addToDataClass(Request $request, AbstractTransfer $quoteTransfer): QuoteTransfer
    {
        $paymentTransfer = $quoteTransfer->getPayment();

        if ($paymentTransfer === null) {
            return $quoteTransfer;
        }

        $paymentTransfer->setPaymentProvider(StripeConfig::PAYMENT_PROVIDER_NAME);
        $paymentTransfer->setPaymentMethod(StripeConfig::PAYMENT_METHOD_CREDIT_CARD);
        $paymentTransfer->setPaymentSelection(StripeConfig::PAYMENT_METHOD_CREDIT_CARD);
        $paymentTransfer->setStripe(
            (new StripeTransfer()),
            //->setAmount(1000)
        );
        $paymentTransfer->setStripeCreditCard(
            (new StripeCreditCardTransfer()),
            //->setPaymentMethodToken('token')
        );

        return $quoteTransfer;
    }
}
