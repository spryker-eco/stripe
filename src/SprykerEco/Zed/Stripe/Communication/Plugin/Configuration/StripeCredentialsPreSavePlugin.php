<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Configuration;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePreSavePluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 */
class StripeCredentialsPreSavePlugin extends AbstractPlugin implements ConfigurationValuePreSavePluginInterface
{
    /**
     * {@inheritDoc}
     * - Validates Stripe API configuration items.
     *
     * @api
     */
    public function preSave(
        ConfigurationValueCollectionRequestTransfer $requestTransfer,
    ): ConfigurationValueCollectionRequestTransfer {
        return $this->getBusinessFactory()
            ->createCredentialsPreSaveHandler()
            ->handleCredentialsPreSave($requestTransfer);
    }
}
