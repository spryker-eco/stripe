<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Executor;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface CheckoutPostSaveExecutorInterface
{
    public function executeCheckoutPostSaveHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void;
}
