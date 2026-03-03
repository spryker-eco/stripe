<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeTransfer;
use SprykerEco\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerEco\Zed\Stripe\Persistence\StripePersistenceFactory getFactory()
 */
class StripeRepository extends AbstractRepository implements StripeRepositoryInterface
{
    public function findStripeByIdSalesOrder(int $idSalesOrder): ?StripeTransfer
    {
        $paymentEntity = $this->getFactory()
            ->createStripeQuery()
            ->filterByFkSalesOrder($idSalesOrder)
            ->findOne();

        if ($paymentEntity === null) {
            return null;
        }

        return (new StripeTransfer())->fromArray($paymentEntity->toArray(), true);
    }

    public function findStripeByProviderReference(string $providerReference): ?StripeTransfer
    {
        // TODO: Implement filtering by external reference number by adding filterBy to the query below.
        $paymentEntity = $this->getFactory()
            ->createStripeQuery()
            // e.g.
            //->filterByProviderReference($providerReference)
            ->findOne();

        if ($paymentEntity === null) {
            return null;
        }

        return (new StripeTransfer())->fromArray($paymentEntity->toArray(), true);
    }
}
