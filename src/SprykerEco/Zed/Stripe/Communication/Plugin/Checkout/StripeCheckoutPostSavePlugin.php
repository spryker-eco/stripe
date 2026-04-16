<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Checkout;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\CheckoutExtension\Dependency\Plugin\CheckoutPostSaveInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Business\StripeBusinessFactory getBusinessFactory()
 */
class StripeCheckoutPostSavePlugin extends AbstractPlugin implements CheckoutPostSaveInterface
{
    /**
     * {@inheritDoc}
     * - Reads orderReference and idSalesOrder from `CheckoutResponseTransfer.saveOrder`.
     * - Sets `QuoteTransfer.orderReference` so the PaymentIntent description/metadata is correct.
     * - Creates a Stripe PaymentIntent with the final grand total.
     * - On success sets `CheckoutResponseTransfer.isExternalRedirect` to true and `CheckoutResponseTransfer.redirectUrl` to the Yves Stripe payment page.
     * - On failure adds a checkout error and sets `CheckoutResponseTransfer.isSuccess` to false.
     *
     * @api
     */
    public function executeHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $this->getBusinessFactory()
            ->createCheckoutPostSaveExecutor()
            ->executeCheckoutPostSaveHook($quoteTransfer, $checkoutResponseTransfer);
    }
}
