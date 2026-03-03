<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
