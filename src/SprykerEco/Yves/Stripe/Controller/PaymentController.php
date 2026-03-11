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

    public function paymentAction(Request $request): Response
    {
        $orderReference = (string)$request->query->get(static::PARAM_ORDER_REFERENCE, '');

        if ($orderReference === '') {
            return $this->redirectResponseInternal('home');
        }

        $paymentDetails = $this->getFactory()
            ->getStripeClient()
            ->getPaymentDetails($orderReference);

        if (!$paymentDetails->getIsSuccessful()) {
            // Payment already captured/succeeded — send customer to the success page
            if (in_array($paymentDetails->getStatus(), ['requires_capture', 'succeeded'], true)) {
                return $this->redirectResponseInternal('payment-success');
            }

            return $this->redirectResponseInternal('home');
        }

        $checkoutSuccessUrl = $this->getRouter()->generate('checkout-success', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $paymentPageUrl = $this->getRouter()->generate('stripe-payment', [
            static::PARAM_ORDER_REFERENCE => $orderReference,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $idSalesOrder = $paymentDetails->getIdSalesOrder();
        $orderDetailsUrl = $idSalesOrder !== null
            ? $this->getRouter()->generate('customer/order/details', [], UrlGeneratorInterface::ABSOLUTE_PATH) . '?id=' . $idSalesOrder
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
