<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class PaymentController extends AbstractController
{
    protected const PARAM_ORDER_REFERENCE = 'order';

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

        return $this->renderView(
            '@Stripe/views/payment/payment.twig',
            [
                'orderReference' => $orderReference,
                'idSalesOrder' => $paymentDetails->getIdSalesOrder(),
                'stripePublishableKey' => $paymentDetails->getPublishableKey(),
                'stripeClientSecret' => $paymentDetails->getClientSecret(),
            ],
        );
    }
}
