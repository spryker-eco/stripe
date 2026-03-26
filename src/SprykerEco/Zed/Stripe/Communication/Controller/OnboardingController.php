<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 */
class OnboardingController extends AbstractController
{
    /**
     * @return Response|array<string, string>
     */
    public function initializeAction(Request $request): array|Response
    {
        $merchantUser = $this->getFactory()->getMerchantUserFacade()->getCurrentMerchantUser();
        $merchantReference = $merchantUser->getMerchantOrFail()->getMerchantReferenceOrFail();

        $returnUrl = (string)$request->query->get('successUrl', '');
        $refreshUrl = (string)$request->query->get('refreshUrl', '');

        $accountLinksResponse = $this->getFacade()->generateMerchantOnboardingUrl(
            $merchantReference,
            $returnUrl,
            $refreshUrl,
        );

        if (!$accountLinksResponse->getIsSuccessful()) {
            return $this->viewResponse(['errorMessage' => $accountLinksResponse->getMessage()]);
        }

        return new RedirectResponse((string)$accountLinksResponse->getUrl());
    }
}
