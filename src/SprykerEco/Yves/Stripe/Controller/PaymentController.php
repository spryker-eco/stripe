<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StripePaymentDetailsRequestTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerEco\Shared\Stripe\StripeConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class PaymentController extends AbstractController
{
    protected const PARAM_ORDER_REFERENCE = 'orderReference';

    protected const string PARAM_PAYMENT_INTENT_CLIENT_SECRET = 'payment_intent_client_secret';

    /**
     * @uses \SprykerShop\Yves\CustomerPage\Plugin\CustomerPage\RedirectUriCustomerRedirectStrategyPlugin::PARAM_REDIRECT_URI
     */
    protected const string PARAM_REDIRECT_URI = 'redirectUri';

    /**
     * @uses \SprykerShop\Yves\HomePage\Plugin\Router\HomePageRouteProviderPlugin::ROUTE_NAME_HOME
     */
    protected const string ROUTE_NAME_HOME = 'home';

    /**
     * @uses \SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN
     */
    protected const string ROUTE_NAME_LOGIN = 'login';

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

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();
        $stripePaymentDetailsRequestTransfer = (new StripePaymentDetailsRequestTransfer())
            ->setOrderReference($orderReference)
            ->setCustomerReference($customerTransfer?->getCustomerReference())
            ->setClientSecret($this->findClientSecret($request))
            ->setIsOrderPlacedInCurrentSession($this->isOrderPlacedInCurrentSession($orderReference));

        if (!$this->hasAccessProof($stripePaymentDetailsRequestTransfer)) {
            return $this->createLoginRedirectResponse($orderReference);
        }

        $paymentDetails = $this->getFactory()
            ->getStripeClient()
            ->getPaymentDetailsByRequest($stripePaymentDetailsRequestTransfer);

        if (!$paymentDetails->getIsSuccessful()) {
            // Payment already captured/succeeded — send customer to the success page
            if (in_array($paymentDetails->getStatus(), StripeConfig::SUCCESSFUL_PAYMENT_STATUSES, true)) {
                return $this->redirectResponseInternal(static::ROUTE_NAME_PAYMENT_ORDER_SUCCESS);
            }

            if ($customerTransfer === null) {
                return $this->createLoginRedirectResponse($orderReference);
            }

            // Payment canceled (by customer, OMS) or unrecoverable state
            return $this->redirectResponseInternal(static::ROUTE_NAME_HOME);
        }

        if ($customerTransfer !== null) {
            $this->getFactory()->getCartClient()->clearQuote();
        }

        $checkoutSuccessUrl = $this->getRouter()->generate(static::ROUTE_NAME_CHECKOUT_SUCCESS, [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $paymentPageUrl = $this->getRouter()->generate(static::ROUTE_STRIPE_PAYMENT, [
            static::PARAM_ORDER_REFERENCE => $orderReference,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->isOrderCancellableByVisitor($customerTransfer, $stripePaymentDetailsRequestTransfer)
            ? $this->getRouter()->generate(static::ROUTE_NAME_PAYMENT_ORDER_CANCEL, [
                static::PARAM_ORDER_REFERENCE => $orderReference,
            ], UrlGeneratorInterface::ABSOLUTE_PATH)
            : null;

        $idSalesOrder = $customerTransfer !== null ? $paymentDetails->getIdSalesOrder() : null;
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

    protected function findClientSecret(Request $request): ?string
    {
        $clientSecret = (string)$request->query->get(static::PARAM_PAYMENT_INTENT_CLIENT_SECRET, '');

        return $clientSecret !== '' ? $clientSecret : null;
    }

    protected function isOrderPlacedInCurrentSession(string $orderReference): bool
    {
        return $this->getFactory()->getCartClient()->getQuote()->getOrderReference() === $orderReference;
    }

    protected function isOrderCancellableByVisitor(
        ?CustomerTransfer $customerTransfer,
        StripePaymentDetailsRequestTransfer $stripePaymentDetailsRequestTransfer,
    ): bool {
        return $customerTransfer !== null || $stripePaymentDetailsRequestTransfer->getIsOrderPlacedInCurrentSession() === true;
    }

    protected function createLoginRedirectResponse(string $orderReference): RedirectResponse
    {
        return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN, [
            static::PARAM_REDIRECT_URI => $this->getRouter()->generate(static::ROUTE_STRIPE_PAYMENT, [
                static::PARAM_ORDER_REFERENCE => $orderReference,
            ], UrlGeneratorInterface::ABSOLUTE_PATH),
        ]);
    }

    protected function hasAccessProof(StripePaymentDetailsRequestTransfer $stripePaymentDetailsRequestTransfer): bool
    {
        return $stripePaymentDetailsRequestTransfer->getCustomerReference() !== null
            || $stripePaymentDetailsRequestTransfer->getClientSecret() !== null
            || $stripePaymentDetailsRequestTransfer->getIsOrderPlacedInCurrentSession() === true;
    }
}
