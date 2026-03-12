<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;

interface PaymentInitializerInterface
{
    public function initializePayment(QuoteTransfer $quoteTransfer): StripeIntentResponseTransfer;
}
