<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripePaymentTransfer;
use Orm\Zed\Stripe\Persistence\SpyStripeMerchant;
use SprykerEco\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerEco\Zed\Stripe\Persistence\StripePersistenceFactory getFactory()
 */
class StripeEntityManager extends AbstractEntityManager implements StripeEntityManagerInterface
{
    public function createPayment(StripePaymentTransfer $stripePaymentTransfer): StripePaymentTransfer
    {
        $paymentEntity = $this->getFactory()->createStripePaymentMapper()
            ->mapPaymentTransferToEntity($stripePaymentTransfer);

        $paymentEntity->save();

        $stripePaymentTransfer->setIdStripePayment($paymentEntity->getIdStripePayment());

        return $stripePaymentTransfer;
    }

    public function updateTransactionId(string $orderReference, string $transactionId): void
    {
        $paymentEntity = $this->getFactory()
            ->createStripePaymentQuery()
            ->filterByOrderReference($orderReference)
            ->findOne();

        if ($paymentEntity === null) {
            return;
        }

        $paymentEntity->setTransactionId($transactionId);
        $paymentEntity->save();
    }

    public function saveMerchantStripeAccountId(string $merchantReference, string $stripeAccountId): void
    {
        $merchantEntity = $this->getFactory()
            ->createStripeMerchantQuery()
            ->filterByMerchantReference($merchantReference)
            ->findOneOrCreate();

        $merchantEntity->setMerchantReference($merchantReference);
        $merchantEntity->setStripeAccountId($stripeAccountId);
        $merchantEntity->save();
    }
}
