<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 */
class OnboardingController extends AbstractController
{
    public function initializeAction(Request $request): RedirectResponse
    {
        $merchantUser = $this->getFactory()->getMerchantUserFacade()->getCurrentMerchantUser();
        $merchantReference = $merchantUser->getMerchantOrFail()->getMerchantReferenceOrFail();

        $returnUrl = (string)$request->query->get('successUrl', '');
        $refreshUrl = (string)$request->query->get('refreshUrl', '');

        $stripeConnectUrl = $this->getFacade()->generateMerchantOnboardingUrl(
            $merchantReference,
            $returnUrl,
            $refreshUrl,
        );

        return new RedirectResponse($stripeConnectUrl);
    }
}
