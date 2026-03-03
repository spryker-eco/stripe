# Stripe Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/algolia/v/stable.svg)](https://packagist.org/packages/spryker-eco/algolia)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

The Algolia module provides seamless integration between Spryker Commerce OS and Algolia search service, enabling real-time synchronization of products and CMS pages to Algolia indices for fast, relevant search experiences.

## Features

- 🔄 **Real-time synchronization** of products and CMS pages to Algolia
- 🎯 **Event-driven architecture** using Spryker's Publisher module
- 🌍 **Multi-locale and multi-store** support
- 📦 **Batch export** capabilities for initial data sync
- 🔍 **Search API integration** for frontend and backend
- ⚙️ **Configurable event subscriptions** per entity type
- 🏗️ **Modular design** with optional module support
- 🔌 **Custom entity index mapping** for searching any custom entity in Algolia

## Table of Contents

- [Installation](#installation)
- [Real-time Synchronization](#real-time-synchronization)
  - [Product Publisher Plugins](#product-publisher-plugins)
  - [CMS Page Publisher Plugins](#cms-page-publisher-plugins)
- [Full Indexing](#full-indexing)
- [Custom Entity Index Mapping](#custom-entity-index-mapping)
- [Configuration](#configuration)
- [Migration from ACP Algolia App](#migration-from-acp-algolia-app)
- [Troubleshooting](#troubleshooting)
- [Development](#development)

## Installation

```bash
composer require spryker-eco/algolia
```

**Configure Algolia credentials** in your config files:
```php
// config/Shared/config_default.php or config_local.php (for local development)
use SprykerEco\Shared\Algolia\AlgoliaConstants;

$config[AlgoliaConstants::IS_ACTIVE] = true;
$config[AlgoliaConstants::APPLICATION_ID] = getenv('ALGOLIA_APPLICATION_ID');
$config[AlgoliaConstants::ADMIN_API_KEY] = getenv('ALGOLIA_WRITE_API_KEY');
$config[AlgoliaConstants::SEARCH_ONLY_API_KEY] = getenv('ALGOLIA_SEARCH_API_KEY');
// Add if you use one Algolia account for multiple environments, default is "production"
// $config[AlgoliaConstants::TENANT_IDENTIFIER] = 'john';
```


### Step 1: Enable Console Command

File: `src/Pyz/Zed/Console/ConsoleDependencyProvider.php`

```php
<?php

namespace Pyz\Zed\Console;

use Spryker\Zed\Console\ConsoleDependencyProvider as SprykerConsoleDependencyProvider;
use SprykerEco\Zed\Algolia\Communication\Console\AlgoliaEntityExportConsole;

class ConsoleDependencyProvider extends SprykerConsoleDependencyProvider
{
    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Symfony\Component\Console\Command\Command>
     */
    protected function getConsoleCommands(Container $container): array
    {
        $commands = [
            // ... existing commands

            // Add Algolia export command
            new AlgoliaEntityExportConsole(),
        ];

        return $commands;
    }
}
```

### Step 2: Configure Entity Exporter Plugins

File: `src/Pyz/Zed/Algolia/AlgoliaDependencyProvider.php`

```php
<?php

namespace Pyz\Zed\Algolia;

use SprykerEco\Zed\Algolia\AlgoliaDependencyProvider as SprykerEcoAlgoliaDependencyProvider;
use SprykerEco\Zed\Algolia\Communication\Plugin\Algolia\CmsPageAlgoliaEntityExporterPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Algolia\ProductAlgoliaEntityExporterPlugin;

class AlgoliaDependencyProvider extends SprykerEcoAlgoliaDependencyProvider
{
    /**
     * @return array<\SprykerEco\Zed\Algolia\Dependency\Plugin\AlgoliaEntityExporterPluginInterface>
     */
    protected function getAlgoliaEntityExporterPlugins(): array
    {
        return [
            new ProductAlgoliaEntityExporterPlugin(),
            new CmsPageAlgoliaEntityExporterPlugin(),
            // Add more entity exporters here
        ];
    }
}
```

### Step 3: Configure Search Adapter Plugin

File: `src/Pyz/Client/Search/SearchDependencyProvider.php`

```php
<?php

namespace Pyz\Client\Search;

use Spryker\Client\Search\SearchDependencyProvider as SprykerSearchDependencyProvider;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchAdapterPlugin;

class SearchDependencyProvider extends SprykerSearchDependencyProvider
{
    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchAdapterPluginInterface>
     */
    protected function getClientAdapterPlugins(): array
    {
        return [
            new AlgoliaSearchAdapterPlugin(),
            // ... other search adapters
        ];
    }
}
```

### Step 4: Configure Catalog Search Query Plugins

>Note: Also requires `\Pyz\Shared\Algolia\AlgoliaConfig::isSearchInFrontendEnabledForProducts()` to be set to `true`.

>Note 2: Integration heavily depends on SearchHttp module plugins, so they have to be also enabled in `src/Pyz/Client/Catalog/CatalogDependencyProvider.php`,
> [see the integration guide](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/third-party-integrations/algolia/integrate-algolia#configure-modules-and-their-behavior).

File: `src/Pyz/Client/Catalog/CatalogDependencyProvider.php`

```php
<?php

namespace Pyz\Client\Catalog;

use Spryker\Client\Catalog\CatalogDependencyProvider as SprykerCatalogDependencyProvider;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchQueryPlugin;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSuggestionSearchQueryPlugin;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaProductConcreteSearchQueryPlugin;

class CatalogDependencyProvider extends SprykerCatalogDependencyProvider
{
    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface>
     */
    protected function createCatalogSearchQueryPluginVariants(): array
    {
        return [
            new AlgoliaSearchQueryPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface>
     */
    protected function createSuggestionQueryPluginVariants(): array
    {
        return [
            new AlgoliaSuggestionSearchQueryPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface>
     */
    protected function createProductConcreteCatalogSearchQueryPluginVariants(): array
    {
        return [
            new AlgoliaProductConcreteSearchQueryPlugin(),
        ];
    }
}
```

### Step 5: Configure CMS Page Search Query Plugin (Optional)

>Note: Also requires `\Pyz\Shared\Algolia\AlgoliaConfig::isSearchInFrontendEnabledForCmsPages()` to be set to `true`.

>Note 2: Integration heavily depends on SearchHttp module plugins, so they have to be also enabled in
> `src/Pyz/Client/SearchHttp/SearchHttpDependencyProvider.php` and `src/Pyz/Client/CmsPageSearch/CmsPageSearchDependencyProvider.php`,
 [see the integration guide](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/third-party-integrations/algolia/integrate-algolia#configure-the-cmspagesearch-module).


File: `src/Pyz/Client/CmsPageSearch/CmsPageSearchDependencyProvider.php`

```php
<?php

namespace Pyz\Client\CmsPageSearch;

use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\CmsPageSearch\CmsPageSearchConfig;
use Spryker\Client\CmsPageSearch\CmsPageSearchDependencyProvider as SprykerCmsPageSearchDependencyProvider;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchQueryPlugin;

class CmsPageSearchDependencyProvider extends SprykerCmsPageSearchDependencyProvider
{
    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface>
     */
    protected function getCmsPageSearchQueryPlugins(): array
    {
        return [
            new AlgoliaSearchQueryPlugin(
                (new SearchContextTransfer())
                    ->setSourceIdentifier(CmsPageSearchConfig::SOURCE_IDENTIFIER_CMS_PAGE),
            ),
            // ... other search query plugins
        ];
    }
}
```

### Step 6: Generate Transfers

```bash
vendor/bin/console transfer:generate
```

### Step 7: Verify Installation

```bash
# List available commands (should show algolia:entity-export)
vendor/bin/console | grep algolia

vendor/bin/console algolia:entity-export
```

### Step 8: Send data to Algolia

```bash
vendor/bin/console algolia:entity-export --all

# Or export specific entity types
vendor/bin/console algolia:entity-export product

vendor/bin/console algolia:entity-export cms-page
```

See [Full Indexing](#full-indexing) section for more details and scheduling options.

See [Real-time Synchronization](#step-10-configure-real-time-synchronization) section for real-time updates.

### Step 9: Verify data in the Algolia Dashboard

1. Log in to Algolia
2. Check created indexes and data inside (Search section).
3. Try searches from the Algolia Dashboard.
4. Tune index settings ([facets](https://www.algolia.com/doc/guides/managing-results/refine-results/faceting/), [searchable attributes](https://www.algolia.com/doc/guides/managing-results/must-do/searchable-attributes/)) as needed.

### Step 10: Configure Real-time Synchronization

Complete Integration Example:

```php
<?php

namespace Pyz\Zed\Publisher;

use Spryker\Zed\Publisher\PublisherDependencyProvider as SprykerPublisherDependencyProvider;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductAbstractPublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductConcretePublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductConcreteDeletePublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\AlgoliaCmsPagePublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\AlgoliaCmsPageVersionPublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\AlgoliaCmsPageDeletePublisherPlugin;

class PublisherDependencyProvider extends SprykerPublisherDependencyProvider
{
    protected function getPublisherPlugins(): array
    {
        return [
            // Algolia product publishers
            new AlgoliaProductConcretePublisherPlugin(),
            new AlgoliaProductAbstractPublisherPlugin(),
            new AlgoliaProductConcreteDeletePublisherPlugin(),

            // Algolia CMS page publishers
            new AlgoliaCmsPagePublisherPlugin(),
            new AlgoliaCmsPageVersionPublisherPlugin(),
            new AlgoliaCmsPageDeletePublisherPlugin(),
        ];
    }
}
```
See [Real-time Synchronization](#real-time-synchronization) section for details on each plugin and its subscribed events.

### Step 11: Enable Search in Frontend & API
> ⚠️ **WARNING**: Please ensure you have data in the Algolia indices before enabling search in the frontend; otherwise, search will return no results.


Enable product and/or CMS page search in the frontend for Algolia integration at the project level.

File: `src/Pyz/Client/Algolia/AlgoliaConfig.php`

```php
<?php

namespace Pyz\Client\Algolia;

use SprykerEco\Client\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    /**
     * Enable product search in the frontend.
     */
    public function isSearchInFrontendEnabledForProducts(): bool
    {
        return true;
    }

    /**
     * Enable CMS page search in the frontend.
     */
    public function isSearchInFrontendEnabledForCmsPages(): bool
    {
        return true;
    }
}
```

---

## Real-time Synchronization

### Product Publisher Plugins

Located in: `SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\`

#### 1. AlgoliaProductConcretePublisherPlugin

**Purpose**: Publishes product concrete (variant) data to Algolia when products are created or updated.

**Default Subscribed Events**:
- Product creation/update events
- Product localized attributes changes
- Product images changes
- Product bundles changes (if ProductBundleStorage exists)
- Product prices changes (if PriceProduct exists)
- Product search data changes (if ProductSearch exists)

**Behavior**:
- Publishes or updates product concrete data in Algolia indices upon relevant events.
- Handles multi-store and multi-locale data.

#### 2. AlgoliaProductAbstractPublisherPlugin

**Purpose**: Publishes all concrete products of a product abstract when abstract-level data changes.

**Default Subscribed Events**:
- Product abstract updates
- Category assignments
- Product labels
- Reviews
- Images
- Price changes (if PriceProduct exists and enabled in the configuration)

**Behavior**:
- Triggers re-indexing of all related concrete products in Algolia when abstract-level data changes.

#### 3. AlgoliaProductConcreteDeletePublisherPlugin

**Purpose**: Removes deleted products from Algolia indices.

**Default Subscribed Events**:
- PRODUCT_CONCRETE_UNPUBLISH
- ENTITY_SPY_PRODUCT_DELETE

**Behavior**:
- Removes product concrete data from Algolia indices when products are deleted or unpublished.

---

### CMS Page Publisher Plugins

Located in: `SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\`

#### 1. AlgoliaCmsPagePublisherPlugin

**Purpose**: Publishes CMS page data to Algolia when pages are created or updated.

**Default Subscribed Events**:
- ENTITY_SPY_CMS_PAGE_UPDATE

**Behavior**:
- Fetches full CMS page data including latest version
- Checks if page is active AND searchable before publishing
- Extracts locale-specific flattened CMS content
- Sends complete page data to Algolia for indexing
- Removes pages from all relevant indices if page is inactive or not searchable

#### 2. AlgoliaCmsPageVersionPublisherPlugin

**Purpose**: Publishes CMS pages when new versions are created or published.

**Default Subscribed Events**:
- CMS_VERSION_PUBLISH
- ENTITY_SPY_CMS_VERSION_CREATE

**Behavior**:
- Maps CMS version IDs to CMS page IDs
- Fetches CMS page and version data
- Extracts full page content with locale-specific data
- Publishes to Algolia with version metadata

---

## Full Indexing

### Usage Examples

```bash
# Export all products to Algolia
console algolia:entity:export product

# Export all CMS pages
console algolia:entity:export cms-page --store=DE

# Export for specific store
console algolia:entity:export product --locale=en_US

# Export with custom chunk size
console algolia:entity:export product --chunk-size=200

```

### Schedule Automatic Exports (Recommended)

For periodic full re-indexing, add a cron job to export entities to Algolia on a scheduled basis.

File: `config/Zed/cronjobs/jenkins.php`

```php
/* Algolia - Weekly full export */
$jobs[] = [
    'name' => 'algolia-export-products',
    'command' => $logger . '$PHP_BIN vendor/bin/console algolia:entity:export product',
    'schedule' => '0 2 * * 0',
    'enable' => true,
];

$jobs[] = [
    'name' => 'algolia-export-cms-pages',
    'command' => $logger . '$PHP_BIN vendor/bin/console algolia:entity:export cms-page',
    'schedule' => '30 2 * * 0',
    'enable' => true,
];
```

**Schedule explanation:**
- `0 2 * * 0` - Runs at 2:00 AM every Sunday (weekly)
- `30 2 * * 0` - Runs at 2:30 AM every Sunday (weekly)

**Note:** These cron jobs complement the real-time publisher plugins. The publishers handle incremental updates,
while the cron jobs ensure full data consistency by performing periodic complete exports.

---

## Custom Entity Index Mapping

The Algolia module supports searching custom entities that are already indexed in Algolia but are not natively supported by the module (like products or CMS pages). This feature allows you to integrate any custom entity search without creating new plugins or modules.

### When to Use

Use entity-to-index mapping when you:
- Have custom entities (e.g., documents, manufacturers, locations) already indexed in Algolia
- Want to search these entities from your Spryker storefront
- Don't want to create custom publisher plugins for simple read-only search

### Quick Setup

**Step 1:** Configure the mapping in your shared config:

```php
<?php

namespace Pyz\Shared\Algolia;

use SprykerEco\Shared\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    public function getEntityToIndexMappings(): array
    {
        return [
            [
                'sourceIdentifier' => 'document',
                'store' => 'DE',
                'locales' => ['de_DE'],
                'indexName' => 'documents_de',
            ],
            [
                'sourceIdentifier' => 'manufacturer',
                'store' => '*', // All stores
                'locales' => ['*'], // All locales
                'indexName' => 'manufacturers',
            ],
        ];
    }
}
```

**Step 2:** Create a search query plugin:

```php
<?php

namespace Pyz\Client\YourModule\Plugin\Search;

use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

/**
 * @method \Pyz\Client\YourModule\YourModuleFactory getFactory()
 */
class DocumentSearchQueryPlugin extends AbstractPlugin implements QueryInterface
{
    protected const SOURCE_IDENTIFIER = 'document';

    protected ?SearchContextTransfer $searchContextTransfer = null;

    public function getSearchQuery()
    {
        // Your query logic
    }

    public function getSearchContext(): SearchContextTransfer
    {
        return $this->searchContextTransfer ?? (new SearchContextTransfer())
            ->setSourceIdentifier(static::SOURCE_IDENTIFIER);
    }

    public function setSearchContext(SearchContextTransfer $searchContextTransfer): void
    {
        $this->searchContextTransfer = $searchContextTransfer;
    }
}
```

**Step 3:** Use the plugin in your dependency provider and execute search.

For a complete implementation guide with examples, see [Custom Entity Index Mapping Guide](docs/CUSTOM_ENTITY_INDEX_MAPPING.md).

---

## Configuration

### Available Configuration Methods

**Product Events:**
- `getProductConcreteSubscribedEvents()` - Product variant events
- `getProductAbstractSubscribedEvents()` - Product abstract events
- `getProductConcreteUnpublishSubscribedEvents()` - Delete events

**CMS Page Events:**
- `getCmsPageUpdateSubscribedEvents()` - Page update events
- `getCmsPageVersionPublishSubscribedEvents()` - Version publish events

**Search:**
- `isSearchInFrontendEnabledForProducts()` - Enable product search in frontend
- `isSearchInFrontendEnabledForCmsPages()` - Enable CMS page search in frontend

**Insights & Analytics & Personalization:**
- `getIsPersonalizationEnabled()` - Enable/disable Algolia Personalization for search. This feature requires a premium Algolia plan.
- `getProjectMappingFacets()` - Facet names mapping for Algolia Insights event tracking (via TraceableEventWidget).


### Default Event Subscriptions

All publisher plugins get their subscribed events from `AlgoliaConfig`. The config automatically includes events from optional modules if they exist:

**For Products:**
- All product abstract and product concrete events
- ProductBundle - Bundle events (if module exists)
- PriceProduct - Price events (if module exists)
- ProductLabel - Label events (if module exists)
- ProductReview - Review events (if module exists)

**For CMS Pages:**
- CMS - All CMS page and version events

### Customizing Event Subscriptions

Extend `AlgoliaConfig` in your project to customize events:

```php
<?php

namespace Pyz\Zed\Algolia;

use SprykerEco\Zed\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    public function getProductConcreteSubscribedEvents(): array
    {
        // Completely override events
        return [
            'Product.product_concrete.publish',
            'Entity.spy_product.update',
        ];
    }

    public function getCmsPageUpdateSubscribedEvents(): array
    {
        // Extend parent events
        $events = parent::getCmsPageUpdateSubscribedEvents();
        $events[] = 'YourCustom.custom_event';
        return $events;
    }

    public function getDefaultExportChunkSize(): int
    {
        return 500; // Custom chunk size for exports
    }
}
```

---

## Architecture

### Data Flow

```
Spryker Events (Back Office/API changes/Data Import)
           ↓
Publisher Module (Queue-based processing)
           ↓
Algolia Publisher Plugins
    ├── Product Publishers
    └── CMS Page Publishers
           ↓
AlgoliaFacade → Mappers → Indexers → API Client
           ↓
Algolia Search Service
```

### Event Consolidation

**Products:**
- Concrete events: Direct changes to variants
- Abstract events: Changes affecting all variants
- The abstract publisher fetches all active concrete products

**CMS Pages:**
- Page update events: Direct entity changes
- Version publish events: New version creation
- Delete events: Page removal (unpublish)
- Both update and version plugins ensure pages stay current

---

## Troubleshooting

### No entity types available

**Problem**: "No entity exporters are registered"

**Solution**:
1. Ensure plugins are registered in `AlgoliaDependencyProvider::getAlgoliaEntityExporterPlugins()`
2. Check the dependency provider is in `Pyz` namespace if extended
3. Clear cache: `console cache:empty-all`

### Transfer not found

**Problem**: `Class 'Generated\Shared\Transfer\AlgoliaExportCriteriaTransfer' not found`

**Solution**:
```bash
console transfer:generate
```

### Events not triggering

**Problem**: Changes not appearing in Algolia

**Solution**:
1. Check `AlgoliaConfig::getIsActive()` returns `true`
2. Verify publisher plugins are registered in `PublisherDependencyProvider`
3. Check queue workers are running:
   ```bash
   console queue:task:start publish
   ```
4. Debug publishing with Xdebug `docker/sdk console -x queue:task:start publish` or using logs.


### Search requests are failing

**Problem**: Search queries return errors or no results

**Solution**:
1. Verify Algolia credentials in the config are correct
2. Ensure indices exist in Algolia dashboard
3. Disable personalization `getIsPersonalizationEnabled()` if you do not use an Algolia premium plan.

---

## Migration from ACP Algolia App

If migrating from MessageBroker-based [Algolia ACP App](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/third-party-integrations/algolia/algolia):

>Note: The logic of data synchronization remains the same, so if you don't want to re-synchronize all data to Algolia, just use TENANT_IDENTIFIER the same as ACP tenant ID:
> ```php
> $config[AlgoliaConstants::TENANT_IDENTIFIER] = getenv('SPRYKER_TENANT_IDENTIFIER'); // tenant-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxx
>```

### Step 1: Remove Old ACP Plugins and Configuration

#### 1a. Remove from `src/Pyz/Zed/Publisher/PublisherDependencyProvider.php`

Remove these imports and their usages:

```php
// Remove these use statements:
use Spryker\Zed\Cms\Communication\Plugin\Publisher\CmsPageUpdateMessageBrokerPublisherPlugin;
use Spryker\Zed\Cms\Communication\Plugin\Publisher\CmsPageVersionPublishedMessageBrokerPublisherPlugin;
use Spryker\Zed\Product\Communication\Plugin\Publisher\ProductAbstractUpdatedMessageBrokerPublisherPlugin;
use Spryker\Zed\Product\Communication\Plugin\Publisher\ProductConcreteCreatedMessageBrokerPublisherPlugin;
use Spryker\Zed\Product\Communication\Plugin\Publisher\ProductConcreteDeletedMessageBrokerPublisherPlugin;
use Spryker\Zed\Product\Communication\Plugin\Publisher\ProductConcreteExportedMessageBrokerPublisherPlugin;
use Spryker\Zed\Product\Communication\Plugin\Publisher\ProductConcreteUpdatedMessageBrokerPublisherPlugin;
```

Remove the methods that register them (e.g., `getProductMessageBrokerPlugins()`, `getCmsPageMessageBrokerPlugins()`) and their calls from `getPublisherPlugins()`.

Also keep `ProductCategoryProductUpdatedEventTriggerPlugin` and `ProductLabelProductUpdatedEventTriggerPlugin` — they are **not** ACP-specific and must remain (they will be re-registered under the new Algolia plugins method in Step 2).

#### 1b. Remove from `src/Pyz/Zed/MessageBroker/MessageBrokerDependencyProvider.php`

```php
// Remove these use statements and plugin instantiations:
use Spryker\Zed\Cms\Communication\Plugin\MessageBroker\CmsPageMessageHandlerPlugin;
use Spryker\Zed\Product\Communication\Plugin\MessageBroker\ProductExportMessageHandlerPlugin;
use Spryker\Zed\SearchHttp\Communication\Plugin\MessageBroker\SearchEndpointMessageHandlerPlugin;
```

#### 1c. Clean up `config/Shared/config_default.php`

Disable product publishing via MessageBroker:

```php
// Change this line:
$config[ProductConstants::PUBLISHING_TO_MESSAGE_BROKER_ENABLED] = $config[MessageBrokerConstants::IS_ENABLED];
// To:
$config[ProductConstants::PUBLISHING_TO_MESSAGE_BROKER_ENABLED] = false;
```

Add Algolia configuration at the end of the file:

```php
use SprykerEco\Shared\Algolia\AlgoliaConstants;

// Algolia
$config[AlgoliaConstants::APPLICATION_ID] = getenv('ALGOLIA_APPLICATION_ID');
$config[AlgoliaConstants::ADMIN_API_KEY] = getenv('ALGOLIA_WRITE_API_KEY');
$config[AlgoliaConstants::SEARCH_ONLY_API_KEY] = getenv('ALGOLIA_SEARCH_API_KEY');
$config[AlgoliaConstants::IS_ACTIVE] = $config[AlgoliaConstants::APPLICATION_ID] && $config[AlgoliaConstants::ADMIN_API_KEY] && $config[AlgoliaConstants::SEARCH_ONLY_API_KEY];
$config[AlgoliaConstants::TENANT_IDENTIFIER] = getenv('SPRYKER_TENANT_IDENTIFIER'); // old ACP tenant ID
```

#### 1d. Update `src/Pyz/Client/Search/SearchDependencyProvider.php`

Replace `SearchHttpSearchAdapterPlugin` with `AlgoliaSearchAdapterPlugin` and remove `SearchHttpSearchContextExpanderPlugin`:

```php
// Remove:
use Spryker\Client\SearchHttp\Plugin\Search\SearchHttpSearchAdapterPlugin;
use Spryker\Client\SearchHttp\Plugin\Search\SearchHttpSearchContextExpanderPlugin;

// Add:
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchAdapterPlugin;
```

In `getClientAdapterPlugins()` replace `new SearchHttpSearchAdapterPlugin()` with `new AlgoliaSearchAdapterPlugin()`.

In `getSearchContextExpanderPlugins()` remove `new SearchHttpSearchContextExpanderPlugin()`.

#### 1e. Update `src/Pyz/Client/Catalog/CatalogDependencyProvider.php`

Replace SearchHttp query plugins with Algolia equivalents:

```php
// Remove:
use Spryker\Client\SearchHttp\Plugin\Catalog\Query\ProductConcreteSearchHttpQueryPlugin;
use Spryker\Client\SearchHttp\Plugin\Catalog\Query\SearchHttpQueryPlugin;
use Spryker\Client\SearchHttp\Plugin\Catalog\Query\SuggestionSearchHttpQueryPlugin;

// Add:
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaProductConcreteSearchQueryPlugin;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchQueryPlugin;
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSuggestionSearchQueryPlugin;
```

Replace plugin instantiations:
- `createCatalogSearchQueryPluginVariants()`: `SearchHttpQueryPlugin` → `AlgoliaSearchQueryPlugin`
- `createSuggestionQueryPluginVariants()`: `SuggestionSearchHttpQueryPlugin` → `AlgoliaSuggestionSearchQueryPlugin`
- `createProductConcreteCatalogSearchQueryPluginVariants()`: `ProductConcreteSearchHttpQueryPlugin` → `AlgoliaProductConcreteSearchQueryPlugin`

#### 1f. Update `src/Pyz/Client/CmsPageSearch/CmsPageSearchDependencyProvider.php`

```php
// Remove:
use Spryker\Client\SearchHttp\Plugin\Catalog\Query\SearchHttpQueryPlugin;

// Add:
use SprykerEco\Client\Algolia\Plugin\Search\AlgoliaSearchQueryPlugin;
```

Replace `new SearchHttpQueryPlugin(...)` with `new AlgoliaSearchQueryPlugin(...)` in `getCmsPageSearchQueryPlugins()`.

### Step 2: Add New Algolia Integration

#### 2a. Register the console export command

File: `src/Pyz/Zed/Console/ConsoleDependencyProvider.php`

```php
use SprykerEco\Zed\Algolia\Communication\Console\AlgoliaEntityExportConsole;

// In getConsoleCommands():
new AlgoliaEntityExportConsole(),
```

#### 2b. Create `src/Pyz/Zed/Algolia/AlgoliaDependencyProvider.php`

```php
<?php

namespace Pyz\Zed\Algolia;

use SprykerEco\Zed\Algolia\AlgoliaDependencyProvider as SprykerEcoAlgoliaDependencyProvider;
use SprykerEco\Zed\Algolia\Communication\Plugin\Algolia\CmsPageAlgoliaEntityExporterPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Algolia\ProductAlgoliaEntityExporterPlugin;

class AlgoliaDependencyProvider extends SprykerEcoAlgoliaDependencyProvider
{
    /**
     * @return array<\SprykerEco\Zed\Algolia\Dependency\Plugin\AlgoliaEntityExporterPluginInterface>
     */
    protected function getAlgoliaEntityExporterPlugins(): array
    {
        return [
            new ProductAlgoliaEntityExporterPlugin(),
            new CmsPageAlgoliaEntityExporterPlugin(),
        ];
    }
}
```

#### 2c. Register real-time publisher plugins

File: `src/Pyz/Zed/Publisher/PublisherDependencyProvider.php`

Add a new `getAlgoliaPlugins()` method and call it from `getPublisherPlugins()`:

```php
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\AlgoliaCmsPagePublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\CmsPage\AlgoliaCmsPageVersionPublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductAbstractPublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductConcreteDeletePublisherPlugin;
use SprykerEco\Zed\Algolia\Communication\Plugin\Publisher\Product\AlgoliaProductConcretePublisherPlugin;

// ...

protected function getAlgoliaPlugins(): array
{
    return [
        // CMS pages
        new AlgoliaCmsPagePublisherPlugin(),
        new AlgoliaCmsPageVersionPublisherPlugin(),

        // Products
        new AlgoliaProductAbstractPublisherPlugin(),
        new AlgoliaProductConcretePublisherPlugin(),
        new AlgoliaProductConcreteDeletePublisherPlugin(),
        new ProductCategoryProductUpdatedEventTriggerPlugin(),
        new ProductLabelProductUpdatedEventTriggerPlugin(),
    ];
}
```

#### 2d. Enable frontend search

Create `src/Pyz/Client/Algolia/AlgoliaConfig.php`:

```php
<?php

namespace Pyz\Client\Algolia;

use SprykerEco\Client\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    public function isSearchInFrontendEnabledForProducts(): bool
    {
        return true;
    }

    public function isSearchInFrontendEnabledForCmsPages(): bool
    {
        return true;
    }
}
```

#### 2e. Generate transfers

```bash
vendor/bin/console transfer:generate
```

### Step 3: Initial Data Export and Verify

```bash
# Run full export to populate Algolia indices
vendor/bin/console algolia:entity-export --all
```

- No data schema migration needed — the data structure is the same as the ACP app
- If you want to reuse existing Algolia indices (avoid re-indexing), set `TENANT_IDENTIFIER` to match the ACP tenant ID (see note at top of this section)
- Configure schedule for periodic exports (see [Schedule Automatic Exports](#schedule-automatic-exports-recommended))
- Test a product update in Back Office and verify the change appears in Algolia
- Test a CMS page publish in Back Office and verify the change appears in Algolia
- Check Algolia dashboard for indexed content

### Benefits of Migration

- ✅ Direct integration (no MessageBroker overhead)
- ✅ Simpler architecture
- ✅ Better performance
- ✅ Batch indexing support
- ✅ Configuration and extensibility

---

## Performance Considerations

### Products
- Published asynchronously via the queue system
- Multiple events for the same product are deduplicated
- Events from optional modules are only registered if installed
- Use `AlgoliaConfig` to limit events if needed

### CMS Pages
- Published asynchronously via the queue system
- Only active AND searchable pages indexed
- Not searchable or inactive pages removed from indices

### General
- All plugins check `AlgoliaConfig::getIsActive()` before subscribing
- If Algolia is disabled, no events are processed
- Initial export uses configurable batch sizes

---

## Support

For issues or questions:
- Check [Spryker documentation](https://docs.spryker.com)
- Check [Spryker ACP Algolia app documentation](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/third-party-integrations/algolia/integrate-algolia#configure-modules-and-their-behavior)
- Review [Algolia documentation](https://www.algolia.com/doc/)
- Contact Spryker support

## Development

To check/fix code style and run static analysis, use:

```bash
composer cs-fix # can be used standalone
composer phpstan # only works together with Spryker project (uses autoloader from it)
```

For test execution, check the details in [tests/README.md](tests/README.md) file.


## License

This module is licensed under the same license as [Spryker Commerce OS](LICENSE).
