<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;

use CB\Core\Admin\SettingsRegistry;
use CB\Profiles\Integration\CoreBlueprint;

defined( 'ABSPATH' ) || exit;

final class Assets {
	public static function init(): void { add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ], 20 ); }
	public static function enqueue( string $hook ): void {
		unset( $hook );
		if ( ! self::is_settings_provider_request() ) { return; }
		wp_enqueue_style( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/css/admin.css', [], CB_PROFILES_VERSION );
		wp_enqueue_script( 'cb-profiles-admin', CB_PROFILES_URL . 'assets/js/admin.js', [], CB_PROFILES_VERSION, true );
	}

	private static function is_settings_provider_request(): bool {
		$extension = isset( $_GET['extension'] )
			? sanitize_key( (string) wp_unslash( $_GET['extension'] ) )
			: '';
		if ( CoreBlueprint::ID !== $extension ) {
			return false;
		}

		$query = wp_parse_url( SettingsRegistry::url( CoreBlueprint::ID ), PHP_URL_QUERY );
		if ( ! is_string( $query ) || '' === $query ) {
			return false;
		}

		$canonical = [];
		parse_str( $query, $canonical );
		$canonical_page = isset( $canonical['page'] ) ? sanitize_key( (string) $canonical['page'] ) : '';
		$current_page   = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';

		return '' !== $canonical_page && $canonical_page === $current_page;
	}
}
