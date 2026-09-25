<?php
/**
 * Plugin Name:       Core Blueprint Profiles
 * Plugin URI:        https://coreblueprint.io
 * Description:       Lightweight privacy-aware WordPress profile pages with configurable URLs, visibility and builder-friendly author archives.
 * Version:           1.0.0-rc1
 * Author:            Core Blueprint
 * Author URI:        https://coreblueprint.io
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       core-blueprint-profiles
 * Domain Path:       /languages
 * Requires at least: 7.0
 * Requires PHP:      8.4
 * Requires Plugins:  core-blueprint
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'CB_PROFILES_VERSION', '1.0.0-rc1' );
define( 'CB_PROFILES_REQUIRED_API', '1.0' );
define( 'CB_PROFILES_FILE', __FILE__ );
define( 'CB_PROFILES_DIR', plugin_dir_path( __FILE__ ) );
define( 'CB_PROFILES_URL', plugin_dir_url( __FILE__ ) );
define( 'CB_PROFILES_BASENAME', plugin_basename( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.4', '<' ) ) {
	add_action( 'admin_notices', static function (): void {
		echo '<div class="notice notice-error"><p><strong>Core Blueprint Profiles:</strong> ';
		printf(
			/* translators: %s: current PHP version */
			esc_html__( 'requires PHP 8.4 or higher. This server runs PHP %s.', 'core-blueprint-profiles' ),
			esc_html( PHP_VERSION )
		);
		echo '</p></div>';
	} );
	return;
}

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

// First-party suite identity and health must be registered independently of
// the heavier Profiles runtime gate below.
\CB\Profiles\Integration\CoreBlueprint::init();

add_action( 'init', static function (): void {
	load_plugin_textdomain( 'core-blueprint-profiles', false, dirname( CB_PROFILES_BASENAME ) . '/languages' );
}, 1 );

function cb_profiles_api_compatible( string $available, string $required ): bool {
	if ( 1 !== preg_match( '/^(\d+)\.(\d+)$/', $available, $a ) || 1 !== preg_match( '/^(\d+)\.(\d+)$/', $required, $r ) ) {
		return false;
	}
	return (int) $a[1] === (int) $r[1] && (int) $a[2] >= (int) $r[2];
}

function cb_profiles_base_ready(): bool {
	return defined( 'CB_CORE_API_VERSION' )
		&& cb_profiles_api_compatible( (string) CB_CORE_API_VERSION, CB_PROFILES_REQUIRED_API )
		&& class_exists( '\\CB\\Core\\ExtensionRegistry' )
		&& class_exists( '\\CB\\Core\\Admin\\SettingsRegistry' );
}

function cb_profiles_activate(): void {
	if ( ! cb_profiles_base_ready() ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		deactivate_plugins( CB_PROFILES_BASENAME );
		wp_die(
			esc_html__( 'Core Blueprint Profiles requires an active, Core API 1.x compatible Core Blueprint Base installation.', 'core-blueprint-profiles' ),
			esc_html__( 'Core Blueprint dependency required', 'core-blueprint-profiles' ),
			[ 'back_link' => true ]
		);
	}
	\CB\Profiles\Install::activate();
}
register_activation_hook( __FILE__, 'cb_profiles_activate' );
register_deactivation_hook( __FILE__, [ '\\CB\\Profiles\\Install', 'deactivate' ] );
add_action( 'plugins_loaded', static function (): void {
	if ( ! cb_profiles_base_ready() ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', static function (): void {
				echo '<div class="notice notice-error"><p><strong>Core Blueprint Profiles:</strong> ';
				echo esc_html__( 'Core Blueprint Base with API 1.0 or newer is required.', 'core-blueprint-profiles' );
				echo '</p></div>';
			} );
		}
		return;
	}
	\CB\Profiles\Plugin::boot();
}, 20 );

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
