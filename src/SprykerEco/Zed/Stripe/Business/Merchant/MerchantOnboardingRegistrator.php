<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\LinkTransfer;
use Generated\Shared\Transfer\MerchantOnboardingStateTransfer;
use Generated\Shared\Transfer\OnboardingTransfer;
use Generated\Shared\Transfer\ReadyForMerchantAppOnboardingTransfer;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\StripeConfig;

class MerchantOnboardingRegistrator implements MerchantOnboardingRegistratorInterface
{
    // Placeholder replaced by PaymentSettingsController::prepareAdditionalLinks() at render time
    protected const string MERCHANT_REFERENCE_PLACEHOLDER = '_merchantReference_';

    protected const string LOCALE_PLACEHOLDER = '_locale_';

    protected const string DASHBOARD_URL_PATH = '/stripe/dashboard';

    protected const string ONBOARDING_INITIALIZE_URL_PATH = '/stripe/onboarding/initialize';

    public function __construct(
        protected MerchantAppFacadeInterface $merchantAppFacade,
        protected StripeConfig $config,
    ) {
    }

    public function register(): void
    {
        // 'redirect' strategy: Merchant Portal redirects the browser to our controller; session cookie gives us the logged-in merchant
        $onboardingTransfer = (new OnboardingTransfer())
            ->setStrategy(SharedStripeConfig::ONBOARDING_STRATEGY_REDIRECT)
            ->setUrl(static::ONBOARDING_INITIALIZE_URL_PATH);

        $readyTransfer = (new ReadyForMerchantAppOnboardingTransfer())
            ->setType(SharedStripeConfig::ONBOARDING_TYPE)
            ->setAppName(StripeConfig::APP_NAME)
            ->setAppIdentifier(strtolower(StripeConfig::APP_NAME))
            ->setOnboarding($onboardingTransfer);

        foreach ($this->config->getMerchantOnboardingStates() as $stateName => $attributes) {
            $stateTransfer = (new MerchantOnboardingStateTransfer())
                ->setName($stateName);

            foreach ($attributes as $key => $value) {
                $stateTransfer->addAttribute($key, $value);
            }

            $readyTransfer->addMerchantOnboardingState($stateTransfer);
        }

        $dashboardLink = (new LinkTransfer())
            ->setLabel('Quick link to Stripe Express Dashboard')
            ->setUrl($this->buildDashboardUrl())
            ->addAttribute('target', '_self');

        $readyTransfer->addAdditionalLink($dashboardLink);

        $this->merchantAppFacade->createMerchantAppOnboarding($readyTransfer);
    }

    protected function buildDashboardUrl(): string
    {
        return sprintf(
            '%s?%s',
            static::DASHBOARD_URL_PATH,
            http_build_query([
                'merchantReference' => static::MERCHANT_REFERENCE_PLACEHOLDER,
                'locale' => static::LOCALE_PLACEHOLDER,
            ]),
        );
    }
}
