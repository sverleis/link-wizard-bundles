<?php
/**
 * Bundle product handler.
 *
 * @package Link_Wizard_Bundles
 */

namespace LWWC\Bundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Makes WooCommerce Product Bundles discoverable and usable by Link Wizard.
 */
class Bundle_Product_Handler implements \LWWC_Product_Handler_Interface {

	/**
	 * Get the supported product type.
	 *
	 * @return string
	 */
	public function get_product_type() {
		return 'bundle';
	}

	/**
	 * Check whether this handler supports a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function can_handle( $product ) {
		return $product && $product->is_type( 'bundle' );
	}

	/**
	 * Get search results.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	public function get_search_results( $product ) {
		if ( ! $this->can_handle( $product ) ) {
			return array();
		}

		return array( $this->get_product_data( $product ) );
	}

	/**
	 * Get bundle data for the Link Wizard interface.
	 *
	 * @param \WC_Product $product Bundle product.
	 * @return array
	 */
	public function get_product_data( $product ) {
		if ( ! $this->can_handle( $product ) ) {
			return array();
		}

		$items = $this->get_bundled_items( $product );

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
			'status'            => $product->get_status(),
			'sold_individually' => $product->is_sold_individually(),
			'bundled_items'     => $items,
			'children'          => $items,
			'url'               => $this->generate_url( $product, 'addToCart' ),
			'checkout_url'      => $this->generate_url( $product, 'checkoutLink' ),
		);
	}

	/**
	 * Get bundled item data.
	 *
	 * @param \WC_Product $product Bundle product.
	 * @return array
	 */
	private function get_bundled_items( $product ) {
		$items = array();

		foreach ( $product->get_bundled_items() as $bundled_item_id => $bundled_item ) {
			$bundled_product = $bundled_item->get_product();
			if ( ! $bundled_product ) {
				continue;
			}

			$items[] = array(
				'id'              => (int) $bundled_item_id,
				'bundled_item_id' => (int) $bundled_item_id,
				'product_id'      => $bundled_product->get_id(),
				'name'            => $bundled_item->get_title(),
				'sku'             => $bundled_product->get_sku(),
				'price'           => $bundled_product->get_price_html(),
				'type'            => $bundled_product->get_type(),
				'optional'        => $bundled_item->is_optional(),
				'quantity'        => array(
					'min'     => (int) $bundled_item->get_quantity( 'min' ),
					'max'     => (int) $bundled_item->get_quantity( 'max' ),
					'default' => $bundled_item->is_optional() ? 0 : (int) $bundled_item->get_quantity( 'default' ),
				),
			);
		}

		return $items;
	}

	/**
	 * Check whether a bundle can be used in links.
	 *
	 * @param \WC_Product $product Bundle product.
	 * @return bool
	 */
	public function is_valid_for_links( $product ) {
		return $this->can_handle( $product ) && empty( $this->get_validation_errors( $product ) );
	}

	/**
	 * Get validation errors.
	 *
	 * @param \WC_Product $product Bundle product.
	 * @return array
	 */
	public function get_validation_errors( $product ) {
		$errors = array();
		if ( ! $this->can_handle( $product ) ) {
			return $errors;
		}

		if ( 'publish' !== $product->get_status() ) {
			$errors[] = __( 'Bundle product is not published.', 'link-wizard-bundles' );
		}
		if ( empty( $product->get_bundled_items() ) ) {
			$errors[] = __( 'Bundle product has no bundled items configured.', 'link-wizard-bundles' );
		}
		if ( ! $product->is_purchasable() ) {
			$errors[] = __( 'Bundle product is not purchasable.', 'link-wizard-bundles' );
		}
		if ( ! $product->is_in_stock() ) {
			$errors[] = __( 'Bundle product is out of stock.', 'link-wizard-bundles' );
		}

		return $errors;
	}

	/**
	 * Get frontend validation data.
	 *
	 * @param \WC_Product $product Bundle product.
	 * @return array
	 */
	public function get_validation_data( $product ) {
		$errors   = $this->get_validation_errors( $product );
		$warnings = array();

		if ( $this->can_handle( $product ) ) {
			foreach ( $product->get_bundled_items() as $bundled_item ) {
				$bundled_product = $bundled_item->get_product();
				if ( ! $bundled_product || ! $bundled_product->is_purchasable() ) {
					$warnings[] = sprintf(
						/* translators: %s: Bundled item title. */
						__( 'Bundled item "%s" is unavailable.', 'link-wizard-bundles' ),
						$bundled_item->get_title()
					);
				}
			}
		}

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Generate a default-configuration bundle URL.
	 *
	 * @param \WC_Product $product   Bundle product.
	 * @param string      $link_type Link type.
	 * @param array       $options   URL options.
	 * @return string|null
	 */
	public function generate_url( $product, $link_type, $options = array() ) {
		if ( ! $this->can_handle( $product ) ) {
			return null;
		}

		$quantity = isset( $options['quantity'] ) ? max( 1, absint( $options['quantity'] ) ) : 1;
		if ( 'checkoutLink' === $link_type ) {
			return add_query_arg(
				'products',
				$product->get_id() . ':' . $quantity,
				home_url( '/checkout-link/' )
			);
		}

		$redirect_path = isset( $options['redirect_path'] ) ? $options['redirect_path'] : '/';
		return add_query_arg(
			array(
				'add-to-cart' => $product->get_id(),
				'quantity'    => $quantity,
			),
			home_url( $redirect_path )
		);
	}
}
