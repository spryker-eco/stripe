<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command;

use Generated\Shared\Transfer\OrderTransfer;
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
     * - Captures the full authorized amount of the Stripe PaymentIntent for the order.
     * - Always captures 100% of the authorized amount regardless of which items are in the current OMS batch.
     * - Status transition to captured is driven by the `payment_intent.succeeded` webhook.
     * - Captures the full authorized amount.
     * - Partial per-item capture is not used because Stripe only allows one capture per PaymentIntent.
     *
     * Note: Stripe supports partial captures on a PaymentIntent with capture_method: 'manual', but with limitations:
     * - You can capture less than the authorized amount (partial capture).
     * - However, you can only call capture once per PaymentIntent — any remaining uncaptured amount is automatically released.
     * - For multiple captures against a single authorization, you'd need Stripe's Separate Authorization & Capture
     *   with Overcapture feature (available for certain card networks) or split the original transaction.
     * - To avoid this limitation, we always capture the full amount. Items cancelled after capture are handled via refunds.
     *
     * @api
     *
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function run(array $orderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data): array
    {
        $orderTransfer = (new OrderTransfer())->setOrderReference($orderEntity->getOrderReference());
        $this->getFacade()->capturePayment($orderTransfer, 0);

        return $orderItems;
    }
}
