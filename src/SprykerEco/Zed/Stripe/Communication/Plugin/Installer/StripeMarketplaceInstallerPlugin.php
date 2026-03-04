<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Installer;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Installer\Dependency\Plugin\InstallerPluginInterface;

/**
 * Registers Stripe as a merchant onboarding provider in the MerchantApp module.
 * Install by adding to InstallerDependencyProvider::getInstallerPlugins().
 * Requires STRIPE:STRIPE_MERCHANT_ONBOARDING_INITIALIZE_URL to be configured.
 *
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeMarketplaceInstallerPlugin extends AbstractPlugin implements InstallerPluginInterface
{
    /**
     * {@inheritDoc}
     * - Calls MerchantAppFacade::handleReadyForMerchantAppOnboarding() to register Stripe onboarding.
     * - Strategy is 'redirect'; URL is read from StripeConfig::getMerchantOnboardingInitializeUrl().
     *
     * @api
     */
    public function runInstaller(): void
    {
        $this->getFacade()->registerMerchantOnboarding();
    }
}
