<?php
/**
 * Plugin Name: Link Wizard for Bundles
 * Plugin URI: https://github.com/sverleis/link-wizard-bundles
 * Description: Link Wizard addon providing WooCommerce Product Bundles support (Add-to-Cart full support; Checkout-Link defaults only).
 * Version: 0.1.0
 * Author: Link Wizard
 * Author URI: https://github.com/sverleis
 * Text Domain: link-wizard-bundles
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, link-wizard-for-woocommerce
 * License: GPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants.
define( 'LWWC_BUNDLES_VERSION', '0.1.0' );
define( 'LWWC_BUNDLES_PLUGIN_FILE', __FILE__ );
define( 'LWWC_BUNDLES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Bootstrap the addon only in admin where Link Wizard runs.
 */
add_action( 'plugins_loaded', function () {
    // Load text domain.
    load_plugin_textdomain( 'link-wizard-bundles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Basic guard.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Include core class.
    require_once LWWC_BUNDLES_PLUGIN_DIR . 'includes/class-lwwc-bundles-handler.php';

    // Initialize.
    \LWWC\Bundles\Handler::init();
} );


