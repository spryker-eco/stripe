<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Controller;

use Generated\Shared\Transfer\MerchantAppOnboardingInitializationRequestTransfer;
use Generated\Shared\Transfer\MerchantAppOnboardingInitializationResponseTransfer;
use SprykerEco\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class OnboardingController extends AbstractController
{
    /**
     * Handles a POST from MerchantApp's initializeMerchantAppOnboarding() call.
     * Returns a redirect response with the Stripe Connect account link URL.
     *
     * Route: POST /stripe/onboarding/initialize
     */
    public function initializeAction(Request $request): JsonResponse
    {
        $initializationRequest = $this->buildInitializationRequest($request);

        $merchantReference = $initializationRequest->getMerchantOrFail()->getMerchantReferenceOrFail();

        $url = $this->getFacade()->generateMerchantOnboardingUrl(
            $merchantReference,
            (string)$initializationRequest->getSuccessUrl(),
            (string)$initializationRequest->getRefreshUrl(),
        );

        $response = (new MerchantAppOnboardingInitializationResponseTransfer())
            ->setStrategy('redirect')
            ->setUrl($url);

        return $this->jsonResponse($response->toArray(true, true));
    }

    protected function buildInitializationRequest(Request $request): MerchantAppOnboardingInitializationRequestTransfer
    {
        $data = (array)json_decode($request->getContent(), true);

        return (new MerchantAppOnboardingInitializationRequestTransfer())->fromArray($data, true);
    }
}
