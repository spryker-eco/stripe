<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerEco\Shared\Stripe\StripeConfig;
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
     * @uses \SprykerShop\Yves\PaymentPage\Plugin\Router\PaymentPageRouteProviderPlugin::ROUTE_NAME_PAYMENT_ORDER_CANCEL
     */
    protected const string ROUTE_NAME_PAYMENT_ORDER_CANCEL = 'payment-cancel';

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
            if (in_array($paymentDetails->getStatus(), StripeConfig::SUCCESSFUL_PAYMENT_STATUSES, true)) {
                return $this->redirectResponseInternal(static::ROUTE_NAME_PAYMENT_ORDER_SUCCESS);
            }

            // Payment canceled (by customer, OMS) or unrecoverable state
            return $this->redirectResponseInternal(static::ROUTE_NAME_HOME);
        }

        $this->getFactory()->getCartClient()->clearQuote();

        $checkoutSuccessUrl = $this->getRouter()->generate(static::ROUTE_NAME_CHECKOUT_SUCCESS, [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $paymentPageUrl = $this->getRouter()->generate(static::ROUTE_STRIPE_PAYMENT, [
            static::PARAM_ORDER_REFERENCE => $orderReference,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->getRouter()->generate(static::ROUTE_NAME_PAYMENT_ORDER_CANCEL, [
            static::PARAM_ORDER_REFERENCE => $orderReference,
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        $idSalesOrder = $paymentDetails->getIdSalesOrder();
        $orderDetailsUrl = $idSalesOrder !== null
            ? $this->getRouter()->generate(static::ROUTE_NAME_CUSTOMER_ORDER_DETAILS, [], UrlGeneratorInterface::ABSOLUTE_PATH) . '?id=' . $idSalesOrder
            : null;

        return $this->renderView(
            '@Stripe/views/payment/payment.twig',
            [
                'orderReference' => $orderReference,
                'idSalesOrder' => $idSalesOrder,
                'stripePaymentIntent' => $paymentDetails,
                'checkoutSuccessUrl' => $checkoutSuccessUrl,
                'paymentPageUrl' => $paymentPageUrl,
                'orderDetailsUrl' => $orderDetailsUrl,
                'cancelUrl' => $cancelUrl, // order payment cancellation requires that OMS state "payment pending" has flag "cancellable"
            ],
        );
    }
}
