<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;
defined( 'ABSPATH' ) || exit;

final class Assets {
	public static function init(): void { add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ], 20 ); }
	public static function enqueue( string $hook ): void {
		if ( ! self::is_screen( $hook ) ) { return; }
		$deps = [];
		if ( wp_style_is( 'cb-core-css-tokens', 'registered' ) || wp_style_is( 'cb-core-css-tokens', 'enqueued' ) ) { $deps[] = 'cb-core-css-tokens'; }
		wp_enqueue_style( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/css/admin.css', $deps, CB_PROFILES_VERSION );
		wp_enqueue_script( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/js/admin.js', [], CB_PROFILES_VERSION, true );
	}
	private static function is_screen( string $hook ): bool {
		if ( in_array( $hook, [ 'settings_page_core-blueprint-profiles', 'core-blueprint_page_core-blueprint-profiles' ], true ) ) { return true; }
		return isset( $_GET['page'] ) && 'core-blueprint-profiles' === sanitize_key( wp_unslash( (string) $_GET['page'] ) );
	}
}
