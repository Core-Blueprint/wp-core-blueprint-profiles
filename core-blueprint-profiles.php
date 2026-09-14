<?php
/**
 * Plugin Name:       Core Blueprint Profiles
 * Plugin URI:        https://coreblueprint.io
 * Update URI:        https://coreblueprint.io/
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
define( 'CB_PROFILES_MIN_PHP', '8.4' );
define( 'CB_PROFILES_REQUIRED_API', '1.0' );
define( 'CB_PROFILES_FILE', __FILE__ );
define( 'CB_PROFILES_DIR', plugin_dir_path( __FILE__ ) );
define( 'CB_PROFILES_URL', plugin_dir_url( __FILE__ ) );
define( 'CB_PROFILES_BASENAME', plugin_basename( __FILE__ ) );

/* Bootstrap v1 earliest-safe PHP boundary. */
if ( version_compare( PHP_VERSION, CB_PROFILES_MIN_PHP, '<' ) ) {
	register_activation_hook( __FILE__, static function (): void {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		deactivate_plugins( CB_PROFILES_BASENAME );
		wp_die(
			esc_html( sprintf( __( 'requires PHP 8.4 or higher. This server runs PHP %s.', 'core-blueprint-profiles' ), PHP_VERSION ) ),
			esc_html( 'Core Blueprint requirements not met' ),
			[ 'back_link' => true ]
		);
	} );

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

add_action( 'init', static function (): void {
	load_plugin_textdomain( 'core-blueprint-profiles', false, dirname( CB_PROFILES_BASENAME ) . '/languages' );
}, 1 );

/** Product-specific public Base contracts. Not part of generic Bootstrap v1 readiness. */
function cb_profiles_base_contracts_ready(): bool {
	return class_exists( '\\CB\\Core\\ExtensionRegistry' )
		&& class_exists( '\\CB\\Core\\Admin\\SettingsRegistry' );
}

/** Current-time readiness boundary shared by Profiles public APIs and runtime. */
function cb_profiles_runtime_ready(): bool {
	return \CB\Profiles\Support\Requirements::runtime_ready()
		&& cb_profiles_base_contracts_ready();
}

function cb_profiles_dependency_message(): string {
	return __( 'Core Blueprint Base with API 1.0 or newer is required.', 'core-blueprint-profiles' );
}

function cb_profiles_fail_activation( string $message ): void {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	deactivate_plugins( CB_PROFILES_BASENAME );
	wp_die(
		esc_html( $message ),
		esc_html( 'Core Blueprint dependency required' ),
		[ 'back_link' => true ]
	);
}

function cb_profiles_activate(): void {
	if ( ! \CB\Profiles\Support\Requirements::runtime_ready() ) {
		cb_profiles_fail_activation( cb_profiles_dependency_message() );
	}
	if ( ! cb_profiles_base_contracts_ready() ) {
		cb_profiles_fail_activation( cb_profiles_dependency_message() );
	}
	\CB\Profiles\Install::activate();
}
register_activation_hook( __FILE__, 'cb_profiles_activate' );
register_deactivation_hook( __FILE__, [ '\\CB\\Profiles\\Install', 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	if ( ! \CB\Profiles\Support\Requirements::runtime_ready() ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', static function (): void {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>Core Blueprint Profiles:</strong> ';
				echo esc_html( cb_profiles_dependency_message() );
				echo '</p></div>';
			} );
		}
		return;
	}

	if ( ! cb_profiles_base_contracts_ready() ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', static function (): void {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>Core Blueprint Profiles:</strong> ';
				echo esc_html( cb_profiles_dependency_message() );
				echo '</p></div>';
			} );
		}
		return;
	}

	// Suite integrations attach only after Base/Core and Profiles contracts are ready.
	\CB\Profiles\Integration\CoreBlueprint::init();
	\CB\Profiles\Integration\Updates::init();

	\CB\Profiles\Plugin::boot();
}, 20 );

/** Return the current profile user on an author archive, or false. */
function cb_profiles_get_profile_user(): \WP_User|false {
	if ( ! cb_profiles_runtime_ready() ) {
		return false;
	}
	return \CB\Profiles\ProfilePolicy::current_profile_user();
}

/** Return the current profile user ID, or 0 outside a profile. */
function cb_profiles_get_profile_user_id(): int {
	$user = cb_profiles_get_profile_user();
	return $user instanceof \WP_User ? (int) $user->ID : 0;
}

/** Return a profile URL for a WordPress user, or an empty string when unavailable. */
function cb_profiles_get_profile_url( int $user_id ): string {
	if ( ! cb_profiles_runtime_ready() ) {
		return '';
	}
	return \CB\Profiles\Routing::profile_url( $user_id );
}

/** Determine whether a visitor may view a user's public profile. */
function cb_profiles_can_view_profile( int $profile_user_id, ?int $viewer_user_id = null ): bool {
	if ( ! cb_profiles_runtime_ready() ) {
		return false;
	}
	return \CB\Profiles\ProfilePolicy::can_view( $profile_user_id, $viewer_user_id );
}

/** Determine whether the current request is a Core Blueprint profile request. */
function cb_profiles_is_profile(): bool {
	return cb_profiles_runtime_ready() && is_author() && cb_profiles_get_profile_user_id() > 0;
}
