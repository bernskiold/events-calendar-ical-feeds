/**
 * Vitest configuration for the JavaScript unit tests in tests/unit.
 *
 * jsdom supplies the DOM the subscribe button script works on.
 */
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'jsdom',
		globals: false,
		restoreMocks: true,
		include: [ 'tests/unit/**/*.test.js' ],
	},
} );
