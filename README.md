# Stripe Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/algolia/v/stable.svg)](https://packagist.org/packages/spryker-eco/algolia)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)


## Integration

## Data Import

### Import Payment Methods

The module provides pre-configured data import files for payment methods, store assignments, and translations.

#### Option 1: Import Using Module's Configuration File

```bash
docker/sdk cli
vendor/bin/console data:import --config=vendor/spryker-eco/stripe/data/import/stripe.yml
```

#### Option 2: Copy File Content and Import Individually
Copy file's content from `vendor/spryker-eco/stripe/data/import/*.csv` to the same files in you project `data/import/common/common/`.
Then run:

```bash
docker/sdk cli
vendor/bin/console data:import payment-method
vendor/bin/console data:import payment-method-store
vendor/bin/console data:import glossary
```

#### Option 3: Add to Project's Main Import Configuration

Add the import actions to your project's main data import configuration file and include in your regular import pipeline.

### Customize Payment Methods

Before importing, you can customize the payment method data:

**File:** `vendor/your-org/your-psp/data/import/payment_method.csv`
- Update payment method names
- Enable/disable methods
- Add additional payment methods

**File:** `vendor/your-org/your-psp/data/import/payment_method_store.csv`
- Configure which stores each payment method is available in

**File:** `vendor/your-org/your-psp/data/import/glossary.csv`
- Customize translations for payment method names
- Add additional locales

### Verify Import

Check Back Office:
1. Go to **Administration → Payment → Payment Methods**
2. Verify payment methods appear with correct names and provider
3. Verify methods are assigned to correct stores
4. Go to **Administration → Glossary** and verify translations
5. Enable method for the store and validate Storefront Checkout payment step



## For Webhooks in local development environments

1. Install [Stripe CLI](https://docs.stripe.com/stripe-cli)
2. Run:
```bash
stripe listen --forward-to http://yves.eu.spryker.local/stripe/notification
```
3. Copy signing secret from the output. Use it for `STRIPE_WEBHOOK_SECRET`.

Configure Stripe module:

```php
// config/Shared/config_local.php

// Get these from https://dashboard.stripe.com/apikeys and Dashboard > Webhooks
$config[StripeConstants::STRIPE_SECRET_KEY] = 'sk_test_***';
$config[StripeConstants::STRIPE_PUBLISHABLE_KEY] = 'pk_test_***';
$config[StripeConstants::STRIPE_WEBHOOK_SECRET] = 'whsec_***';
$config[StripeConstants::STRIPE_BUSINESS_MODEL] = 'direct'; // or marketplace
```

>Note: For testing Google Pay and Apple Pay you need to register your external domain in Stripe [Payment method domains](https://dashboard.stripe.com/test/settings/payment_method_domains?enabled=true).
> You can use `ngrok` for this.
> 1. Download and install ngrok from https://ngrok.com/.
> 2. Run `ngrok http --host-header=yves.eu.spryker.local 80` to expose your local environment to the internet (dynamic domain will be assigned).
>
> To use personal dev domain you need to register it in https://dashboard.ngrok.com/domains and then run
> ```bash
> ngrok http --domain=your-personal-domain.ngrok-free.dev --host-header=yves.eu.spryker.local 80
> ```
> 3. Open https://your-personal-domain.ngrok-free.dev in the browser and go to Stripe payment page.

## Support

For issues or questions:
- Check [Spryker Stripe documentation](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/stripe/stripe)
- Review [Stripe documentation](https://docs.stripe.com/)
- Contact Spryker support

## Development

To check/fix code style and run static analysis, use:

```bash
composer cs-fix # can be used standalone
cd project-root # only works together with Spryker project (uses autoloader from it)
vendor/bin/phpstan analyze -c vendor/spryker-eco/stripe/phpstan.neon vendor/spryker-eco/stripe
```

For test execution, check the details in [tests/README.md](tests/README.md) file.


## License

This module is licensed under the same license as [Spryker Commerce OS](LICENSE).
