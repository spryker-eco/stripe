<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\StripeTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

class PaymentReader implements PaymentReaderInterface
{
    public function __construct(
        protected StripeRepositoryInterface $repository,
    ) {
    }

    public function findPaymentByIdSalesOrder(int $idSalesOrder): ?StripeTransfer
    {
        return $this->repository->findStripeByIdSalesOrder($idSalesOrder);
    }

    public function findPaymentByOrderItem(SpySalesOrderItem $orderItemEntity): ?StripeTransfer
    {
        return $this->repository->findStripeByIdSalesOrder($orderItemEntity->getFkSalesOrder());
    }

    public function findPaymentByProviderReference(string $providerReference): ?StripeTransfer
    {
        return $this->repository->findStripeByProviderReference($providerReference);
    }
}
