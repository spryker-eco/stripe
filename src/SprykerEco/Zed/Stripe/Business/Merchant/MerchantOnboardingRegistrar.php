<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\MerchantOnboardingStateTransfer;
use Generated\Shared\Transfer\OnboardingTransfer;
use Generated\Shared\Transfer\ReadyForMerchantAppOnboardingTransfer;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class MerchantOnboardingRegistrar implements MerchantOnboardingRegistrarInterface
{
    public function __construct(
        protected MerchantAppFacadeInterface $merchantAppFacade,
        protected StripeConfig $config,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        $onboardingTransfer = (new OnboardingTransfer())
            ->setStrategy('redirect')
            ->setUrl($this->config->getMerchantOnboardingInitializeUrl());

        $readyTransfer = (new ReadyForMerchantAppOnboardingTransfer())
            ->setType($this->config->getMerchantOnboardingType())
            ->setAppName(StripeConfig::APP_NAME)
            ->setAppIdentifier($this->config->getMerchantOnboardingAppIdentifier())
            ->setOnboarding($onboardingTransfer);

        foreach ($this->config->getMerchantOnboardingStates() as $stateName => $attributes) {
            $stateTransfer = (new MerchantOnboardingStateTransfer())
                ->setName($stateName);

            foreach ($attributes as $key => $value) {
                $stateTransfer->addAttribute($key, $value);
            }

            $readyTransfer->addMerchantOnboardingState($stateTransfer);
        }

        $this->merchantAppFacade->handleReadyForMerchantAppOnboarding($readyTransfer);
    }
}
