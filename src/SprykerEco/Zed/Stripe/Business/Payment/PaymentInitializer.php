<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;

class PaymentInitializer
{
    public function __construct(protected StripeIntents $stripeIntents)
    {
    }

    public function initializePayment(QuoteTransfer $quoteTransfer): StripeIntentResponseTransfer
    {
        $stripeIntentRequestTransfer = (new StripeIntentRequestTransfer())
            ->setQuote($quoteTransfer);

        return $this->stripeIntents->create($stripeIntentRequestTransfer);
    }
}
