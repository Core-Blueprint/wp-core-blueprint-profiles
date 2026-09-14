<?php
declare(strict_types=1);

$root = (string) file_get_contents( dirname( __DIR__ ) . '/core-blueprint-profiles.php' );
$adapter = (string) file_get_contents( dirname( __DIR__ ) . '/src/Integration/Updates.php' );

function cb_profiles_updates_expect( bool $condition, string $message ): void {
	if ( $condition ) {
		return;
	}
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

cb_profiles_updates_expect( str_contains( $root, 'Update URI:        https://coreblueprint.io/' ), 'Canonical Update URI header must be declared.' );
cb_profiles_updates_expect( str_contains( $adapter, "[ self::class, 'register_product' ]" ), 'Updates hook must use the canonical parameterless callback.' );
cb_profiles_updates_expect( str_contains( $adapter, "'\\\\CB\\\\Updates\\\\ProductRegistry'" ), 'Adapter must target the central ProductRegistry.' );
cb_profiles_updates_expect( str_contains( $adapter, '$registry::register( [' ), 'Adapter must call the static ProductRegistry contract.' );
cb_profiles_updates_expect( str_contains( $adapter, "'core-blueprint-profiles'" ), 'Canonical Profiles product key must be registered.' );
cb_profiles_updates_expect( str_contains( $adapter, "'core-blueprint'" ), 'Canonical vendor id must be registered.' );
cb_profiles_updates_expect( str_contains( $adapter, 'CB_PROFILES_BASENAME' ), 'Adapter must advertise the actual plugin basename.' );
cb_profiles_updates_expect( str_contains( $adapter, 'CB_PROFILES_VERSION' ), 'Adapter must advertise the installed version.' );
cb_profiles_updates_expect( str_contains( $adapter, "'software_uuid' => ''" ), 'Marketplace UUID must remain learnable rather than hardcoded.' );
cb_profiles_updates_expect( str_contains( $adapter, "! \\cb_profiles_runtime_ready()" ), 'Direct Updates initialization must fail closed outside Profiles readiness.' );

$plugins_loaded = strpos( $root, "add_action( 'plugins_loaded'" );
cb_profiles_updates_expect( false !== $plugins_loaded, 'Canonical plugins_loaded runtime boundary must exist.' );
$runtime = substr( $root, (int) $plugins_loaded );
$generic_gate = strpos( $runtime, '\\CB\\Profiles\\Support\\Requirements::runtime_ready()' );
$product_gate = strpos( $runtime, 'if ( ! cb_profiles_base_contracts_ready() )' );
$core_init = strpos( $runtime, '\\CB\\Profiles\\Integration\\CoreBlueprint::init();' );
$updates_init = strpos( $runtime, '\\CB\\Profiles\\Integration\\Updates::init();' );
$feature_boot = strpos( $runtime, '\\CB\\Profiles\\Plugin::boot();' );
cb_profiles_updates_expect(
	false !== $generic_gate && false !== $product_gate && false !== $core_init && false !== $updates_init && false !== $feature_boot
		&& $generic_gate < $product_gate && $product_gate < $core_init && $core_init < $updates_init && $updates_init < $feature_boot,
	'Bootstrap order must be readiness -> Profiles contracts -> Core integration -> Updates integration -> feature runtime.'
);
cb_profiles_updates_expect( 1 === substr_count( $root, '\\CB\\Profiles\\Integration\\Updates::init();' ), 'Updates adapter must attach exactly once.' );
cb_profiles_updates_expect( 1 === substr_count( $root, '\\CB\\Profiles\\Integration\\CoreBlueprint::init();' ), 'Core Blueprint integration must attach exactly once.' );

echo "Profiles Updates adapter regression PASS\n";
