<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use SprykerEco\Shared\Stripe\StripeConfig;

class StripeRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    protected const string ROUTE_STRIPE_NOTIFICATION = 'stripe-notification';

    protected const string ROUTE_STRIPE_PAYMENT = 'stripe-payment';

    /**
     * Specification:
     * - Adds routes for webhook notification and Stripe payment page.
     *
     * @api
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addStripeNotificationRoute($routeCollection);
        $routeCollection = $this->addStripePaymentRoute($routeCollection);

        return $routeCollection;
    }

    protected function addStripeNotificationRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(StripeConfig::ROUTE_PATH_NOTIFICATION, 'Stripe', 'Notification', 'notificationAction');
        $routeCollection->add(static::ROUTE_STRIPE_NOTIFICATION, $route);

        return $routeCollection;
    }

    protected function addStripePaymentRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute(StripeConfig::ROUTE_PATH_PAYMENT, 'Stripe', 'Payment', 'paymentAction');
        $routeCollection->add(static::ROUTE_STRIPE_PAYMENT, $route);

        return $routeCollection;
    }
}
