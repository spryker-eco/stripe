<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeMerchantPayoutTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerEco\Zed\Stripe\Persistence\StripePersistenceFactory getFactory()
 */
class StripeRepository extends AbstractRepository implements StripeRepositoryInterface
{
    public function findPaymentByOrderReference(string $orderReference): ?StripePaymentTransfer
    {
        $paymentEntity = $this->getFactory()
            ->createStripePaymentQuery()
            ->filterByOrderReference($orderReference)
            ->findOne();

        if ($paymentEntity === null) {
            return null;
        }

        return $this->getFactory()->createStripePaymentMapper()
            ->mapPaymentEntityToTransfer($paymentEntity, new StripePaymentTransfer());
    }

    public function findPaymentByTransactionId(string $transactionId): ?StripePaymentTransfer
    {
        $paymentEntity = $this->getFactory()
            ->createStripePaymentQuery()
            ->filterByTransactionId($transactionId)
            ->findOne();

        if ($paymentEntity === null) {
            return null;
        }

        return $this->getFactory()->createStripePaymentMapper()
            ->mapPaymentEntityToTransfer($paymentEntity, new StripePaymentTransfer());
    }

    public function findMerchantByReference(string $merchantReference): ?StripeMerchantTransfer
    {
        $merchantEntity = $this->getFactory()
            ->createStripeMerchantQuery()
            ->filterByMerchantReference($merchantReference)
            ->findOne();

        if ($merchantEntity === null) {
            return null;
        }

        return $this->getFactory()->createStripePaymentMapper()
            ->mapMerchantEntityToTransfer($merchantEntity, new StripeMerchantTransfer());
    }

    public function findSuccessfulMerchantPayoutByOrderReferenceAndMerchantReference(
        string $orderReference,
        string $merchantReference,
    ): ?StripeMerchantPayoutTransfer {
        $entity = $this->getFactory()
            ->createStripeMerchantPayoutQuery()
            ->filterByOrderReference($orderReference)
            ->filterByMerchantReference($merchantReference)
            ->filterByIsSuccessful(true)
            ->filterByIsReversed(false)
            ->orderByIdStripeMerchantPayout('DESC')
            ->findOne();

        if ($entity === null) {
            return null;
        }

        return $this->getFactory()->createStripePaymentMapper()
            ->mapMerchantPayoutEntityToTransfer($entity, new StripeMerchantPayoutTransfer());
    }

    public function findSuccessfulMerchantPayoutReversalByOrderReferenceAndMerchantReference(
        string $orderReference,
        string $merchantReference,
    ): ?StripeMerchantPayoutTransfer {
        $entity = $this->getFactory()
            ->createStripeMerchantPayoutQuery()
            ->filterByOrderReference($orderReference)
            ->filterByMerchantReference($merchantReference)
            ->filterByIsSuccessful(true)
            ->filterByIsReversed(true)
            ->orderByIdStripeMerchantPayout('DESC')
            ->findOne();

        if ($entity === null) {
            return null;
        }

        return $this->getFactory()->createStripePaymentMapper()
            ->mapMerchantPayoutEntityToTransfer($entity, new StripeMerchantPayoutTransfer());
    }
}
