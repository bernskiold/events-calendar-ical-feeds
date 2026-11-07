/**
 * Extends the standard @wordpress/scripts webpack config with the plugin's
 * front-end entries, built to dist/ together with their *.asset.php files.
 *
 * - dist/ical-feed.{js,css}: copy button script and subscribe button styles.
 * - dist/alpinejs.js: Alpine.js, registered as `alpinejs` unless the site
 *   already registers it.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'ical-feed': path.resolve( __dirname, 'assets/scripts/ical-feed.js' ),
		alpinejs: path.resolve( __dirname, 'assets/scripts/alpinejs.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
};
