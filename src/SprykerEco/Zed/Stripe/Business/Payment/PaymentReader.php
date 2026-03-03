<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
