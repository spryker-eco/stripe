<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Orm\Zed\Stripe\Persistence\SpyStripeMerchantQuery;
use Orm\Zed\Stripe\Persistence\SpyStripePaymentQuery;
use SprykerEco\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerEco\Zed\Stripe\Persistence\Propel\Mapper\StripePaymentMapper;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripePersistenceFactory extends AbstractPersistenceFactory
{
    public function createStripePaymentQuery(): SpyStripePaymentQuery
    {
        return SpyStripePaymentQuery::create();
    }

    public function createStripeMerchantQuery(): SpyStripeMerchantQuery
    {
        return SpyStripeMerchantQuery::create();
    }

    public function createStripePaymentMapper(): StripePaymentMapper
    {
        return new StripePaymentMapper();
    }
}
