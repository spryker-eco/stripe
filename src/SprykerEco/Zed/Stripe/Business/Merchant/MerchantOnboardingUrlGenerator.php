<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccountLinks;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccounts;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class MerchantOnboardingUrlGenerator
{
    public function __construct(
        protected StripeAccounts $stripeAccounts,
        protected StripeAccountLinks $stripeAccountLinks,
        protected StripeEntityManagerInterface $entityManager,
        protected StripeRepositoryInterface $repository,
        protected StripeConfig $config,
    ) {
    }

    /**
     * Generates a Stripe Connect onboarding URL for the given merchant.
     * Creates a connected account if none exists, then returns an account link URL.
     * Full implementation in Phase 13.
     */
    public function generateOnboardingUrl(string $merchantReference): string
    {
        // TODO: Phase 13 — implement Stripe Connect account creation + account link generation
        return '';
    }
}
