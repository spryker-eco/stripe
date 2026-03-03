<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use SprykerEco\Zed\Kernel\Persistence\AbstractRepository;

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
}
