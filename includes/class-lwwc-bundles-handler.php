<?php

namespace LWWC\Bundles;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main handler class for bundle products.
 */
class Handler {
    public static function init(): void {
        // Register REST endpoints.
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );

        // Register product handler with Link Wizard.
        add_action( 'lwwc_after_product_handlers_loaded', [ __CLASS__, 'register_product_handler' ] );

        // Register capabilities with Link Wizard.
        add_filter( 'lwwc_addon_capabilities', [ __CLASS__, 'register_capabilities' ], 10, 2 );
    }

    /**
     * Register our product handler with Link Wizard's handler manager.
     *
     * @param LWWC_Product_Handler_Manager $handler_manager The handler manager instance.
     */
    public static function register_product_handler( $handler_manager ) {
        // Load our bundle product handler.
        require_once dirname( __FILE__ ) . '/class-lwwc-bundle-product-handler.php';

        // Create and register the handler.
        // Use fully qualified class name (leading backslash) to escape namespace.
        $bundle_handler = new \LWWC_Bundle_Product_Handler();
        $handler_manager->register_handler( $bundle_handler );
    }

    /**
     * Register plugin capabilities with Link Wizard.
     *
     * @param array  $capabilities Existing capabilities from other addons.
     * @param string $plugin_slug  The plugin slug being checked.
     * @return array Updated capabilities.
     */
    public static function register_capabilities( $capabilities, $plugin_slug ) {
        // Only respond if Link Wizard is asking about our plugin.
        if ( 'link-wizard-bundles/link-wizard-bundles.php' !== $plugin_slug ) {
            return $capabilities;
        }

        // Tell Link Wizard what we can do.
        return array(
            'product_types' => array( 'bundle' ), // We handle bundle products.
            'features'      => array(
                'add_to_cart',      // We enable full add-to-cart URLs for bundles.
                'checkout_links',   // We enable checkout-links with default configuration.
            ),
        );
    }

    /**
     * Register REST endpoints.
     */
    public static function register_rest_routes(): void {
        register_rest_route(
            'lwwc-bundles/v1',
            '/generate-url',
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'rest_generate_url' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_woocommerce' );
                },
                'args'                => [
                    'product_id' => [ 'required' => true, 'type' => 'integer' ],
                    'link_type'  => [ 'required' => true, 'type' => 'string', 'enum' => [ 'addToCart', 'checkoutLink' ] ],
                ],
            ]
        );
    }

    /**
     * Build URLs for bundles.
     */
    public static function rest_generate_url( \WP_REST_Request $request ): \WP_REST_Response {
        $product_id = absint( $request['product_id'] );
        $link_type  = sanitize_text_field( $request['link_type'] );

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_type( 'bundle' ) ) {
            return new \WP_REST_Response( [ 'error' => 'invalid_product', 'message' => 'Product is not a bundle.' ], 400 );
        }

        $defaults = self::get_default_bundled_items( $product );

        if ( 'addToCart' === $link_type ) {
            $url = self::build_add_to_cart_url( $product_id, $defaults );
        } else {
            $url = self::build_checkout_link_default_url( $defaults );
        }

        return new \WP_REST_Response( [ 'url' => $url, 'defaults' => $defaults ], 200 );
    }

    /**
     * Extract default bundled items (id, quantity, optional flag, type info).
     */
    private static function get_default_bundled_items( \WC_Product $bundle ): array {
        $items = [];
        if ( ! $bundle->is_type( 'bundle' ) ) {
            return $items;
        }
        foreach ( $bundle->get_bundled_items() as $bundled_item_id => $bundled_item ) {
            $bundled_product = $bundled_item->get_product();
            if ( ! $bundled_product ) {
                continue;
            }
            $qty      = (int) $bundled_item->get_quantity( 'default' );
            $optional = $bundled_item->is_optional();

            $items[] = [
                'bundled_item_id' => (int) $bundled_item_id,
                'product_id'      => (int) $bundled_product->get_id(),
                'type'            => $bundled_product->get_type(),
                'quantity'        => $qty,
                'optional'        => $optional,
            ];
        }
        return $items;
    }

    /**
     * Build add-to-cart URL with bundle parameters using defaults.
     */
    private static function build_add_to_cart_url( int $bundle_id, array $defaults ): string {
        $base   = home_url( '/?add-to-cart=' . $bundle_id );
        $params = [];
        foreach ( $defaults as $item ) {
            $qty = max( 0, (int) ( $item['quantity'] ?? 0 ) );
            if ( $qty > 0 ) {
                $params[] = 'bundle_quantity_' . $item['bundled_item_id'] . '=' . $qty;
                if ( ! empty( $item['optional'] ) ) {
                    $params[] = 'bundle_selected_optional_' . $item['bundled_item_id'] . '=yes';
                }
            }
        }
        if ( $params ) {
            $base .= '&' . implode( '&', $params );
        }
        return $base;
    }

    /**
     * Build checkout-link URL by expanding bundled items into individual products with default quantities.
     * Note: This ignores bundle-level pricing and treats items as standalone for checkout-link purposes.
     */
    private static function build_checkout_link_default_url( array $defaults ): string {
        $pairs = [];
        foreach ( $defaults as $item ) {
            $qty = max( 0, (int) ( $item['quantity'] ?? 0 ) );
            if ( $qty > 0 ) {
                $pairs[] = $item['product_id'] . ':' . $qty;
            }
        }
        $products = implode( ',', $pairs );
        return home_url( '/checkout-link/?products=' . rawurlencode( str_replace( [':', ','], [':', ','], $products ) ) );
    }
}


