<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\OnboardingTransfer;
use Generated\Shared\Transfer\ReadyForMerchantAppOnboardingTransfer;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class MerchantOnboardingRegistrar
{
    public function __construct(
        protected MerchantAppFacadeInterface $merchantAppFacade,
        protected StripeConfig $config,
    ) {
    }

    /**
     * Registers Stripe as a ready-to-use merchant onboarding provider.
     * Stores strategy='redirect' and the initialize endpoint URL in the MerchantApp module.
     */
    public function register(): void
    {
        $onboardingTransfer = (new OnboardingTransfer())
            ->setStrategy('redirect')
            ->setUrl($this->config->getMerchantOnboardingInitializeUrl());

        $readyTransfer = (new ReadyForMerchantAppOnboardingTransfer())
            ->setType($this->config->getMerchantOnboardingType())
            ->setAppIdentifier($this->config->getMerchantOnboardingAppIdentifier())
            ->setOnboarding($onboardingTransfer);

        $this->merchantAppFacade->handleReadyForMerchantAppOnboarding($readyTransfer);
    }
}
