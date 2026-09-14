<?php
declare(strict_types=1);

$main = file_get_contents( dirname( __DIR__ ) . '/core-blueprint-profiles.php' );
$requirements = file_get_contents( dirname( __DIR__ ) . '/src/Support/Requirements.php' );
if ( false === $main || false === $requirements ) {
	fwrite( STDERR, "Unable to read Profiles Bootstrap source.\n" );
	exit( 1 );
}

$normalized = str_replace( '\\\\', '\\', $main );
$checks = [
	'public version stays RC1' => str_contains( $main, 'Version:           1.0.0-rc1' ) && str_contains( $main, "define( 'CB_PROFILES_VERSION', '1.0.0-rc1' );" ),
	'PHP boundary constant' => str_contains( $main, "define( 'CB_PROFILES_MIN_PHP', '8.4' );" ),
	'Core API 1.0 contract' => str_contains( $main, "define( 'CB_PROFILES_REQUIRED_API', '1.0' );" ),
	'native Base dependency' => str_contains( $main, 'Requires Plugins:  core-blueprint' ),
	'canonical Requirements gate' => str_contains( $main, '\\CB\\Profiles\\Support\\Requirements::runtime_ready()' ),
	'current-time public readiness' => str_contains( $main, 'function cb_profiles_runtime_ready()' ),
	'product contract gate' => str_contains( $main, 'function cb_profiles_base_contracts_ready()' ),
	'ExtensionRegistry required' => str_contains( $normalized, 'CB\\Core\\ExtensionRegistry' ),
	'SettingsRegistry required' => str_contains( $normalized, 'CB\\Core\\Admin\\SettingsRegistry' ),
	'no obsolete Base product-version gate' => ! str_contains( $main, 'CB_CORE_VERSION' ),
	'no retired inline API helper' => ! str_contains( $main, 'function cb_profiles_api_compatible(' ),
	'no retired combined base helper' => ! str_contains( $main, 'function cb_profiles_base_ready(' ),
	'Requirements owns API compatibility' => str_contains( $requirements, 'public static function api_compatible(' ),
];

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( [] !== $failed ) {
	fwrite( STDERR, "Profiles Base dependency regression failed:\n- " . implode( "\n- ", $failed ) . "\n" );
	exit( 1 );
}

echo "Profiles Base dependency regression PASS\n";
