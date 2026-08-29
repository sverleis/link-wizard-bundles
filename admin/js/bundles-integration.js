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

		if ( Object.keys( quantities ).length > 0 ) {
			bundleProduct.child_quantities = getQuantities(
				product,
				quantities
			);
		}

		setSelectedProducts( ( selectedProducts ) => [
			...selectedProducts,
			bundleProduct,
		] );
	};

	const ExistingComplexProductUI = window.LWWCAddons.ComplexProductUI;
	const { createElement, useState } = window.wp.element;

	/**
	 * Render the default contents of a bundle without replacing Composite UI.
	 *
	 * @param {Object} props Link Wizard complex-product component props.
	 * @return {Object|null} Element tree or null when collapsed.
	 */
	function BundleProductConfig( props ) {
		const { product, isProductExpanded, linkType } = props;
		const [ quantities, setQuantities ] = useState( () =>
			getQuantities( product )
		);

		if ( product.type !== 'bundle' ) {
			return ExistingComplexProductUI
				? createElement( ExistingComplexProductUI, props )
				: null;
		}

		const canConfigure = linkType === 'addToCart';
		if ( canConfigure && ! isProductExpanded?.( product.id ) ) {
			return null;
		}

		const items = product.bundled_items || [];

		return createElement(
			'div',
			{ className: 'lwwc-bundle-config' },
			createElement(
				'h4',
				null,
				canConfigure
					? 'Configure bundle quantities'
					: 'Default bundle contents'
			),
			createElement(
				'ul',
				{ className: 'lwwc-bundle-config-items' },
				...items.map( ( item ) => {
					const minimum = item.optional ? 0 : item.quantity.min;
					const maximum =
						item.quantity.max > 0 ? item.quantity.max : undefined;
					const quantity = quantities[ item.bundled_item_id ] || 0;

					return createElement(
						'li',
						{ key: item.bundled_item_id },
						createElement(
							'span',
							null,
							item.optional
								? `${ item.name } (optional)`
								: item.name
						),
						canConfigure
							? createElement( 'input', {
									className:
										'lwwc-bundle-config-quantity-input',
									type: 'number',
									min: minimum,
									max: maximum,
									value: quantity,
									'aria-label': `Quantity for ${ item.name }`,
									onChange: ( event ) => {
										const entered = Number.parseInt(
											event.target.value,
											10
										);
										const bounded = Math.max(
											minimum,
											maximum
												? Math.min(
														maximum,
														entered || 0
												  )
												: entered || 0
										);
										setQuantities( ( current ) => ( {
											...current,
											[ item.bundled_item_id ]: bounded,
										} ) );
									},
							  } )
							: createElement(
									'span',
									{
										className:
											'lwwc-bundle-config-quantity',
									},
									quantity
										? `× ${ quantity }`
										: 'Not selected'
							  )
					);
				} )
			),
			canConfigure
				? createElement(
						'button',
						{
							type: 'button',
							className: 'button button-secondary',
							onClick: () => {
								props.handleAddBundleProduct(
									product,
									quantities
								);
								props.toggleProductExpansion?.( product.id );
							},
						},
						'Add configured bundle'
				  )
				: createElement(
						'p',
						{ className: 'description' },
						'Checkout links use the default bundle configuration.'
				  )
		);
	}

	window.LWWCAddons.ComplexProductUI = BundleProductConfig;
	window.lwwcAddonUrlDisplayHandlers =
		window.lwwcAddonUrlDisplayHandlers || {};
	window.lwwcAddonUrlDisplayHandlers.bundle = function ( product ) {
		const parts = [];

		Object.entries( product.child_quantities || {} ).forEach(
			( [ childId, quantity ] ) => {
				parts.push(
					createElement(
						'span',
						{
							key: `amp-bundle-${ product.unique_id }-${ childId }`,
							className: 'dynamic-link-separator',
						},
						'&'
					),
					createElement(
						'span',
						{
							key: `bundle-quantity-${ product.unique_id }-${ childId }`,
							className: 'dynamic-link-product-param',
						},
						`bundle_quantity_${ childId }=${ quantity }`
					)
				);

				const item = product.bundled_items?.find(
					( bundledItem ) =>
						String( bundledItem.bundled_item_id ) ===
						String( childId )
				);
				if ( item?.optional && Number( quantity ) > 0 ) {
					parts.push(
						createElement(
							'span',
							{
								key: `amp-optional-${ product.unique_id }-${ childId }`,
								className: 'dynamic-link-separator',
							},
							'&'
						),
						createElement(
							'span',
							{
								key: `bundle-optional-${ product.unique_id }-${ childId }`,
								className: 'dynamic-link-product-param',
							},
							`bundle_selected_optional_${ childId }=yes`
						)
					);
				}
			}
		);

		parts.push(
			createElement(
				'span',
				{
					key: `amp-bundle-total-${ product.unique_id }`,
					className: 'dynamic-link-separator',
				},
				'&'
			),
			createElement(
				'span',
				{
					key: `bundle-total-${ product.unique_id }`,
					className: 'dynamic-link-product-param',
				},
				`quantity=${ product.quantity || 1 }`
			)
		);

		return parts;
	};
} )();
