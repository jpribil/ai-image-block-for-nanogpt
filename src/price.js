/**
 * Pricing helpers shared by the editor UI.
 */

/**
 * Formats a numeric amount with its currency.
 *
 * @param {number|string} amount   Price amount.
 * @param {string}        currency Currency code.
 * @return {string} Formatted price, or an empty string.
 */
export function formatPrice( amount, currency ) {
	const value = Number( amount );
	if ( ! isFinite( value ) ) {
		return '';
	}
	const rounded = Math.round( value * 10000 ) / 10000;
	return currency === 'USD' ? '$' + rounded : rounded + ' ' + currency;
}

/**
 * Builds the price text for a model and the selected size.
 *
 * @param {Object} model A normalized model object (with a pricing field).
 * @param {string} size  Selected size, or an empty string.
 * @return {string} Price text, or an empty string when unavailable.
 */
export function priceText( model, size ) {
	if ( ! model || ! model.pricing || ! model.pricing.perImage ) {
		return '';
	}

	const perImage = model.pricing.perImage;
	const currency = model.pricing.currency || 'USD';

	if ( size && Object.prototype.hasOwnProperty.call( perImage, size ) ) {
		return formatPrice( perImage[ size ], currency );
	}

	const values = Object.keys( perImage )
		.map( ( key ) => Number( perImage[ key ] ) )
		.filter( ( value ) => isFinite( value ) );

	if ( values.length === 0 ) {
		return '';
	}

	const min = Math.min( ...values );
	const max = Math.max( ...values );

	return min === max
		? formatPrice( min, currency )
		: formatPrice( min, currency ) + '–' + formatPrice( max, currency );
}
