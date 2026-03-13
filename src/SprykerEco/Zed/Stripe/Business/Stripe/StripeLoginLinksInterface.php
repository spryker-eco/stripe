<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

interface StripeLoginLinksInterface
{
    /**
     * Creates a single-use login link for a connected Stripe account's Express Dashboard.
     * Returns null and logs an error if the Stripe API call fails.
     */
    public function create(string $stripeAccountId): ?string;
}
