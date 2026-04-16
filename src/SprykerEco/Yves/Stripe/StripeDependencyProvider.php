<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_STRIPE = 'CLIENT_STRIPE';

    public const string CLIENT_CART = 'CLIENT_CART';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addStripeClient($container);
        $container = $this->addCartClient($container);

        return $container;
    }

    protected function addCartClient(Container $container): Container
    {
        $container->set(static::CLIENT_CART, function (Container $container) {
            return $container->getLocator()->cart()->client();
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
