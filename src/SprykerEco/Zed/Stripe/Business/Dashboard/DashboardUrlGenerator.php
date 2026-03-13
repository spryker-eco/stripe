<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Dashboard;

use SprykerEco\Zed\Stripe\Business\Stripe\StripeLoginLinksInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

class DashboardUrlGenerator implements DashboardUrlGeneratorInterface
{
    public function __construct(
        protected StripeRepositoryInterface $repository,
        protected StripeLoginLinksInterface $stripeLoginLinks,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generateDashboardUrl(string $merchantReference): ?string
    {
        $stripeMerchantTransfer = $this->repository->findMerchantByReference($merchantReference);

        if ($stripeMerchantTransfer === null || $stripeMerchantTransfer->getStripeAccountId() === null) {
            return null;
        }

        return $this->stripeLoginLinks->create($stripeMerchantTransfer->getStripeAccountId());
    }
}
