<?php
declare(strict_types=1);

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '' );
	}

	function wp_unslash( mixed $value ): mixed {
		return $value;
	}

	function cb_profiles_tab_assert( bool $condition, string $message ): void {
		if ( ! $condition ) {
			fwrite( STDERR, "FAIL: {$message}\n" );
			exit( 1 );
		}
	}
}

namespace CB\Core\Admin {
	final class SettingsRegistry {
		/** @param array<string,scalar> $query */
		public static function url( string $extension_id, array $query = [] ): string {
			$url = 'https://example.test/wp-admin/admin.php?page=core-blueprint-extensions&extension=' . $extension_id;
			foreach ( $query as $key => $value ) {
				$url .= '&' . $key . '=' . $value;
			}
			return $url;
		}
	}
}

namespace CB\Profiles\Integration {
	final class CoreBlueprint {
		public const ID = 'core-blueprint-profiles';
	}
}

namespace {
	require_once dirname( __DIR__ ) . '/src/Admin/PageContent.php';

	use CB\Profiles\Admin\PageContent;

	$current_tab = new \ReflectionMethod( PageContent::class, 'current_tab' );
	$tab_url     = new \ReflectionMethod( PageContent::class, 'tab_url' );

	$cases = [
		[ 'query' => [], 'expected' => 'general' ],
		[ 'query' => [ 'tab' => 'general' ], 'expected' => 'general' ],
		[ 'query' => [ 'tab' => 'restricted' ], 'expected' => 'restricted' ],
		[ 'query' => [ 'tab' => 'usage' ], 'expected' => 'usage' ],
		[ 'query' => [ 'tab' => 'whatever' ], 'expected' => 'general' ],
	];

	foreach ( $cases as $case ) {
		$_GET = $case['query'];
		cb_profiles_tab_assert( $case['expected'] === $current_tab->invoke( null ), 'Tab routing must resolve to ' . $case['expected'] . '.' );
	}

	$restricted_url = (string) $tab_url->invoke( null, 'restricted' );
	cb_profiles_tab_assert( str_contains( $restricted_url, 'extension=core-blueprint-profiles' ), 'Tab URLs must preserve the Profiles provider identity.' );
	cb_profiles_tab_assert( str_contains( $restricted_url, 'tab=restricted' ), 'Restricted Access must expose a canonical tab deep link.' );

	$invalid_url = (string) $tab_url->invoke( null, 'whatever' );
	cb_profiles_tab_assert( str_contains( $invalid_url, 'tab=general' ), 'Invalid programmatic tab URLs must fall back to General.' );

	$page = file_get_contents( dirname( __DIR__ ) . '/src/Admin/PageContent.php' );
	$js   = file_get_contents( dirname( __DIR__ ) . '/assets/js/admin.js' );
	cb_profiles_tab_assert( false !== $page && false !== $js, 'Profiles tab regression must be able to read renderer and admin JS.' );
	cb_profiles_tab_assert( str_contains( $page, "SettingsRegistry::url( CoreBlueprint::ID, [ 'tab' => \$tab ] )" ), 'All settings tab URLs must use SettingsRegistry::url().' );
	cb_profiles_tab_assert( str_contains( $page, 'wp_referer_field();' ), 'Settings form must preserve the canonical tab URL across options.php redirects.' );
	cb_profiles_tab_assert( str_contains( $page, 'aria-current="page"' ) && str_contains( $page, 'nav-tab-active' ), 'Active tabs must be marked semantically and visually.' );
	cb_profiles_tab_assert( ! str_contains( $js, 'sessionStorage' ) && ! str_contains( $js, 'cbProfilesAdminTab' ), 'JavaScript must not keep a second tab-state source of truth.' );
	cb_profiles_tab_assert( str_contains( $js, 'data-cb-profiles-denied-behavior' ) && str_contains( $js, 'data-cb-profiles-role-count' ), 'Conditional settings and role-count JavaScript must remain present.' );

	fwrite( STDOUT, "Profiles settings tab routing regression: PASS\n" );
}
