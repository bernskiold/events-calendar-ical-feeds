/**
 * Extends the standard @wordpress/scripts webpack config with the plugin's
 * front-end entry, built to dist/ together with its *.asset.php file:
 * dist/ical-feed.{js,css}, the subscribe button script and styles.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'ical-feed': path.resolve( __dirname, 'assets/scripts/ical-feed.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
};
