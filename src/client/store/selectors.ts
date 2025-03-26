/**
 * Selectors
 */

/**
 * WordPress dependencies
 */
import { createSelector, createRegistrySelector } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from './index';

// Select all features
export const getRegisteredFeatures = createSelector(
	( state ) => {
		return Object.values( state.featuresById );
	},
	( state ) => [ state.featuresById ]
);

// Select a feature by ID
export const getRegisteredFeature = ( state, id ) =>
	state.featuresById[ id ] || null;

// Return the feature callback
export const getRegisteredFeatureCallback = createRegistrySelector(
	( select ) => ( state, id ) => {
		const feature = select( store ).getRegisteredFeature( id );
		return feature?.callback;
	}
);
