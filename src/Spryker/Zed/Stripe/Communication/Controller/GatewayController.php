<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Communication\Controller;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use SprykerEco\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 */
class GatewayController extends AbstractGatewayController
{
    public function processWebhookAction(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer
    ): StripeWebhookProcessResponseTransfer {
        return $this->getFacade()->processWebhook($webhookPayloadTransfer);
    }
}
