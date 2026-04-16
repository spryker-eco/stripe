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
class DashboardController extends AbstractController
{
    protected const string MERCHANT_REFERENCE_PLACEHOLDER = '_merchantReference_';

    /**
     * Redirects the merchant to their Stripe Express Dashboard.
     * The `merchantReference` query parameter must be the real merchant reference (not the placeholder).
     *
     * Route: GET /stripe/dashboard
     *
     * @return \Symfony\Component\HttpFoundation\Response|array<string, string>
     */
    public function indexAction(Request $request): array|Response
    {
        $merchantReference = (string)$request->query->get('merchantReference', '');

        if ($merchantReference === '' || $merchantReference === static::MERCHANT_REFERENCE_PLACEHOLDER) {
            return $this->viewResponse(['errorMessage' => 'Missing or unresolved merchant reference.']);
        }

        $dashboardUrl = $this->getFacade()->generateDashboardUrl($merchantReference);

        if ($dashboardUrl === null) {
            return $this->viewResponse(['errorMessage' => 'Unable to generate Stripe Dashboard link. Please ensure the merchant has completed onboarding.']);
        }

        return new RedirectResponse($dashboardUrl);
    }
}
