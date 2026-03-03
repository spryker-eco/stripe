<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Communication;

use SprykerEco\Zed\Calculation\Business\CalculationFacadeInterface;
use SprykerEco\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerEco\Zed\Sales\Business\SalesFacadeInterface;
use SprykerEco\Zed\Stripe\StripeDependencyProvider;

/**
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeCommunicationFactory extends AbstractCommunicationFactory
{
    public function getCalculationFacade(): CalculationFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_CALCULATION);
    }

    public function getSalesFacade(): SalesFacadeInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::FACADE_SALES);
    }
}
