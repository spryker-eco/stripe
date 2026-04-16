<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\AclMerchantPortal;

use Generated\Shared\Transfer\AclEntityMetadataConfigTransfer;
use Generated\Shared\Transfer\AclEntityMetadataTransfer;
use Spryker\Zed\AclMerchantPortalExtension\Dependency\Plugin\AclEntityConfigurationExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeAclEntityConfigurationExpanderPlugin extends AbstractPlugin implements AclEntityConfigurationExpanderPluginInterface
{
    /**
     * @uses {@link \Spryker\Shared\AclEntity\AclEntityConstants::OPERATION_MASK_READ}
     */
    protected const int OPERATION_MASK_READ = 0b1;

    /**
     * @uses {@link \Spryker\Shared\AclEntity\AclEntityConstants::OPERATION_MASK_CREATE}
     */
    protected const int OPERATION_MASK_CREATE = 0b10;

    /**
     * @uses {@link \Spryker\Shared\AclEntity\AclEntityConstants::OPERATION_MASK_UPDATE}
     */
    protected const int OPERATION_MASK_UPDATE = 0b100;

    /**
     * {@inheritDoc}
     * - Expands provided `AclEntityMetadataConfig` transfer object with entities related to Stripe module functionality.
     *
     * @api
     */
    public function expand(AclEntityMetadataConfigTransfer $aclEntityMetadataConfigTransfer): AclEntityMetadataConfigTransfer
    {
        $aclEntityMetadataConfigTransfer
            ->getAclEntityMetadataCollectionOrFail()
            ->addAclEntityMetadata(
                'Orm\Zed\Stripe\Persistence\SpyStripeMerchant',
                (new AclEntityMetadataTransfer())
                    ->setEntityName('Orm\Zed\Stripe\Persistence\SpyStripeMerchant')
                    ->setDefaultGlobalOperationMask(static::OPERATION_MASK_UPDATE | static::OPERATION_MASK_CREATE | static::OPERATION_MASK_READ),
            );

        return $aclEntityMetadataConfigTransfer;
    }
}
