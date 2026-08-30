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
		add_action( 'lwwc_after_product_handlers_loaded', array( __CLASS__, 'register_product_handler' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the bundle handler with Link Wizard.
	 *
	 * @param \LWWC_Product_Handler_Manager $handler_manager Product handler manager.
	 */
	public static function register_product_handler( $handler_manager ): void {
		require_once LWWC_BUNDLES_PLUGIN_DIR . 'includes/class-lwwc-bundle-product-handler.php';
		$handler_manager->register_handler( new Bundle_Product_Handler() );
	}

	/**
	 * Register bundle product data and URL-generation routes.
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'lwwc-bundles/v1',
			'/product/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_product' ),
				'permission_callback' => array( __CLASS__, 'check_rest_permission' ),
				'args'                => array(
					'id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
		register_rest_route(
			'lwwc-bundles/v1',
			'/generate-url',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_generate_url' ),
				'permission_callback' => array( __CLASS__, 'check_rest_permission' ),
				'args'                => array(
					'product_id' => array( 'required' => true, 'type' => 'integer' ),
					'link_type'  => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'addToCart', 'checkoutLink' ),
					),
					'quantity'   => array( 'default' => 1, 'type' => 'integer', 'minimum' => 1 ),
				),
			)
		);
	}

	/**
	 * Check REST permissions.
	 *
	 * @return bool
	 */
	public static function check_rest_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get bundle product data through REST.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_get_product( $request ) {
		$product = wc_get_product( $request->get_param( 'id' ) );
		$handler = self::get_product_handler();
		if ( ! $handler->can_handle( $product ) ) {
			return new \WP_Error( 'invalid_bundle', __( 'Product is not a bundle.', 'link-wizard-bundles' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( $handler->get_product_data( $product ) );
	}

	/**
	 * Generate a default bundle URL through REST.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_generate_url( $request ) {
		$product = wc_get_product( $request->get_param( 'product_id' ) );
		$handler = self::get_product_handler();
		if ( ! $handler->can_handle( $product ) ) {
			return new \WP_Error( 'invalid_bundle', __( 'Product is not a bundle.', 'link-wizard-bundles' ), array( 'status' => 400 ) );
		}

		$url = $handler->generate_url(
			$product,
			$request->get_param( 'link_type' ),
			array( 'quantity' => $request->get_param( 'quantity' ) )
		);

		return rest_ensure_response(
			array(
				'url'        => $url,
				'product_id' => $product->get_id(),
				'quantity'   => (int) $request->get_param( 'quantity' ),
			)
		);
	}

	/**
	 * Get the bundle product handler.
	 *
	 * @return Bundle_Product_Handler
	 */
	private static function get_product_handler(): Bundle_Product_Handler {
		require_once LWWC_BUNDLES_PLUGIN_DIR . 'includes/class-lwwc-bundle-product-handler.php';
		return new Bundle_Product_Handler();
	}

	/**
	 * Enqueue the lightweight bundle interaction layer on Link Wizard only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ): void {
		if ( 'product_page_link-wizard-for-woocommerce' !== $hook_suffix ) {
			return;
		}

		$dependencies = array( 'link-wizard-for-woocommerce', 'wp-element' );
		if ( class_exists( '\\LWWC_Composite_Handler' ) ) {
			$dependencies[] = 'lwwc-composite-admin';
		}

		wp_enqueue_script(
			'lwwc-bundles-integration',
			LWWC_BUNDLES_PLUGIN_URL . 'admin/js/bundles-integration.js',
			$dependencies,
			LWWC_BUNDLES_VERSION,
			true
		);
		wp_enqueue_style(
			'lwwc-bundles-integration',
			LWWC_BUNDLES_PLUGIN_URL . 'admin/css/bundles-integration.css',
			array(),
			LWWC_BUNDLES_VERSION
		);
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
