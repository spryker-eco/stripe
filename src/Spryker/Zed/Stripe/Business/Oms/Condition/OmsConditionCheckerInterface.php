<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Oms\Condition;

use Orm\Zed\Sales\Persistence\SpySalesOrderItem;

interface OmsConditionCheckerInterface
{
    public function isPaymentAuthorized(SpySalesOrderItem $orderItemEntity): bool;

    public function isPaymentAuthorizationFailed(SpySalesOrderItem $orderItemEntity): bool;

    public function isPaymentCaptured(SpySalesOrderItem $orderItemEntity): bool;
}
