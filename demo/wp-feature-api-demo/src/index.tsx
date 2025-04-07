/**
 * WordPress dependencies
 */
import { render, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ChatApp } from './chat-app';
import './style.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'wp-feature-api-demo-root' );
	if ( container ) {
		render( createElement( ChatApp ), container );
	}
} );
