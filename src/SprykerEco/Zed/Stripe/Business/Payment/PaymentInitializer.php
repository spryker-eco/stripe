<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentInitializer implements PaymentInitializerInterface
{
    public function __construct(
        protected StripeIntentsInterface $stripeIntents,
        protected StripeConfig $config,
    ) {
    }

    public function initializePayment(QuoteTransfer $quoteTransfer): StripeIntentResponseTransfer
    {
        $stripeIntentRequestTransfer = (new StripeIntentRequestTransfer())
            ->setQuote($quoteTransfer);

        $response = $this->stripeIntents->create($stripeIntentRequestTransfer);
        $response->setPublishableKey($this->config->getPublishableKey());

        return $response;
    }
}
