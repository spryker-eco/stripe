<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Dashboard;

interface DashboardUrlGeneratorInterface
{
    /**
     * Generates a single-use Stripe Express Dashboard login URL for the given merchant.
     * Returns null if the merchant has no connected Stripe account or if the Stripe API call fails.
     */
    public function generateDashboardUrl(string $merchantReference): ?string;
}
