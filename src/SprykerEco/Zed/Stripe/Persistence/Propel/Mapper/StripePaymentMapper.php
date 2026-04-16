<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Orm\Zed\Stripe\Persistence\SpyStripeMerchant;
use Orm\Zed\Stripe\Persistence\SpyStripePayment;

class StripePaymentMapper
{
    public function mapPaymentEntityToTransfer(
        SpyStripePayment $entity,
        StripePaymentTransfer $transfer,
    ): StripePaymentTransfer {
        return $transfer->fromArray($entity->toArray(), true);
    }

    public function mapPaymentTransferToEntity(StripePaymentTransfer $transfer): SpyStripePayment
    {
        $entity = new SpyStripePayment();
        $entity->fromArray($transfer->modifiedToArray());

        return $entity;
    }

    public function mapMerchantEntityToTransfer(
        SpyStripeMerchant $entity,
        StripeMerchantTransfer $transfer,
    ): StripeMerchantTransfer {
        return $transfer->fromArray($entity->toArray(), true);
    }
}
