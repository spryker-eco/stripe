<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Installer;

use Spryker\Zed\InstallerExtension\Dependency\Plugin\InstallerPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeMarketplaceInstallerPlugin extends AbstractPlugin implements InstallerPluginInterface
{
    /**
     * {@inheritDoc}
     * - Calls {@link \Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface::handleReadyForMerchantAppOnboarding()} to register Stripe onboarding.
     * - Registers Stripe as a merchant onboarding provider in the MerchantApp module.
     *
     * @api
     */
    public function install(): void
    {
        $this->getFacade()->registerMerchantOnboarding();
    }
}
