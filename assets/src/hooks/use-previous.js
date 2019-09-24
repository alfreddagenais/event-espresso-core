/**
 * External imports
 */
import { useRef, useEffect } from '@wordpress/element';

/**
 * A hook to get the previous props or state
 *
 * @param {Object|string|number} value The current value.
 * @return {Object|string|number} - the previous value
 */
export default ( value ) => {
	const ref = useRef();
	useEffect( () => {
		ref.current = value;
	} );
	return ref.current;
};
