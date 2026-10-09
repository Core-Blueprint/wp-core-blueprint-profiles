<?php
declare(strict_types=1);

$root = dirname( __DIR__ );
$files = [ $root . '/core-blueprint-profiles.php' ];
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root . '/src', FilesystemIterator::SKIP_DOTS )
);
foreach ( $iterator as $file ) {
	if ( $file->isFile() && 'php' === $file->getExtension() ) {
		$files[] = $file->getPathname();
	}
}

$legacy = [
	'old Base namespace' => 'CB\\Core\\',
	'old extensions hook' => 'cb_core_register_extensions',
	'old settings hook' => 'cb_core_register_settings',
	'old status hook' => 'cb_core_module_status_definitions',
	'old dashboard hook' => 'cb_core_dashboard_register_cards',
	'old capability hook' => 'cb_core_capability_catalog',
];

foreach ( $files as $path ) {
	$content = file_get_contents( $path );
	if ( false === $content ) {
		fwrite( STDERR, "FAIL: unreadable source file: {$path}\n" );
		exit( 1 );
	}
	foreach ( $legacy as $name => $needle ) {
		if ( str_contains( $content, $needle ) ) {
			fwrite( STDERR, "FAIL: {$name} in {$path}\n" );
			exit( 1 );
		}
	}
}

$bootstrap = file_get_contents( $root . '/core-blueprint-profiles.php' );
$integration = file_get_contents( $root . '/src/Integration/CoreBlueprint.php' );
$routing = file_get_contents( $root . '/src/Routing.php' );
$policy = file_get_contents( $root . '/src/ProfilePolicy.php' );
$slug = file_get_contents( $root . '/src/ProfileSlug.php' );
$plugin = file_get_contents( $root . '/src/Plugin.php' );
if ( ! is_string( $bootstrap ) || ! is_string( $integration ) || ! is_string( $routing ) || ! is_string( $policy ) || ! is_string( $slug ) || ! is_string( $plugin ) ) {
	fwrite( STDERR, "FAIL: Profiles contract source unavailable\n" );
	exit( 1 );
}

$checks = [
	'canonical Base namespace' => str_contains( $bootstrap, 'CoreBlueprint\\Core\\ExtensionRegistry' )
		&& str_contains( $bootstrap, 'CoreBlueprint\\Core\\Admin\\SettingsRegistry' ),
	'current extension hook' => str_contains( $integration, "'core_blueprint_register_extensions'" ),
	'current settings hook' => str_contains( $integration, "'core_blueprint_register_settings'" ),
	'current status hook' => str_contains( $integration, "'core_blueprint_module_status_definitions'" ),
	'current shortcut hook' => str_contains( $integration, "'core_blueprint_dashboard_register_cards'" ),
	'current capability hook' => str_contains( $plugin, "'core_blueprint_capability_catalog'" ),
	'public extension identity unchanged' => str_contains( $integration, "'status_id'    => 'profiles'" ),
	'public UI component vocabulary only' => str_contains( $integration, "'components' => [ 'nav-tabs', 'cards', 'fields', 'form-controls', 'disclosure' ]" ),
	'canonical slug only' => ! str_contains( $routing, "get_user_by( 'slug'" )
		&& str_contains( $routing, 'ProfileSlug::find_user_id( $slug )' ),
	'fail-closed request' => str_contains( $routing, "\$vars['error'] = '404'" ),
	'fail-closed runtime' => str_contains( $policy, "cb_profiles_base_ready()" ),
	'fail-closed direct slug helper' => str_contains( $slug, 'private static function base_ready(): bool' ),
];

foreach ( $checks as $label => $passed ) {
	if ( ! $passed ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}

fwrite( STDOUT, "Profiles Base v1 contract regression: PASS\n" );
