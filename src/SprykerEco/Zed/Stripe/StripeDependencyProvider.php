<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe;

use SprykerEco\Zed\Kernel\AbstractBundleDependencyProvider;
use SprykerEco\Zed\Kernel\Container;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_STRIPE = 'CLIENT_STRIPE';

    public const string FACADE_SALES = 'FACADE_SALES';

    public const string FACADE_CALCULATION = 'FACADE_CALCULATION';

    public const string FACADE_OMS = 'FACADE_OMS';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addStripeClient($container);
        $container = $this->addOmsFacade($container);
        $container = $this->addSalesFacade($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addSalesFacade($container);
        $container = $this->addCalculationFacade($container);

        return $container;
    }

    protected function addSalesFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES, function (Container $container) {
            return $container->getLocator()->sales()->facade();
        });

        return $container;
    }

    protected function addCalculationFacade(Container $container): Container
    {
        $container->set(static::FACADE_CALCULATION, function (Container $container) {
            return $container->getLocator()->calculation()->facade();
        });

        return $container;
    }

    protected function addOmsFacade(Container $container): Container
    {
        $container->set(static::FACADE_OMS, function (Container $container) {
            return $container->getLocator()->oms()->facade();
        });

        return $container;
    }

    protected function addStripeClient(Container $container): Container
    {
        $container->set(static::CLIENT_STRIPE, function (Container $container) {
            return $container->getLocator()->stripe()->client();
        });

        return $container;
    }
}
