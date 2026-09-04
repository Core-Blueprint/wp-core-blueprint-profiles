<?php
declare(strict_types=1);

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['cb_profiles_test_actions'] = [];
	$GLOBALS['cb_profiles_test_filters'] = [];

	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $priority, $accepted_args );
		$GLOBALS['cb_profiles_test_actions'][ $hook ][] = $callback;
	}

	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $priority, $accepted_args );
		$GLOBALS['cb_profiles_test_filters'][ $hook ][] = $callback;
	}

	function cb_profiles_assert( bool $condition, string $message ): void {
		if ( ! $condition ) {
			fwrite( STDERR, "FAIL: {$message}\n" );
			exit( 1 );
		}
	}
}

namespace {
	$root = dirname( __DIR__ );
	require_once $root . '/src/Integration/Builders/Bricks/DynamicData.php';
	require_once $root . '/src/Integration/Builders/Bricks/Queries.php';
	require_once $root . '/src/Integration/Builders/Bricks/Conditions.php';
	require_once $root . '/src/Integration/Builders/Bricks/GroupOrder.php';
	require_once $root . '/src/Integration/Builders/Bricks/Bootstrap.php';
	require_once $root . '/src/Integration/Builders/Bootstrap.php';

	use CB\Profiles\Integration\Builders\Bootstrap;

	Bootstrap::init();
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_actions']['init'] ), 'Builder bootstrap must register lazily on init.' );

	Bootstrap::boot();
	cb_profiles_assert( empty( $GLOBALS['cb_profiles_test_filters'] ), 'Profiles must remain inert when Bricks is unavailable.' );

	define( 'BRICKS_VERSION', '2.0-test' );
	Bootstrap::boot();

	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/dynamic_tags_list'] ), 'Bricks dynamic data hooks must register.' );
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/setup/control_options'] ), 'Bricks query types must register.' );
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/query/run'] ), 'Bricks query runner must register.' );
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/conditions/groups'] ), 'Bricks condition group must register.' );
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/conditions/options'] ), 'Bricks condition options must register.' );
	cb_profiles_assert( isset( $GLOBALS['cb_profiles_test_filters']['bricks/conditions/result'] ), 'Bricks condition evaluator must register.' );

	$data_source = file_get_contents( $root . '/src/Frontend/Data.php' );
	$tags_source = file_get_contents( $root . '/src/Integration/Builders/Bricks/DynamicData.php' );
	cb_profiles_assert( is_string( $data_source ) && false === strpos( $data_source, '$user->user_email' ), 'Frontend data must not expose user_email.' );
	cb_profiles_assert( is_string( $data_source ) && false === strpos( $data_source, '$user->user_login' ), 'Frontend data must not expose user_login.' );
	cb_profiles_assert( is_string( $data_source ) && false === strpos( $data_source, '$user->user_nicename' ), 'Frontend data must not expose user_nicename.' );
	cb_profiles_assert( is_string( $tags_source ) && false === strpos( $tags_source, 'cb_profiles_email' ), 'Bricks tags must not expose email.' );
	cb_profiles_assert( is_string( $tags_source ) && false === strpos( $tags_source, 'cb_profiles_login' ), 'Bricks tags must not expose login names.' );
	cb_profiles_assert( is_string( $tags_source ) && false === strpos( $tags_source, 'cb_profiles_nicename' ), 'Bricks tags must not expose user_nicename.' );

	fwrite( STDOUT, "Profiles Bricks adapter regression: PASS\n" );
}
