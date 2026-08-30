<?php
/**
 * Link Wizard Bundles integration coordinator.
 *
 * @package Link_Wizard_Bundles
 */

namespace LWWC\Bundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Bundles metadata with Link Wizard.
 */
class Handler {

	/**
	 * Register Link Wizard integration hooks.
	 */
	public static function init(): void {
		add_filter( 'lwwc_addon_capabilities', array( __CLASS__, 'register_capabilities' ), 10, 2 );
		add_filter( 'lwwc_addon_icon', array( __CLASS__, 'register_icon' ), 10, 2 );
	}

	/**
	 * Register the add-on capabilities shown in the add-on card.
	 *
	 * @param array  $capabilities Existing capabilities.
	 * @param string $plugin_slug Add-on directory slug.
	 * @return array Filtered capabilities.
	 */
	public static function register_capabilities( $capabilities, $plugin_slug ) {
		if ( 'link-wizard-bundles' !== $plugin_slug ) {
			return $capabilities;
		}

		return array(
			'product_types' => array( 'bundle' ),
			'features'      => array( 'add_to_cart', 'checkout_links' ),
			'admin_pages'   => array(),
		);
	}

	/**
	 * Register the icon shown in the add-on card.
	 *
	 * @param string $icon Current icon.
	 * @param string $plugin_slug Add-on directory slug.
	 * @return string Filtered icon.
	 */
	public static function register_icon( $icon, $plugin_slug ) {
		if ( 'link-wizard-bundles' !== $plugin_slug ) {
			return $icon;
		}

		return '📦';
	}
}
