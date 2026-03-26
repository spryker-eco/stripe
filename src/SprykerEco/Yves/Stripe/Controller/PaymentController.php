<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class PaymentController extends AbstractController
{
    protected const PARAM_ORDER_REFERENCE = 'orderReference';

    /**
     * @uses \SprykerShop\Yves\HomePage\Plugin\Router\HomePageRouteProviderPlugin::ROUTE_NAME_HOME
     */
    protected const string ROUTE_NAME_HOME = 'home';

    /**
     * @uses \SprykerShop\Yves\PaymentPage\Plugin\Router\PaymentPageRouteProviderPlugin::ROUTE_NAME_PAYMENT_ORDER_SUCCESS
     */
    protected const string ROUTE_NAME_PAYMENT_ORDER_SUCCESS = 'payment-success';

    /**
     * @uses \SprykerEco\Yves\Stripe\Plugin\Router\StripeRouteProviderPlugin::ROUTE_NAME_STRIPE_PAYMENT
     */
    protected const string ROUTE_STRIPE_PAYMENT = 'stripe-payment';

    /**
     * @uses \SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin::ROUTE_NAME_CUSTOMER_ORDER_DETAILS
     */
    protected const string ROUTE_NAME_CUSTOMER_ORDER_DETAILS = 'customer/order/details';

    /**
     * @uses \SprykerShop\Yves\CheckoutPage\Plugin\Router\CheckoutPageRouteProviderPlugin::ROUTE_NAME_CHECKOUT_SUCCESS
     */
    protected const string ROUTE_NAME_CHECKOUT_SUCCESS = 'checkout-success';

    public function paymentAction(Request $request): Response
    {
        $orderReference = (string)$request->query->get(static::PARAM_ORDER_REFERENCE, '');

        if ($orderReference === '') {
            return $this->redirectResponseInternal(static::ROUTE_NAME_HOME);
        }

        $paymentDetails = $this->getFactory()
            ->getStripeClient()
            ->getPaymentDetails($orderReference);

        if (!$paymentDetails->getIsSuccessful()) {
            // Payment already captured/succeeded — send customer to the success page
            if (in_array($paymentDetails->getStatus(), $this->getFactory()->getSharedConfig()->getSuccessfulPaymentStatuses(), true)) {
                return $this->redirectResponseInternal(static::ROUTE_NAME_PAYMENT_ORDER_SUCCESS);
            }

            return $this->redirectResponseInternal(static::ROUTE_NAME_HOME);
        }

        $checkoutSuccessUrl = $this->getRouter()->generate(static::ROUTE_NAME_CHECKOUT_SUCCESS, [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $paymentPageUrl = $this->getRouter()->generate(static::ROUTE_STRIPE_PAYMENT, [
            static::PARAM_ORDER_REFERENCE => $orderReference,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $idSalesOrder = $paymentDetails->getIdSalesOrder();
        $orderDetailsUrl = $idSalesOrder !== null
            ? $this->getRouter()->generate(static::ROUTE_NAME_CUSTOMER_ORDER_DETAILS, [], UrlGeneratorInterface::ABSOLUTE_PATH) . '?id=' . $idSalesOrder
            : null;

        return $this->renderView(
            '@Stripe/views/payment/payment.twig',
            [
                'orderReference' => $orderReference,
                'idSalesOrder' => $idSalesOrder,
                'stripePublishableKey' => $paymentDetails->getPublishableKey(),
                'stripeClientSecret' => $paymentDetails->getClientSecret(),
                'checkoutSuccessUrl' => $checkoutSuccessUrl,
                'paymentPageUrl' => $paymentPageUrl,
                'orderDetailsUrl' => $orderDetailsUrl,
            ],
        );
    }
}
