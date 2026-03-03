<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe;

use SprykerEco\Yves\Kernel\AbstractBundleDependencyProvider;
use SprykerEco\Yves\Kernel\Container;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_STRIPE = 'CLIENT_STRIPE';

    public const string CLIENT_QUOTE = 'CLIENT_QUOTE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addStripeClient($container);
        $container = $this->addQuoteClient($container);

        return $container;
    }

    protected function addStripeClient(Container $container): Container
    {
        $container->set(static::CLIENT_STRIPE, function (Container $container) {
            return $container->getLocator()->stripe()->client();
        });

        return $container;
    }

    protected function addQuoteClient(Container $container): Container
    {
        $container->set(static::CLIENT_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->client();
        });

        return $container;
    }
}
