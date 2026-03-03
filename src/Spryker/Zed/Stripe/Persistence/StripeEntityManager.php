<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeTransfer;
use Orm\Zed\Stripe\Persistence\SpyStripeNotification;
use SprykerEco\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerEco\Zed\Stripe\Persistence\StripePersistenceFactory getFactory()
 */
class StripeEntityManager extends AbstractEntityManager implements StripeEntityManagerInterface
{
    public function savePayment(StripeTransfer $stripeTransfer): StripeTransfer
    {
        $paymentEntity = $this->getFactory()
            ->createStripeQuery()
            ->filterByIdStripe($stripeTransfer->getIdStripe())
            ->findOneOrCreate();

        $paymentEntity->fromArray($stripeTransfer->modifiedToArray());
        $paymentEntity->save();

        $stripeTransfer->setIdStripe($paymentEntity->getIdStripe());

        return $stripeTransfer;
    }

    public function updatePaymentStatus(
        int $idStripe,
        string $status,
        ?string $providerReference = null,
    ): void {
        $paymentEntity = $this->getFactory()
            ->createStripeQuery()
            ->filterByIdStripe($idStripe)
            ->findOne();

        if ($paymentEntity === null) {
            return;
        }

        // TODO: Implement status update based on the PSP-specific implementation and DB schema.
        // e.g.
        // $paymentEntity->setStatus($status);
        // if ($providerReference !== null) {
        //    $paymentEntity->setProviderReference($providerReference);
        //}

        $paymentEntity->save();
    }

    public function savePaymentOrderItem(
        int $idStripe,
        int $idSalesOrderItem,
        string $status
    ): void {
        $orderItemEntity = $this->getFactory()
            ->createStripeOrderItemQuery()
            ->filterByFkStripe($idStripe)
            ->filterByFkSalesOrderItem($idSalesOrderItem)
            ->findOneOrCreate();

        $orderItemEntity
            ->setFkStripe($idStripe)
            ->setFkSalesOrderItem($idSalesOrderItem)
            ->save();
    }

    public function saveNotification(string $payload): void
    {
        $notificationEntity = new SpyStripeNotification();

        $notificationEntity
            ->setPayload($payload)
            ->save();
    }
}
