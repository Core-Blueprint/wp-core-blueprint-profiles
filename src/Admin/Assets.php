<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;
defined( 'ABSPATH' ) || exit;

final class Assets {
	public static function init(): void { add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ], 20 ); }
	public static function enqueue( string $hook ): void {
		if ( ! self::is_screen( $hook ) ) { return; }
		wp_enqueue_style( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/css/admin.css', [], CB_PROFILES_VERSION );
		wp_enqueue_script( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/js/admin.js', [], CB_PROFILES_VERSION, true );
	}
	private static function is_screen( string $hook ): bool {
		if ( 'core-blueprint_page_core-blueprint-profiles' === $hook ) { return true; }
		return isset( $_GET['page'] ) && 'core-blueprint-profiles' === sanitize_key( wp_unslash( (string) $_GET['page'] ) );
	}
}
