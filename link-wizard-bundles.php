<?php
/**
 * Plugin Name: Link Wizard for Bundles
 * Plugin URI: https://github.com/sverleis/link-wizard-bundles
 * Description: Link Wizard addon providing WooCommerce Product Bundles support (Add-to-Cart full support; Checkout-Link defaults only).
 * Version: 1.0.0-beta1
 * Author: Link Wizard
 * Author URI: https://github.com/sverleis
 * Text Domain: link-wizard-bundles
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: link-wizard-for-woocommerce, woocommerce
 * Link Wizard Add-on API: 2.0
 * Requires Link Wizard: 2.0.0-beta1
 * Tested Link Wizard: 2.0
 * Requires WooCommerce Extension: woocommerce-product-bundles
 * License: GPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants.
define( 'LWWC_BUNDLES_VERSION', '1.0.0-beta1' );
define( 'LWWC_BUNDLES_PLUGIN_FILE', __FILE__ );
define( 'LWWC_BUNDLES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LWWC_BUNDLES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstrap the addon only in admin where Link Wizard runs.
 */
add_action( 'plugins_loaded', function () {
    // Load only when the required integration APIs are available.
    if (
        ! class_exists( 'LWWC_Link_Wizard' ) ||
        ! defined( 'LWWC_ADDON_API_VERSION' ) ||
        '2.0' !== LWWC_ADDON_API_VERSION ||
        version_compare( LWWC_VERSION, '2.0.0-beta1', '<' ) ||
        ! class_exists( 'WooCommerce' ) ||
        ! class_exists( 'WC_Bundles' )
    ) {
        return;
    }

    // Include core class.
    require_once LWWC_BUNDLES_PLUGIN_DIR . 'includes/class-lwwc-bundles-handler.php';

    // Initialize.
    \LWWC\Bundles\Handler::init();
} );


