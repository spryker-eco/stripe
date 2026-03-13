<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\AclMerchantPortal;

use Generated\Shared\Transfer\RuleTransfer;
use Spryker\Zed\AclMerchantPortalExtension\Dependency\Plugin\MerchantAclRuleExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeMerchantAclRuleExpanderPlugin extends AbstractPlugin implements MerchantAclRuleExpanderPluginInterface
{
    /**
     * @uses {@link \Spryker\Shared\Acl\AclConstants::VALIDATOR_WILDCARD}
     *
     * @var string
     */
    protected const RULE_VALIDATOR_WILDCARD = '*';

    /**
     * @uses {@link \Spryker\Shared\Acl\AclConstants::ALLOW}
     *
     * @var string
     */
    protected const RULE_TYPE_ALLOW = 'allow';

    /**
     * {@inheritDoc}
     * - Adds `stripe` to list of `AclRules`.
     *
     * @api
     *
     * @param list<\Generated\Shared\Transfer\RuleTransfer> $ruleTransfers
     *
     * @return list<\Generated\Shared\Transfer\RuleTransfer>
     */
    public function expand(array $ruleTransfers): array
    {
        $ruleTransfers[] = (new RuleTransfer())
            ->setBundle('stripe')
            ->setController(static::RULE_VALIDATOR_WILDCARD)
            ->setAction(static::RULE_VALIDATOR_WILDCARD)
            ->setType(static::RULE_TYPE_ALLOW);

        return $ruleTransfers;
    }
}
