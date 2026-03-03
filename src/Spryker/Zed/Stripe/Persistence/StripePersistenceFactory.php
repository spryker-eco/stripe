<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Orm\Zed\Stripe\Persistence\SpyStripeNotificationQuery;
use Orm\Zed\Stripe\Persistence\SpyStripeOrderItemQuery;
use Orm\Zed\Stripe\Persistence\SpyStripeQuery;
use SprykerEco\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripePersistenceFactory extends AbstractPersistenceFactory
{
    public function createStripeQuery(): SpyStripeQuery
    {
        return SpyStripeQuery::create();
    }

    public function createStripeOrderItemQuery(): SpyStripeOrderItemQuery
    {
        return SpyStripeOrderItemQuery::create();
    }

    public function createStripeNotificationQuery(): SpyStripeNotificationQuery
    {
        return SpyStripeNotificationQuery::create();
    }
}
