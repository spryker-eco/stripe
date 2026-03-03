<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe;

use SprykerEco\Client\Kernel\AbstractDependencyProvider;
use SprykerEco\Client\Kernel\Container;

/**
 * @method \SprykerEco\Client\Stripe\StripeConfig getConfig()
 */
class StripeDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_ZED_REQUEST = 'CLIENT_ZED_REQUEST';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addZedRequestClient($container);

        return $container;
    }

    protected function addZedRequestClient(Container $container): Container
    {
        $container->set(static::CLIENT_ZED_REQUEST, function (Container $container) {
            return $container->getLocator()->zedRequest()->client();
        });

        return $container;
    }
}
