<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Condition;

use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use SprykerEco\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\Oms\Dependency\Plugin\Condition\ConditionInterface;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class IsAuthorizationFailedPlugin extends AbstractPlugin implements ConditionInterface
{
    /**
     * {@inheritDoc}
     * - Called by OMS to determine if transition to "authorization failed" state is actual.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function check(SpySalesOrderItem $orderItemEntity): bool
    {
        return $this->getFacade()->isPaymentAuthorizationFailed($orderItemEntity);
    }
}
