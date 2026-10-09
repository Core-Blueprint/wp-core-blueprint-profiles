<?php
declare(strict_types=1);

namespace CoreBlueprint\Core {
	final class ExtensionRegistry {}
}
namespace CoreBlueprint\Core\Admin {
	final class SettingsRegistry {}
}
namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'CB_CORE_API_VERSION', '1.2' );

	$GLOBALS['profiles_test_hooks'] = [];
	function plugin_dir_path( string $file ): string { return dirname( $file ) . '/'; }
	function plugin_dir_url( string $file ): string { return 'https://example.test/wp-content/plugins/core-blueprint-profiles/'; }
	function plugin_basename( string $file ): string { return 'core-blueprint-profiles/core-blueprint-profiles.php'; }
	function add_action( string $name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['profiles_test_hooks'][] = $name;
	}
	function add_filter( string $name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['profiles_test_hooks'][] = $name;
	}
	function register_activation_hook( string $file, callable $callback ): void {}
	function register_deactivation_hook( string $file, callable $callback ): void {}
	function cb_profiles_test_assert( bool $okay, string $reason ): void {
		if ( ! $okay ) {
			fwrite( STDERR, "FAIL: {$reason}\n" );
			exit( 1 );
		}
	}

	require_once dirname( __DIR__ ) . '/core-blueprint-profiles.php';

	cb_profiles_test_assert( cb_profiles_base_ready(), 'Base Core API 1.2 and current classes must satisfy Profiles requirements.' );
	cb_profiles_test_assert( cb_profiles_api_compatible( '1.0', '1.0' ), 'Required Base 1.0 must be accepted.' );
	cb_profiles_test_assert( cb_profiles_api_compatible( '1.2', '1.0' ), 'Compatible Base 1.2 must be accepted.' );
	cb_profiles_test_assert( ! cb_profiles_api_compatible( '0.9', '1.0' ), 'Old major must be rejected.' );
	cb_profiles_test_assert( ! cb_profiles_api_compatible( '2.0', '1.0' ), 'Future major must not be implicitly accepted.' );
	cb_profiles_test_assert( ! cb_profiles_api_compatible( '1.0.1', '1.0' ), 'Invalid API marker must fail closed.' );
	cb_profiles_test_assert( in_array( 'core_blueprint_register_extensions', $GLOBALS['profiles_test_hooks'], true ), 'Public extensions hook must be registered.' );
	cb_profiles_test_assert( in_array( 'core_blueprint_register_settings', $GLOBALS['profiles_test_hooks'], true ), 'Public settings hook must be registered.' );
	cb_profiles_test_assert( in_array( 'core_blueprint_dashboard_register_cards', $GLOBALS['profiles_test_hooks'], true ), 'Public shortcuts hook must be registered.' );
	cb_profiles_test_assert( in_array( 'core_blueprint_module_status_definitions', $GLOBALS['profiles_test_hooks'], true ), 'Public status hook must be registered.' );

	fwrite( STDOUT, "Profiles Base 1.2 bootstrap readiness regression: PASS\n" );
}
