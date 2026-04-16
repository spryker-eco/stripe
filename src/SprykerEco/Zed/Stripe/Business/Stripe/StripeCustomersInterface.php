<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeCustomerRequestTransfer;
use Generated\Shared\Transfer\StripeCustomerResponseTransfer;

interface StripeCustomersInterface
{
    public function searchOrCreate(StripeCustomerRequestTransfer $stripeCustomerRequestTransfer): StripeCustomerResponseTransfer;
}
