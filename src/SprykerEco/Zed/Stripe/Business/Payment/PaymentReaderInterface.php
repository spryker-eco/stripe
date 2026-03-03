<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\StripeTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;

interface PaymentReaderInterface
{
    public function findPaymentByIdSalesOrder(int $idSalesOrder): ?StripeTransfer;

    public function findPaymentByOrderItem(SpySalesOrderItem $orderItemEntity): ?StripeTransfer;

    public function findPaymentByProviderReference(string $providerReference): ?StripeTransfer;
}
