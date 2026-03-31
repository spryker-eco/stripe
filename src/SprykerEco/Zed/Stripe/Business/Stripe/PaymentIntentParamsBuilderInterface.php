<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeCustomerResponseTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;

interface PaymentIntentParamsBuilderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        QuoteTransfer $quoteTransfer,
        StripeCustomerResponseTransfer $stripeCustomerResponseTransfer,
        StripeIntentRequestTransfer $stripeIntentRequestTransfer,
    ): array;
}
