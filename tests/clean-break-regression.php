<?php
declare(strict_types=1);

$routing = (string) file_get_contents( dirname( __DIR__ ) . '/src/Routing.php' );
$main = (string) file_get_contents( dirname( __DIR__ ) . '/core-blueprint-profiles.php' );

$checks = [
	'no native nicename fallback' => ! str_contains( $routing, "get_user_by( 'slug'" ) && ! str_contains( $routing, 'legacy_user' ),
	'canonical Profiles slug lookup retained' => str_contains( $routing, 'ProfileSlug::find_user_id( $slug )' ),
	'unknown profile slug fails 404' => str_contains( $routing, "$vars['error'] = '404';" ),
	'pre-v1 inline API compatibility helper removed' => ! str_contains( $main, 'function cb_profiles_api_compatible(' ),
	'pre-v1 combined Base readiness helper removed' => ! str_contains( $main, 'function cb_profiles_base_ready(' ),
];

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( [] !== $failed ) {
	fwrite( STDERR, "Profiles clean-break regression failed:\n- " . implode( "\n- ", $failed ) . "\n" );
	exit( 1 );
}

echo "Profiles clean-break regression PASS\n";
