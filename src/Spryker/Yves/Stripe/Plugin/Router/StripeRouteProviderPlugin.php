<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe\Plugin\Router;

use SprykerEco\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use SprykerEco\Yves\Router\Route\RouteCollection;

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
