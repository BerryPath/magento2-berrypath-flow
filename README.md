# BerryPath Flow for Magento 2

Magento 2 module for the [BerryPath](https://www.berrypath.eu) Flow widget.

BerryPath helps ecommerce teams add guided selling, product finder flows and guided product advice to their webshop. In Magento 2, this module makes those Flow widgets available on category pages, product detail pages and CMS/widget placements.

The category banner title and description are rendered as regular Magento HTML, so merchants can add relevant, indexable context around product advice flows. The module is built for Luma/Blank based storefronts and uses Magento LESS for styling.

## Installation

```bash
composer require berrypath/magento2-berrypath-flow
bin/magento module:enable BerryPath_Flow
bin/magento setup:upgrade
bin/magento cache:flush
```

For local `app/code` development, place it at:

```text
app/code/BerryPath/Flow
```

## Configuration

Global settings are in:

```text
Stores > Configuration > BerryPath > Flow
```

Use this for enable/disable, market code and product ID source.

Enable the widget per page by entering a Flow UUID:

- Category: `Catalog > Categories > BerryPath Flow`
- Product: `Catalog > Products > BerryPath Flow`
- CMS/widget usage: Magento widget `BerryPath Flow`

If no Flow UUID is set on the category, product or widget, nothing is rendered.

## Styling

Luma/Blank styling lives in:

```text
view/frontend/web/css/source/_module.less
```

Override the LESS variables in your theme to adjust spacing, colors, borders, radius and button styles.

## Hyva

For Hyva storefronts, install the separate compatibility module:

- Package: [`berrypath/magento2-berrypath-flow-hyva-compat`](https://github.com/BerryPath/magento2-berrypath-flow-hyva-compat)

The Hyva module replaces the frontend templates with Tailwind-based templates through `hyva-themes/magento2-compat-module-fallback`.
