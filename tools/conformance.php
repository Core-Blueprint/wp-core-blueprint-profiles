<?php
declare(strict_types=1);

$tests = glob( dirname( __DIR__ ) . '/tests/*-regression.php' ) ?: [];
sort( $tests );
if ( [] === $tests ) {
	fwrite( STDERR, "No Profiles regression tests found.\n" );
	exit( 1 );
}

foreach ( $tests as $test ) {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $test );
	passthru( $command, $status );
	if ( 0 !== $status ) {
		fwrite( STDERR, 'Profiles conformance failed: ' . basename( $test ) . "\n" );
		exit( $status );
	}
}

echo "Profiles conformance PASS\n";
