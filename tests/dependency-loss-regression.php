<?php
declare(strict_types=1);

if ( defined( 'CB_PROFILES_FILE' ) ) {
	fwrite( STDERR, "Profiles dependency-loss regression must run in an isolated PHP process.\n" );
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

final class WP_User {
	public int $ID = 1;
}

$GLOBALS['cb_profiles_test_hooks'] = [];
$GLOBALS['cb_profiles_forbidden_calls'] = 0;

function plugin_dir_path( string $file ): string { return dirname( $file ) . '/'; }
function plugin_dir_url( string $file ): string { return 'https://example.test/wp-content/plugins/core-blueprint-profiles/'; }
function plugin_basename( string $file ): string { return 'core-blueprint-profiles/core-blueprint-profiles.php'; }
function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void { $GLOBALS['cb_profiles_test_hooks'][] = [ 'action', $hook, $callback, $priority, $accepted_args ]; }
function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void { $GLOBALS['cb_profiles_test_hooks'][] = [ 'filter', $hook, $callback, $priority, $accepted_args ]; }
function register_activation_hook( string $file, callable|string $callback ): void {}
function register_deactivation_hook( string $file, callable|array|string $callback ): void {}
function __( string $text, ?string $domain = null ): string { return $text; }
function esc_html__( string $text, ?string $domain = null ): string { return $text; }
function esc_html( string $text ): string { return $text; }
function is_author(): bool { ++$GLOBALS['cb_profiles_forbidden_calls']; throw new RuntimeException( 'is_author reached while Base is unavailable' ); }
function get_queried_object(): mixed { ++$GLOBALS['cb_profiles_forbidden_calls']; throw new RuntimeException( 'get_queried_object reached while Base is unavailable' ); }
function get_userdata( int $user_id ): mixed { ++$GLOBALS['cb_profiles_forbidden_calls']; throw new RuntimeException( 'get_userdata reached while Base is unavailable' ); }

require dirname( __DIR__ ) . '/core-blueprint-profiles.php';

$expect = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$expect( ! cb_profiles_runtime_ready(), 'Profiles runtime must be unavailable without Base.' );
$expect( false === cb_profiles_get_profile_user(), 'Public profile-user helper must fail closed.' );
$expect( 0 === cb_profiles_get_profile_user_id(), 'Public profile-user ID helper must fail closed.' );
$expect( '' === cb_profiles_get_profile_url( 1 ), 'Public profile URL helper must fail closed.' );
$expect( false === cb_profiles_can_view_profile( 1 ), 'Public visibility helper must fail closed.' );
$expect( false === cb_profiles_is_profile(), 'Public profile-request helper must fail closed.' );

$before = count( $GLOBALS['cb_profiles_test_hooks'] );
\CB\Profiles\Integration\Updates::init();
\CB\Profiles\Plugin::boot();
$expect( $before === count( $GLOBALS['cb_profiles_test_hooks'] ), 'Direct Updates init and Plugin boot must remain inert without Base.' );
$expect( 0 === $GLOBALS['cb_profiles_forbidden_calls'], 'No WordPress profile/user access may escape the readiness gate.' );

echo "Profiles dependency-loss regression PASS\n";
