<?php
declare(strict_types=1);

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'CB_PROFILES_BASENAME', 'core-blueprint-profiles/core-blueprint-profiles.php' );

	$GLOBALS['cb_test_actions'] = [];
	$GLOBALS['cb_test_filters'] = [];
	$GLOBALS['cb_test_option']  = [];

	function add_action( string $hook, callable $callback ): void {
		$GLOBALS['cb_test_actions'][ $hook ][] = $callback;
	}
	function add_filter( string $hook, callable $callback ): void {
		$GLOBALS['cb_test_filters'][ $hook ][] = $callback;
	}
	function admin_url( string $path = '' ): string {
		return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
	}
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
	function esc_url( string $url ): string {
		return $url;
	}
	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}
	function get_option( string $name, mixed $default = false ): mixed {
		return 'cb_profiles_settings' === $name ? $GLOBALS['cb_test_option'] : $default;
	}
	function wp_parse_args( array $args, array $defaults = [] ): array {
		return array_merge( $defaults, $args );
	}
	function sanitize_title( string $title ): string {
		$title = strtolower( trim( $title ) );
		return preg_replace( '/[^a-z0-9]+/', '-', $title ) ?: '';
	}

	function cb_assert( bool $condition, string $message ): void {
		if ( ! $condition ) {
			fwrite( STDERR, "FAIL: {$message}\n" );
			exit( 1 );
		}
	}
}

namespace CB\Core {
	final class ExtensionRegistry {
		public static array $registered = [];
		public static function register( array $definition ): void {
			self::$registered[] = $definition;
		}
	}
}

namespace CB\Core\Dashboard {
	final class CardRegistry {
		public static array $shortcuts = [];
		public static function register_shortcut( string $extension_id, array $shortcut ): void {
			self::$shortcuts[ $extension_id ][] = $shortcut;
		}
	}
}

namespace {
	require_once dirname( __DIR__ ) . '/src/Settings.php';
	require_once dirname( __DIR__ ) . '/src/Capabilities.php';
	require_once dirname( __DIR__ ) . '/src/Integration/CoreBlueprint.php';

	use CB\Core\Dashboard\CardRegistry;
	use CB\Core\ExtensionRegistry;
	use CB\Profiles\Integration\CoreBlueprint;

	CoreBlueprint::init();
	cb_assert( isset( $GLOBALS['cb_test_actions']['cb_core_register_extensions'] ), 'ExtensionRegistry hook must be registered during lightweight init.' );
	cb_assert( isset( $GLOBALS['cb_test_filters']['cb_core_module_status_definitions'] ), 'Health-provider hook must be registered during lightweight init.' );
	cb_assert( isset( $GLOBALS['cb_test_actions']['cb_core_dashboard_register_cards'] ), 'Dashboard shortcut hook must be registered during lightweight init.' );

	CoreBlueprint::register_extension();
	$registration = ExtensionRegistry::$registered[0] ?? [];
	cb_assert( 'core-blueprint-profiles' === ( $registration['id'] ?? '' ), 'Profiles extension identity must remain canonical.' );
	cb_assert( 'profiles' === ( $registration['status_id'] ?? '' ), 'Profiles must declare status_id=profiles.' );

	$definitions = CoreBlueprint::register_status_definition( [] );
	cb_assert( isset( $definitions['profiles']['provider'] ), 'Profiles must register a matching health provider.' );

	$GLOBALS['cb_test_option'] = [ 'enabled' => false ];
	$status = CoreBlueprint::extension_status();
	cb_assert( 'off' === $status['state'], 'Disabled profile pages must project off state.' );
	cb_assert( 'Profile pages are currently disabled.' === $status['detail'], 'Disabled detail must use existing product copy.' );

	$GLOBALS['cb_test_option'] = [ 'enabled' => true, 'visibility' => 'public' ];
	$status = CoreBlueprint::extension_status();
	cb_assert( 'ok' === $status['state'], 'Public profiles must be healthy.' );
	cb_assert( 'Profiles are currently visible to everyone.' === $status['detail'], 'Public visibility detail must be factual.' );

	$GLOBALS['cb_test_option'] = [ 'enabled' => true, 'visibility' => 'logged_in' ];
	$status = CoreBlueprint::extension_status();
	cb_assert( 'ok' === $status['state'], 'Logged-in-only profiles are a valid healthy privacy policy.' );
	cb_assert( 'Profiles are limited to logged-in users.' === $status['detail'], 'Restricted visibility detail must be factual.' );

	CoreBlueprint::register_dashboard_shortcuts();
	$shortcut = CardRegistry::$shortcuts['core-blueprint-profiles'][0] ?? [];
	cb_assert( 'settings' === ( $shortcut['id'] ?? '' ), 'Profiles must expose a Settings dashboard shortcut.' );
	cb_assert( 'cb_profiles_manage' === ( $shortcut['capability'] ?? '' ), 'Settings shortcut must preserve Profiles capability boundary.' );

	$bootstrap = file_get_contents( dirname( __DIR__ ) . '/core-blueprint-profiles.php' );
	$plugin    = file_get_contents( dirname( __DIR__ ) . '/src/Plugin.php' );
	cb_assert( false !== strpos( $bootstrap, '\\CB\\Profiles\\Integration\\CoreBlueprint::init();' ), 'Suite integration must initialize before the plugins_loaded runtime gate.' );
	cb_assert( false === strpos( $plugin, 'CoreBlueprint::init();' ), 'Product runtime must not register suite hooks a second time.' );

	fwrite( STDOUT, "Profiles dashboard health regression: PASS\n" );
}
