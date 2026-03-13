<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_PAYMENT_APP = 'FACADE_PAYMENT_APP';

    public const string FACADE_MERCHANT_APP = 'FACADE_MERCHANT_APP';

    public const string FACADE_SALES_PAYMENT_DETAIL = 'FACADE_SALES_PAYMENT_DETAIL';

    public const string FACADE_SALES_PAYMENT = 'FACADE_SALES_PAYMENT';

    public const string FACADE_REFUND = 'FACADE_REFUND';

    public const string FACADE_MERCHANT_USER = 'FACADE_MERCHANT_USER';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addPaymentAppFacade($container);
        $container = $this->addMerchantAppFacade($container);
        $container = $this->addSalesPaymentDetailFacade($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addSalesPaymentFacade($container);
        $container = $this->addRefundFacade($container);
        $container = $this->addMerchantUserFacade($container);

        return $container;
    }

    protected function addPaymentAppFacade(Container $container): Container
    {
        $container->set(static::FACADE_PAYMENT_APP, function (Container $container) {
            return $container->getLocator()->paymentApp()->facade();
        });

        return $container;
    }

    protected function addMerchantAppFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT_APP, function (Container $container) {
            return $container->getLocator()->merchantApp()->facade();
        });

        return $container;
    }

    protected function addSalesPaymentDetailFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES_PAYMENT_DETAIL, function (Container $container) {
            return $container->getLocator()->salesPaymentDetail()->facade();
        });

        return $container;
    }

    protected function addSalesPaymentFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES_PAYMENT, function (Container $container) {
            return $container->getLocator()->salesPayment()->facade();
        });

        return $container;
    }

    protected function addRefundFacade(Container $container): Container
    {
        $container->set(static::FACADE_REFUND, function (Container $container) {
            return $container->getLocator()->refund()->facade();
        });

        return $container;
    }

    protected function addMerchantUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT_USER, function (Container $container) {
            return $container->getLocator()->merchantUser()->facade();
        });

        return $container;
    }
}
