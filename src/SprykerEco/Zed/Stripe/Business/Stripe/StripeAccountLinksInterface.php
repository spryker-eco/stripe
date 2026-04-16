<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeAccountLinksRequestTransfer;
use Generated\Shared\Transfer\StripeAccountLinksResponseTransfer;

interface StripeAccountLinksInterface
{
    public function create(StripeAccountLinksRequestTransfer $stripeAccountLinksRequestTransfer): StripeAccountLinksResponseTransfer;
}
