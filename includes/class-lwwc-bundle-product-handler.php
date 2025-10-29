<?php
/**
 * Bundle Product Handler.
 *
 * Handles WooCommerce Product Bundles for Link Wizard.
 *
 * @package Link_Wizard_Bundles
 * @subpackage Link_Wizard_Bundles/includes
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bundle Product Handler class.
 *
 * Handles bundle product functionality for Link Wizard.
 *
 * @since 0.1.0
 */
class LWWC_Bundle_Product_Handler implements LWWC_Product_Handler_Interface {

	/**
	 * Debug logging helper (only logs if WP_DEBUG is enabled).
	 *
	 * @param string $message The message to log.
	 */
	private function debug_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logs when WP_DEBUG is enabled.
			error_log( 'Link Wizard for Bundles: ' . $message );
		}
	}

	/**
	 * Get the product type this handler supports.
	 *
	 * @since 0.1.0
	 * @return string The product type.
	 */
	public function get_product_type() {
		return 'bundle';
	}

	/**
	 * Check if this handler can handle the given product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The product to check.
	 * @return bool True if this handler can handle the product.
	 */
	public function can_handle( $product ) {
		return $product && $product->is_type( 'bundle' );
	}

	/**
	 * Get search results for this product type.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The product to get data for.
	 * @return array Array of product data.
	 */
	public function get_search_results( $product ) {
		if ( ! $this->can_handle( $product ) ) {
			return array();
		}

		return array( $this->get_product_data( $product ) );
	}

	/**
	 * Get product data for a bundle product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The bundle product.
	 * @return array Array of product data.
	 */
	public function get_product_data( $product ) {
		if ( ! $this->can_handle( $product ) ) {
			return array();
		}

		// Get bundled items.
		$bundled_items = array();
		$bundled_products = $product->get_bundled_items();

		if ( ! empty( $bundled_products ) ) {
			foreach ( $bundled_products as $bundled_item_id => $bundled_item ) {
				$bundled_product = $bundled_item->get_product();
				if ( ! $bundled_product ) {
					continue;
				}

				$bundled_items[] = array(
					'bundled_item_id' => $bundled_item_id,
					'id'              => $bundled_product->get_id(),
					'name'            => $bundled_product->get_name(),
					'sku'             => $bundled_product->get_sku(),
					'price'           => $bundled_product->get_price_html(),
					'quantity'        => array(
						'min'     => $bundled_item->get_quantity( 'min' ),
						'max'     => $bundled_item->get_quantity( 'max' ),
						'default' => $bundled_item->get_quantity( 'default' ),
					),
					'optional'        => $bundled_item->is_optional(),
					'type'            => $bundled_product->get_type(),
				);
			}
		}

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'sku'               => $product->get_sku(),
			'price'             => $product->get_price_html(),
			'image'             => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
			'parent_id'         => null,
			'parent_name'       => null,
			'attributes'        => null,
			'type'              => 'bundle',
			'slug'              => $product->get_slug(),
			'sold_individually' => $product->is_sold_individually(),
			'bundled_items'     => $bundled_items,
		);
	}

	/**
	 * Check if the product is valid for link generation.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The product to check.
	 * @return bool True if the product is valid for links.
	 */
	public function is_valid_for_links( $product ) {
		if ( ! $this->can_handle( $product ) ) {
			return false;
		}

		// Bundles are valid if they have bundled items.
		$bundled_items = $product->get_bundled_items();
		return ! empty( $bundled_items );
	}

	/**
	 * Get validation errors for the product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The product to validate.
	 * @return array Array of validation errors.
	 */
	public function get_validation_errors( $product ) {
		$errors = array();

		if ( ! $this->can_handle( $product ) ) {
			return $errors;
		}

		$bundled_items = $product->get_bundled_items();
		if ( empty( $bundled_items ) ) {
			$errors[] = array(
				'type'    => 'bundle',
				'message' => __( 'This bundle has no bundled items configured.', 'link-wizard-bundles' ),
			);
		}

		return $errors;
	}

	/**
	 * Get validation data for the product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product The product to validate.
	 * @return array Validation data including whether the product is valid and any errors.
	 */
	public function get_validation_data( $product ) {
		$errors = $this->get_validation_errors( $product );

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
		);
	}

	/**
	 * Generate URL for this product type.
	 *
	 * Bundle products use the default URL logic, so we return null.
	 * Custom bundle URLs are handled via the REST API endpoint.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product      The product.
	 * @param string     $link_type    'addToCart' or 'checkoutLink'.
	 * @param array      $options      Additional options (redirect, quantity, etc.).
	 * @return string|null The generated URL, or null to use default logic.
	 */
	public function generate_url( $product, $link_type, $options = array() ) {
		// Bundle products use the default URL logic or REST API.
		return null;
	}
}

