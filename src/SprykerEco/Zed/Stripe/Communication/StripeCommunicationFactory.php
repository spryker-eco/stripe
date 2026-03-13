<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\MerchantUser\Business\MerchantUserFacadeInterface;
use Spryker\Zed\Refund\Business\RefundFacadeInterface;
use Spryker\Zed\SalesPayment\Business\SalesPaymentFacadeInterface;
use SprykerEco\Zed\Stripe\StripeDependencyProvider;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeCommunicationFactory extends AbstractCommunicationFactory
{
    public function getSalesPaymentFacade(): SalesPaymentFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_SALES_PAYMENT);
    }

    public function getRefundFacade(): RefundFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_REFUND);
    }

    public function getMerchantUserFacade(): MerchantUserFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_MERCHANT_USER);
    }
}
