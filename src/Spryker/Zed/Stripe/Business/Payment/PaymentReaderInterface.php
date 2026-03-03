<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
