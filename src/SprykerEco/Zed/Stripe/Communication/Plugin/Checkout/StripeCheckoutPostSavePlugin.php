<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Checkout;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\CheckoutExtension\Dependency\Plugin\CheckoutPostSaveInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeCheckoutPostSavePlugin extends AbstractPlugin implements CheckoutPostSaveInterface
{
    /**
     * {@inheritDoc}
     * - Reads orderReference and idSalesOrder from checkoutResponseTransfer->saveOrder.
     * - Sets orderReference on quoteTransfer so the PaymentIntent description/metadata is correct.
     * - Creates a Stripe PaymentIntent via StripeFacade::initializePayment() with the final grand total.
     * - Calls StripeFacade::savePayment() with the returned transactionId.
     * - On success: sets isExternalRedirect=true and redirectUrl to the Yves Stripe payment page.
     * - On failure: adds a checkout error and sets isSuccess=false.
     *
     * @api
     */
    public function executeHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $saveOrderTransfer = $checkoutResponseTransfer->getSaveOrderOrFail();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();

        $quoteTransfer->setOrderReference($orderReference);

        $intentResponse = $this->getFacade()->initializePayment($quoteTransfer);

        if (!$intentResponse->getIsSuccessful()) {
            $checkoutResponseTransfer
                ->setIsSuccess(false)
                ->addError(
                    (new CheckoutErrorTransfer())
                        ->setMessage('Stripe payment initialization failed. Please try again.'),
                );

            return;
        }

        $this->getFacade()->savePayment(
            $quoteTransfer,
            $saveOrderTransfer,
            $intentResponse->getTransactionIdOrFail(),
        );

        // TODO: use constants and ref to \SprykerEco\Yves\Stripe\Plugin\Router\StripeRouteProviderPlugin
        $redirectUrl = '/stripe/payment?orderReference=' . rawurlencode($orderReference);

        $checkoutResponseTransfer
            ->setIsExternalRedirect(true)
            ->setRedirectUrl($redirectUrl);
    }
}
