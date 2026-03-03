<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Oms\Command;

use Orm\Zed\Sales\Persistence\SpySalesOrder;

interface OmsCommandHandlerInterface
{
    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeAuthorizeCommand(SpySalesOrder $orderEntity, array $orderItems): void;

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCaptureCommand(SpySalesOrder $orderEntity, array $orderItems): void;

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCancelCommand(SpySalesOrder $orderEntity, array $orderItems): void;
}
