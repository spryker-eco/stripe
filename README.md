# Stripe Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/algolia/v/stable.svg)](https://packagist.org/packages/spryker-eco/stripe)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)


## Integration

### Step 1: Install the package

```bash
composer require spryker-eco/stripe
```

---

### Step 2: Remove old ACP MessageBroker plugins (if any)

**File:** `src/Pyz/Zed/MessageBroker/MessageBrokerDependencyProvider.php`

Remove these imports and their instantiations from `getMessageHandlerPlugins()`:

```php
// Remove these use statements:
use Spryker\Zed\Payment\Communication\Plugin\MessageBroker\PaymentMethodMessageHandlerPlugin;
use Spryker\Zed\Payment\Communication\Plugin\MessageBroker\PaymentOperationsMessageHandlerPlugin;
use Spryker\Zed\PaymentApp\Communication\Plugin\MessageBroker\PaymentAppOperationsMessageHandlerPlugin;
use Spryker\Zed\SalesPaymentDetail\Communication\Plugin\MessageBroker\SalesPaymentDetailMessageHandlerPlugin;
use Spryker\Zed\MerchantApp\Communication\Plugin\MessageBroker\MerchantAppOnboardingMessageHandlerPlugin;
```

---

### Step 3: Update OMS configuration

**File:** `config/Shared/config_default.php`

Add the Stripe OMS process location and activate the state machine:

```php
$config[OmsConstants::PROCESS_LOCATION] = [
    OmsConfig::DEFAULT_PROCESS_LOCATION,
    APPLICATION_ROOT_DIR . '/vendor/spryker-eco/stripe/config/Zed/oms', // Add this line
];

$config[OmsConstants::ACTIVE_PROCESSES] = [
    // Replace ForeignPaymentStateMachine01 and ForeignPaymentB2CStateMachine01 with the Stripe process
    'StripeManual01', // or StripeManualMarketplace01 for marketplace projects
];

$config[SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING] = [
    \SprykerEco\Shared\Stripe\StripeConfig::PAYMENT_PROVIDER_NAME => 'StripeManual01', // or StripeManualMarketplace01 for marketplace projects
];
```

---

### Step 4: Register Stripe OMS command and condition plugins

**File:** `src/Pyz/Zed/Oms/OmsDependencyProvider.php`

Add Stripe command plugins to `extendCommandPlugins()`:

```php
use SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command\StripeCancelCommandPlugin;
use SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command\StripeCaptureCommandPlugin;
use SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command\StripeRefundCommandPlugin;

// In extendCommandPlugins():
$commandCollection->add(new StripeCaptureCommandPlugin(), 'Stripe/Capture');
$commandCollection->add(new StripeRefundCommandPlugin(), 'Stripe/Refund');
$commandCollection->add(new StripeCancelCommandPlugin(), 'Stripe/Cancel');

// ----- for Marketplace only
$commandCollection->add(new MerchantPayoutCommandByOrderPlugin(), 'SalesPaymentMerchant/Payout');
$commandCollection->add(new MerchantPayoutReverseCommandByOrderPlugin(), 'SalesPaymentMerchant/ReversePayout');
```

> **Note:** `StripeCaptureCommandPlugin` always captures the full authorized amount regardless of which items are in the OMS batch. Stripe allows only one capture per PaymentIntent — any remaining uncaptured amount is automatically released after the first capture. Items canceled after capture are handled via refunds.

Also add payment conditions to `extendConditionPlugins()`:

```php
// In extendConditionPlugins():
$conditionCollection->add(new IsPaymentAppPaymentStatusAuthorizationFailedConditionPlugin(), 'Payment/IsAuthorizationFailed');
$conditionCollection->add(new IsPaymentAppPaymentStatusAuthorizedConditionPlugin(), 'Payment/IsAuthorized');
$conditionCollection->add(new IsPaymentAppPaymentStatusCanceledConditionPlugin(), 'Payment/IsCanceled');
$conditionCollection->add(new IsPaymentAppPaymentStatusCancellationFailedConditionPlugin(), 'Payment/IsCancellationFailed');
$conditionCollection->add(new IsPaymentAppPaymentStatusCapturedConditionPlugin(), 'Payment/IsCaptured');
$conditionCollection->add(new IsPaymentAppPaymentStatusCaptureFailedConditionPlugin(), 'Payment/IsCaptureFailed');
$conditionCollection->add(new IsPaymentAppPaymentStatusCaptureRequestedConditionPlugin(), 'Payment/IsCaptureRequested');
$conditionCollection->add(new IsPaymentAppPaymentStatusOverpaidConditionPlugin(), 'Payment/IsOverpaid');
$conditionCollection->add(new IsPaymentAppPaymentStatusUnderpaidConditionPlugin(), 'Payment/IsUnderpaid');
$conditionCollection->add(new IsPaymentAppPaymentStatusRefundedConditionPlugin(), 'Payment/IsRefunded');
$conditionCollection->add(new IsPaymentAppPaymentStatusRefundFailedConditionPlugin(), 'Payment/IsRefundFailed');

// ------- for Marketplace only
$conditionCollection->add(new IsMerchantPaidOutConditionPlugin(), 'SalesPaymentMerchant/IsMerchantPaidOut');
$conditionCollection->add(new IsMerchantPayoutReversedConditionPlugin(), 'SalesPaymentMerchant/IsMerchantPayoutReversed');
```

---

### Step 5: Register the Stripe payout transmission plugin (marketplace only)

**File:** `src/Pyz/Zed/SalesPaymentMerchant/SalesPaymentMerchantDependencyProvider.php`

Register `StripePayoutTransmissionPlugin` so the `SalesPaymentMerchant` module routes merchant payouts and payout reversals through direct Stripe Connect API calls instead of the default PSP App HTTP endpoint:

```php
use SprykerEco\Zed\Stripe\Communication\Plugin\SalesPaymentMerchant\StripePayoutTransmissionPlugin;

// In getMerchantPayoutTransmissionPlugins():
return [
    new StripePayoutTransmissionPlugin(),
];
```

The OMS subprocesses `StripeMerchantPayout01.xml` and `StripeMerchantPayoutReverse01.xml` use the generic `SalesPaymentMerchant/Payout` and `SalesPaymentMerchant/ReversePayout` commands.
`StripePayoutTransmissionPlugin` intercepts those commands and executes Stripe Connect transfers (forward payouts) or transfer reversals (payout reversals) directly via the Stripe API.

---

### Step 6: Register Stripe checkout post-save plugin

**File:** `src/Pyz/Zed/Checkout/CheckoutDependencyProvider.php`

```php
use SprykerEco\Zed\Stripe\Communication\Plugin\Checkout\StripeCheckoutPostSavePlugin;

// In getCheckoutPostHooks():
new StripeCheckoutPostSavePlugin(),
```

---

### Step 7: Register Stripe Yves checkout plugins

**File:** `src/Pyz/Yves/CheckoutPage/CheckoutPageDependencyProvider.php`

```php
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Yves\Stripe\Plugin\StepEngine\StripeStepHandlerPlugin;
use SprykerEco\Yves\Stripe\Plugin\StepEngine\StripeSubFormPlugin;

// In extendPaymentMethodHandler():
$paymentMethodHandler->add(new StripeStepHandlerPlugin(), SharedStripeConfig::PAYMENT_METHOD_NAME);

// In extendSubFormPluginCollection():
$paymentSubFormPluginCollection->add(new StripeSubFormPlugin());
```

---

### Step 8: Register payment method filter plugin (optional)

Only required if you need custom filtering logic, for example, to hide Stripe or other payment methods based on the Quote. Extend `StripePaymentMethodFilterPlugin` with your filtering logic.

**File:** `src/Pyz/Zed/Payment/PaymentDependencyProvider.php`

```php
use SprykerEco\Zed\Stripe\Communication\Plugin\Payment\StripePaymentMethodFilterPlugin;

// In getPaymentMethodFilterPlugins():
new StripePaymentMethodFilterPlugin(),
```

---

### Step 9: Register Stripe routes

**File:** `src/Pyz/Yves/Router/RouterDependencyProvider.php`

```php
use SprykerEco\Yves\Stripe\Plugin\Router\StripeRouteProviderPlugin;

// In getRouteProvider():
new StripeRouteProviderPlugin(),
```

---

### Step 10: Add Stripe payment form to the checkout payment template

**File:** `src/Pyz/Yves/CheckoutPage/Theme/default/views/payment/payment.twig`

Add the Stripe form entry to the `customForms` map:

```twig
{% define data = {
    customForms: {
        'Payone/credit_card': ['credit-card', 'payone'],
        'Stripe/stripe': ['stripe'],
    },
} %}
```

---

### Step 11: Register the marketplace installer plugin (marketplace only)

**File:** `src/Pyz/Zed/Installer/InstallerDependencyProvider.php`

```php
use SprykerEco\Zed\Stripe\Communication\Plugin\Installer\StripeMarketplaceInstallerPlugin;

// In getInstallerPlugins():
new StripeMarketplaceInstallerPlugin(),
```

---

### Step 12: Allow Stripe controllers in the Merchant Portal security config (marketplace only)

By default, Merchant Portal rejects all routes that do not match the portal pattern. The `/stripe/*` endpoint must be excluded from authentication.

**File:** `src/Pyz/Zed/SecurityMerchantPortalGui/SecurityMerchantPortalGuiConfig.php`

```php
<?php

namespace Pyz\Zed\SecurityMerchantPortalGui;

use Spryker\Zed\SecurityMerchantPortalGui\SecurityMerchantPortalGuiConfig as SprykerSecurityMerchantPortalGuiConfig;

class SecurityMerchantPortalGuiConfig extends SprykerSecurityMerchantPortalGuiConfig
{
    protected const MERCHANT_PORTAL_ROUTE_PATTERN = '^/((.+)-merchant-portal-gui|multi-factor-auth-merchant-portal/(merchant-user|user-management)|_profiler|stripe)/';

    protected const IGNORABLE_PATH_PATTERN = '^/(security-merchant-portal-gui|multi-factor-auth-merchant-portal|_profiler|stripe)';
}
```

---

### Step 13: Configure Stripe credentials

**File:** `config/Shared/config_local.php`

```php
use SprykerEco\Shared\Stripe\StripeConstants;

$config[StripeConstants::STRIPE_SECRET_KEY] = 'sk_live_***';       // from Stripe Dashboard → API keys
$config[StripeConstants::STRIPE_PUBLISHABLE_KEY] = 'pk_live_***';  // from Stripe Dashboard → API keys
$config[StripeConstants::STRIPE_WEBHOOK_SECRET] = 'whsec_***';     // from Stripe Dashboard → Webhooks
```

---

### Step 14: Import payment methods

The module provides pre-configured data import files for payment methods, store assignments, and translations.

#### Option 1: Import using the module's configuration file

> Recommended during the development phase.

```bash
docker/sdk cli
vendor/bin/console data:import --config=vendor/spryker-eco/stripe/data/import/stripe.yml
```

#### Option 2: Copy files and import individually

> Recommended once the integration is tested and tuned.

Copy the file contents from `vendor/spryker-eco/stripe/data/import/*.csv` to the matching files in your project under `data/import/common/common/`. Then run:

```bash
docker/sdk cli
vendor/bin/console data:import payment-method
vendor/bin/console data:import payment-method-store
vendor/bin/console data:import glossary
```

#### Customize payment methods

Before importing, you can customize the payment method data:

**File:** `vendor/spryker-eco/stripe/data/import/payment_method.csv`
- Update payment method names
- Enable/disable methods
- Add additional payment methods

**File:** `vendor/spryker-eco/stripe/data/import/payment_method_store.csv`
- Configure which stores each payment method is available in

**File:** `vendor/spryker-eco/stripe/data/import/glossary.csv`
- Customize translations for payment method names
- Add additional locales

#### Verify import

Check the Back Office:
1. Go to **Administration → Payment → Payment Methods**
2. Verify payment methods appear with correct names and provider
3. Verify methods are assigned to the correct stores
4. Go to **Administration → Glossary** and verify translations
5. Enable the method for the store and validate the Storefront checkout payment step

---

### Step 15: Run code generation and database migration

```bash
# Apply database schema changes (spy_stripe_payment, spy_stripe_merchant)
vendor/bin/console propel:install

# Generate new transfer objects
vendor/bin/console transfer:generate

# Run marketplace installer
vendor/bin/console setup:init-db

# Run marketplace ACL updater
vendor/bin/console acl-entity:synchronize
```

---

### Step 16: Register the Stripe webhook in the Stripe Dashboard

Point the webhook to your storefront notification endpoint:

```
https://your-domain.com/stripe/notification
```

Select at minimum these event types:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `payment_intent.canceled`
- `charge.refunded`
- `account.updated` (marketplace only)

Copy the **Signing secret** from the webhook configuration and set it as `STRIPE_WEBHOOK_SECRET` in your config.

> **Debugging:** Each processed webhook event stores the raw Stripe object details (PaymentIntent, Charge, or Refund) as JSON in `spy_payment_app_payment_status_history.context`. This makes it easy to trace exactly what Stripe reported at the time of each status change.

---

### Step 17: Register your domain in the Stripe Dashboard

Google Pay and Apple Pay require your site domains to be registered in Stripe. Go to [Settings > Payments > Payment method domains](https://dashboard.stripe.com/settings/payment_method_domains) and add your domains.

---

### Step 18: Verify the installation

1. Place a test order and confirm the payment step renders Stripe Elements.
2. Complete payment and confirm the order transitions to the authorized state.
3. Capture the order from the Back Office and verify the `Stripe/Capture` OMS command triggers successfully (full authorized amount is captured).
4. Send a test webhook event from the Stripe Dashboard and confirm the order status updates.
5. (Marketplace only) Log in to Merchant Portal with a merchant user and go to Payment Settings. The Stripe section should be visible.
6. (Marketplace only) Complete merchant onboarding to Stripe (Stripe Connect must be enabled on the account).
7. (Marketplace only) Trigger a payout and verify the Stripe Connect transfer appears in the Stripe Express Dashboard under the merchant's connected account (also visible under Transactions > Transfers in the main Stripe account).

---

## Development

To check/fix code style and run static analysis, use:

```bash
composer cs-fix # can be used standalone
cd project-root # only works together with Spryker project (uses autoloader from it)
vendor/bin/phpstan analyze -c vendor/spryker-eco/stripe/phpstan.neon vendor/spryker-eco/stripe
```

### Webhooks in local development environment

1. Install [Stripe CLI](https://docs.stripe.com/stripe-cli).
2. Run:
```bash
stripe listen --forward-to http://yves.eu.spryker.local/stripe/notification
```
3. Copy the signing secret from the output and set it as `STRIPE_WEBHOOK_SECRET`.

Configure the Stripe module:

```php
// config/Shared/config_local.php

// Get these from https://dashboard.stripe.com/apikeys and Dashboard > Webhooks
$config[StripeConstants::STRIPE_SECRET_KEY] = 'sk_test_***';
$config[StripeConstants::STRIPE_PUBLISHABLE_KEY] = 'pk_test_***';
$config[StripeConstants::STRIPE_WEBHOOK_SECRET] = 'whsec_***';
```

> **Note:** For testing Google Pay and Apple Pay you need to register your external domain in Stripe [Payment method domains](https://dashboard.stripe.com/test/settings/payment_method_domains?enabled=true).
> You can use `ngrok` for this:
> 1. Download and install ngrok from https://ngrok.com/.
> 2. Run `ngrok http --host-header=yves.eu.spryker.local 80` to expose your local environment to the internet (a dynamic domain will be assigned).
>
> To use a personal dev domain, register it at https://dashboard.ngrok.com/domains and then run:
> ```bash
> ngrok http --domain=your-personal-domain.ngrok-free.dev --host-header=yves.eu.spryker.local 80
> ```
> 3. Open https://your-personal-domain.ngrok-free.dev in the browser and go to the Stripe payment page.

---

## Support

For issues or questions:
- Check [Spryker Stripe documentation](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/stripe/stripe)
- Review [Stripe documentation](https://docs.stripe.com/)
- Contact Spryker support

## License

This module is licensed under the same license as [Spryker Commerce OS](LICENSE).
