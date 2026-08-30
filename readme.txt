=== Link Wizard for Bundles ===
Contributors: sverleis
Tags: woocommerce, product bundles, checkout links, add to cart
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0-beta1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds WooCommerce Product Bundles support to Link Wizard for WooCommerce.

== Description ==

Link Wizard for Bundles connects Link Wizard for WooCommerce with WooCommerce Product Bundles.

The 1.0 beta adds bundle product discovery, default add-to-cart and checkout links, and per-item quantity configuration for add-to-cart links. Checkout links intentionally use the bundle's default configuration.

== Requirements ==

* Link Wizard for WooCommerce 2.0.0-beta1 or newer (add-on API 2.0)
* WooCommerce
* WooCommerce Product Bundles

== Installation ==

1. Install and activate Link Wizard for WooCommerce 2.0.0-beta1 or newer.
2. Install and activate WooCommerce and WooCommerce Product Bundles.
3. Download the versioned ZIP from https://github.com/sverleis/link-wizard-bundles/releases.
4. Upload and activate the ZIP through Plugins > Add New > Upload Plugin.
5. Open Products > Link Wizard and confirm that the Bundles add-on card is active.

== Frequently Asked Questions ==

= Does this release generate bundle checkout links? =

Yes. Checkout links preserve the Product Bundles parent item and use its default configuration. Per-item custom quantities are available for add-to-cart links only.

== Changelog ==

= 1.0.0-beta1 =
* Add bundle products to Link Wizard product search.
* Expose bundled items, quantities, validation data, and default URLs.
* Add authenticated bundle product and URL-generation REST endpoints.
* Add per-item quantity configuration for add-to-cart links.
* Respect Product Bundles optional-item defaults.
* Preserve Composite Products configuration when both integrations are active.
* Avoid cross-runtime React hooks in the bundle configuration interface.

= 0.1.0 =
* Register Link Wizard for Bundles with the Link Wizard add-on manager.
* Declare Bundles product type and link capabilities.
