/**
 * Link Wizard Product Bundles integration.
 *
 * Supplies the small state helpers expected by Link Wizard's ProductSelect.
 */

( function () {
	'use strict';

	window.LWWCAddons = window.LWWCAddons || {};
	window.LWWCAddons.complexProducts = window.LWWCAddons.complexProducts || {};

	const integration = window.LWWCAddons.complexProducts;

	/**
	 * Get a bundle's selected/default bundled-item quantities.
	 *
	 * @param {Object} product  Bundle product data.
	 * @param {Object} supplied User-supplied quantities.
	 * @return {Object} Quantities keyed by bundled item ID.
	 */
	function getQuantities( product, supplied = {} ) {
		if ( Object.keys( supplied ).length > 0 ) {
			return { ...supplied };
		}

		if ( product.child_quantities ) {
			return { ...product.child_quantities };
		}

		return ( product.bundled_items || [] ).reduce( ( quantities, item ) => {
			quantities[ item.bundled_item_id ] = item.quantity?.default || 0;
			return quantities;
		}, {} );
	}

	integration.hasSelectedBundleChildren = function ( product ) {
		return Object.values( getQuantities( product ) ).some(
			( quantity ) => Number( quantity ) > 0
		);
	};

	integration.addBundleProduct = function (
		product,
		quantities = {},
		setSelectedProducts
	) {
		if ( typeof quantities === 'function' ) {
			setSelectedProducts = quantities;
			quantities = {};
		}

		if ( typeof setSelectedProducts !== 'function' ) {
			return;
		}

		const bundleProduct = {
			...product,
			unique_id: `bundle_${ product.id }_${ Date.now() }`,
			quantity: product.quantity || 1,
		};

		setSelectedProducts( ( selectedProducts ) => [
			...selectedProducts,
			bundleProduct,
		] );
	};

	const ExistingComplexProductUI = window.LWWCAddons.ComplexProductUI;
	const { createElement } = window.wp.element;

	/**
	 * Render the default contents of a bundle without replacing Composite UI.
	 *
	 * @param {Object} props Link Wizard complex-product component props.
	 * @return {Object|null} Element tree or null when collapsed.
	 */
	function BundleProductConfig( props ) {
		const { product, isProductExpanded } = props;

		if ( product.type !== 'bundle' ) {
			return ExistingComplexProductUI
				? createElement( ExistingComplexProductUI, props )
				: null;
		}

		if ( ! isProductExpanded?.( product.id ) ) {
			return null;
		}

		const quantities = getQuantities( product );
		const items = product.bundled_items || [];

		return createElement(
			'div',
			{ className: 'lwwc-bundle-config' },
			createElement( 'h4', null, 'Default bundle contents' ),
			createElement(
				'ul',
				{ className: 'lwwc-bundle-config-items' },
				...items.map( ( item ) =>
					createElement(
						'li',
						{ key: item.bundled_item_id },
						createElement(
							'span',
							null,
							item.optional
								? `${ item.name } (optional)`
								: item.name
						),
						createElement(
							'span',
							{ className: 'lwwc-bundle-config-quantity' },
							quantities[ item.bundled_item_id ]
								? `× ${ quantities[ item.bundled_item_id ] }`
								: 'Not selected'
						)
					)
				)
			),
			createElement(
				'button',
				{
					type: 'button',
					className: 'button button-secondary',
					onClick: () => {
						props.handleAddBundleProduct( product, quantities );
						props.toggleProductExpansion?.( product.id );
					},
				},
				'Add bundle'
			)
		);
	}

	window.LWWCAddons.ComplexProductUI = BundleProductConfig;
} )();
