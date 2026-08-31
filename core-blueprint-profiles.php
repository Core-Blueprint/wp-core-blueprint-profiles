<?php
/**
 * Plugin Name:       Core Blueprint Profiles
 * Plugin URI:        https://coreblueprint.io
 * Description:       Lightweight privacy-aware WordPress profile pages with configurable URLs, visibility and builder-friendly author archives.
 * Version:           1.0.0
 * Author:            Core Blueprint
 * Author URI:        https://coreblueprint.io
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       core-blueprint-profiles
 * Domain Path:       /languages
 * Requires at least: 7.0
 * Requires PHP:      8.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'CB_PROFILES_VERSION', '1.0.0' );
define( 'CB_PROFILES_FILE', __FILE__ );
define( 'CB_PROFILES_DIR', plugin_dir_path( __FILE__ ) );
define( 'CB_PROFILES_URL', plugin_dir_url( __FILE__ ) );
define( 'CB_PROFILES_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'CB\\Profiles\\';
	if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
		return;
	}
	$file = CB_PROFILES_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

register_activation_hook( __FILE__, [ '\\CB\\Profiles\\Install', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\\CB\\Profiles\\Install', 'deactivate' ] );
add_action( 'plugins_loaded', [ '\\CB\\Profiles\\Plugin', 'boot' ], 20 );

/** Return the current profile user on an author archive, or false. */
function cb_profiles_get_profile_user(): \WP_User|false {
	return \CB\Profiles\ProfilePolicy::current_profile_user();
}

/** Return the current profile user ID, or 0 outside a profile. */
function cb_profiles_get_profile_user_id(): int {
	$user = cb_profiles_get_profile_user();
	return $user instanceof \WP_User ? (int) $user->ID : 0;
}

/** Return a profile URL for a WordPress user, or an empty string when unavailable. */
function cb_profiles_get_profile_url( int $user_id ): string {
	return \CB\Profiles\Routing::profile_url( $user_id );
}

/** Determine whether a visitor may view a user's public profile. */
function cb_profiles_can_view_profile( int $profile_user_id, ?int $viewer_user_id = null ): bool {
	return \CB\Profiles\ProfilePolicy::can_view( $profile_user_id, $viewer_user_id );
}

/** Determine whether the current request is a Core Blueprint profile request. */
function cb_profiles_is_profile(): bool {
	return is_author() && cb_profiles_get_profile_user_id() > 0;
}
