<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

class StripeRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    protected const ROUTE_STRIPE_NOTIFICATION = 'stripe-notification';

    /**
     * Specification:
     * - Adds routes for webhook notification.
     *
     * @param \SprykerEco\Yves\Router\Route\RouteCollection $routeCollection
     *
     * @return \SprykerEco\Yves\Router\Route\RouteCollection
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addStripeNotificationRoute($routeCollection);

        return $routeCollection;
    }

    protected function addStripeNotificationRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute('/stripe/notification', 'Stripe', 'Notification', 'notificationAction');
        $routeCollection->add(static::ROUTE_STRIPE_NOTIFICATION, $route);

        return $routeCollection;
    }
}
