#!/usr/bin/env bash
#
# Installs a WordPress core and its PHPUnit test library for the integration
# tests, without Docker. Used by CI; handy locally when wp-env is unavailable.
#
#   bin/install-wp-tests.sh [wp-version] [db-name] [db-user] [db-pass] [db-host]
#
# wp-version is "latest" (default), a release such as "7.0" or "7.0.2", or a
# pre-release such as "7.2-RC3".
# Also installs the latest The Events Calendar into the core's plugins folder,
# where tests/php/bootstrap.php loads it from (override with TEC_VERSION).
# Prints the environment variables to export for `composer test`.
set -euo pipefail

WP_VERSION=${1:-latest}
DB_NAME=${2:-wordpress_tests}
DB_USER=${3:-root}
DB_PASS=${4:-}
DB_HOST=${5:-127.0.0.1}
TARGET=${WP_TESTS_INSTALL_DIR:-${TMPDIR:-/tmp}/events-calendar-ical-feeds-wp-tests}

if [ "$WP_VERSION" = "latest" ]; then
	WP_VERSION=$(curl -fsSL https://api.wordpress.org/core/version-check/1.7/ | php -r 'echo json_decode(stream_get_contents(STDIN))->offers[0]->version;')
fi

# The test library is published per release; use the newest patch of the
# same minor so a fresh core point release never blocks the run.
WP_MINOR=$(echo "${WP_VERSION%%-*}" | cut -d. -f1,2)
DIR="$TARGET/$WP_VERSION"

mkdir -p "$DIR"

if [ ! -d "$DIR/wordpress" ]; then
	curl -fsSL -o "$DIR/wordpress.zip" "https://wordpress.org/wordpress-$WP_VERSION.zip"
	unzip -q "$DIR/wordpress.zip" -d "$DIR"
	rm "$DIR/wordpress.zip"
fi

TEC_VERSION=${TEC_VERSION:-latest}
TEC_ZIP=the-events-calendar.zip
if [ "$TEC_VERSION" != "latest" ]; then
	TEC_ZIP="the-events-calendar.$TEC_VERSION.zip"
fi

if [ ! -f "$DIR/wordpress/wp-content/plugins/the-events-calendar/the-events-calendar.php" ]; then
	curl -fsSL -o "$DIR/tec.zip" "https://downloads.wordpress.org/plugin/$TEC_ZIP"
	unzip -q "$DIR/tec.zip" -d "$DIR/wordpress/wp-content/plugins"
	rm "$DIR/tec.zip"
fi

case "$WP_VERSION" in
	*-*)
		# A pre-release such as 7.2-RC3 has no wp-phpunit package yet, so take
		# the test library from its branch in wordpress-develop.
		# That library reads wp-tests-config.php from the checkout's root.
		TESTS_DIR="$DIR/wordpress-develop/tests/phpunit"
		CONFIG_FILE="$DIR/wordpress-develop/wp-tests-config.php"
		if [ ! -d "$TESTS_DIR/includes" ]; then
			git clone --quiet --depth 1 --branch "$WP_MINOR" --filter=blob:none --sparse \
				https://github.com/WordPress/wordpress-develop.git "$DIR/wordpress-develop"
			git -C "$DIR/wordpress-develop" sparse-checkout set tests/phpunit
		fi
		;;
	*)
		TESTS_DIR="$DIR/vendor/wp-phpunit/wp-phpunit"
		CONFIG_FILE="$DIR/wp-tests-config.php"
		if [ ! -d "$TESTS_DIR" ]; then
			composer --working-dir="$DIR" require --quiet --no-interaction "wp-phpunit/wp-phpunit:~$WP_MINOR.0"
		fi
		;;
esac

cat > "$CONFIG_FILE" <<CONFIG
<?php
define( 'ABSPATH', '$DIR/wordpress/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASS' );
define( 'DB_HOST', '$DB_HOST' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
\$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
CONFIG

echo "WP_PHPUNIT__DIR=$TESTS_DIR"
echo "WP_PHPUNIT__TESTS_CONFIG=$CONFIG_FILE"
