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

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addPaymentAppFacade($container);
        $container = $this->addMerchantAppFacade($container);

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
}
