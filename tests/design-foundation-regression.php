<?php
declare(strict_types=1);

$root        = dirname( __DIR__ );
$integration = file_get_contents( $root . '/src/Integration/CoreBlueprint.php' );
$page        = file_get_contents( $root . '/src/Admin/PageContent.php' );
$css         = file_get_contents( $root . '/assets/css/admin.css' );
$js          = file_get_contents( $root . '/assets/js/admin.js' );

if ( false === $integration || false === $page || false === $css || false === $js ) {
	fwrite( STDERR, "Could not read Profiles source files.\n" );
	exit( 1 );
}

$checks = [
	'PageRegistry requests disclosure component' => str_contains( $integration, "'disclosure'" ),
	'canonical page title class is present'      => str_contains( $page, 'class="cb-core-title"' ),
	'canonical page intro class is present'      => str_contains( $page, 'class="cb-core-intro"' ),
	'native disclosure markup is present'        => str_contains( $page, '<details class="cb-core-disclosure' ) && str_contains( $page, 'class="cb-core-disclosure__summary"' ),
	'disclosure body uses Foundation markup'     => str_contains( $page, 'class="cb-core-disclosure__body"' ),
	'old module disclosure markup is absent'     => ! str_contains( $page, 'cb-core-module' ) && ! str_contains( $page, 'cb-core-module-collapse' ),
	'old module disclosure CSS is absent'        => ! str_contains( $css, '.cb-core-module' ) && ! str_contains( $css, '.cb-core-chevron' ),
	'old custom disclosure JS is absent'         => ! str_contains( $js, 'setRolesExpanded' ) && ! str_contains( $js, 'data-cb-profiles-role-toggle' ),
	'ordinary checkbox is not enable variant'    => str_contains( $page, '$classes = \'cb-core-field\' . ( $enabled_control ? \' cb-core-field--enable\' : \'\' );' ),
	'feature CSS does not redefine Base colors'  => ! preg_match( '/--cb-(?:surface|text|border|accent|success|warning|danger)\s*:/', $css ),
	'feature CSS does not restyle Base buttons'  => ! str_contains( $css, '.button-primary' ) && ! str_contains( $css, '.cb-core-button' ),
];

$failed = false;
foreach ( $checks as $label => $passed ) {
	if ( $passed ) {
		echo "PASS: {$label}\n";
		continue;
	}
	$failed = true;
	fwrite( STDERR, "FAIL: {$label}\n" );
}

exit( $failed ? 1 : 0 );
