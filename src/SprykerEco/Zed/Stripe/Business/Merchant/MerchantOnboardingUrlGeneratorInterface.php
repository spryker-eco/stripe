<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\StripeAccountLinksResponseTransfer;

interface MerchantOnboardingUrlGeneratorInterface
{
    /**
     * Generates a Stripe Connect onboarding URL for the given merchant.
     * Creates a connected account if none exists, then returns an account link response.
     * Check isSuccessful and message on the response for error details.
     */
    public function generateOnboardingUrl(string $merchantReference, string $returnUrl, string $refreshUrl): StripeAccountLinksResponseTransfer;
}
