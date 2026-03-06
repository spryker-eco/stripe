<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentAmountRequestTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandByOrderInterface;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeCaptureCommandPlugin extends AbstractPlugin implements CommandByOrderInterface
{
    /**
     * {@inheritDoc}
     * - Captures a previously authorized Stripe PaymentIntent.
     * - Capture amount is calculated via SalesPaymentFacade: sum of item prices + shipping on the first capture.
     * - Status transition to captured is driven by the `payment_intent.succeeded` webhook.
     *
     * @api
     *
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function run(array $orderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data): array
    {
        $orderItemIds = array_map(
            static fn ($orderItem): int => $orderItem->getIdSalesOrderItem(),
            $orderItems,
        );

        $captureAmount = $this->getFactory()->getSalesPaymentFacade()->calculateCaptureAmount(
            (new PaymentAmountRequestTransfer())
                ->setIdSalesOrder($orderEntity->getIdSalesOrder())
                ->setOrderItemIds($orderItemIds),
        );

        $orderTransfer = (new OrderTransfer())->setOrderReference($orderEntity->getOrderReference());

        $this->getFacade()->capturePayment($orderTransfer, $captureAmount);

        return $orderItems;
    }
}
