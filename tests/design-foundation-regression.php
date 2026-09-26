<?php
declare(strict_types=1);

$root        = dirname( __DIR__ );
$integration = file_get_contents( $root . '/src/Integration/CoreBlueprint.php' );
$page        = file_get_contents( $root . '/src/Admin/PageContent.php' );
$assets      = file_get_contents( $root . '/src/Admin/Assets.php' );
$bootstrap   = file_get_contents( $root . '/core-blueprint-profiles.php' );
$css         = file_get_contents( $root . '/assets/css/admin.css' );
$js          = file_get_contents( $root . '/assets/js/admin.js' );

if ( false === $integration || false === $page || false === $assets || false === $bootstrap || false === $css || false === $js ) {
	fwrite( STDERR, "Could not read Profiles source files.\n" );
	exit( 1 );
}

$checks = [
	'SettingsRegistry lifecycle is registered' => str_contains( $integration, "add_action( 'cb_core_register_settings'" ) && str_contains( $integration, 'SettingsRegistry::register(' ),
	'Community group is registered'             => str_contains( $integration, 'SettingsRegistry::GROUP_COMMUNITY' ),
	'Profiles manage capability is preserved'   => str_contains( $integration, "'capability'  => Capabilities::MANAGE" ),
	'PageContent remains provider renderer'      => str_contains( $integration, "'renderer'    => [ PageContent::class, 'render' ]" ),
	'SettingsRegistry requests disclosure'       => str_contains( $integration, "'disclosure'" ),
	'obsolete PageRegistry lifecycle is absent'  => ! str_contains( $integration, 'cb_core_register_pages' ) && ! str_contains( $integration, 'PageRegistry' ) && ! str_contains( $integration, 'PageBase' ),
	'obsolete PageBase shell file is absent'     => ! is_file( $root . '/src/Admin/CoreBlueprintPage.php' ),
	'Base provider shell is not duplicated'      => ! str_contains( $page, 'class="cb-core-title"' ) && ! str_contains( $page, 'class="cb-core-intro"' ) && ! str_contains( $page, 'class="wrap cb-core-wrap' ),
	'Profiles provider root remains scoped'      => str_contains( $page, 'class="cb-profiles-settings-page"' ),
	'canonical server-side tabs are present'     => str_contains( $page, "self::tab_url( 'general' )" ) && str_contains( $page, "self::tab_url( 'restricted' )" ) && str_contains( $page, "self::tab_url( 'usage' )" ) && str_contains( $page, "SettingsRegistry::url( CoreBlueprint::ID, [ 'tab' => \$tab ] )" ),
	'tab query is allowlisted with fallback'     => str_contains( $page, "private const TABS = [ 'general', 'restricted', 'usage' ];" ) && str_contains( $page, "return in_array( \$tab, self::TABS, true ) ? \$tab : 'general';" ),
	'URL is sole tab state authority'            => ! str_contains( $js, 'sessionStorage' ) && ! str_contains( $js, 'cbProfilesAdminTab' ) && ! str_contains( $js, 'data-cb-profiles-tabs' ),
	'active tab semantics are rendered'          => str_contains( $page, 'nav-tab-active' ) && str_contains( $page, 'aria-current="page"' ) && str_contains( $page, 'aria-selected=' ),
	'one settings form is preserved'             => 1 === substr_count( $page, '<form method="post" action="options.php">' ) && str_contains( $page, "settings_fields( 'cb_profiles_settings_group' )" ),
	'save return preserves current tab URL'      => str_contains( $page, 'wp_referer_field();' ),
	'native disclosure markup is present'        => str_contains( $page, '<details class="cb-core-disclosure' ) && str_contains( $page, 'class="cb-core-disclosure__summary"' ),
	'disclosure body uses Foundation markup'     => str_contains( $page, 'class="cb-core-disclosure__body"' ),
	'old module disclosure markup is absent'     => ! str_contains( $page, 'cb-core-module' ) && ! str_contains( $page, 'cb-core-module-collapse' ),
	'old module disclosure CSS is absent'        => ! str_contains( $css, '.cb-core-module' ) && ! str_contains( $css, '.cb-core-chevron' ),
	'old custom disclosure JS is absent'         => ! str_contains( $js, 'setRolesExpanded' ) && ! str_contains( $js, 'data-cb-profiles-role-toggle' ),
	'conditional field JS is preserved'          => str_contains( $js, 'data-cb-profiles-denied-behavior' ) && str_contains( $js, 'data-cb-profiles-template-source' ) && str_contains( $js, 'data-cb-profiles-enabled-control' ) && str_contains( $js, 'data-cb-profiles-role-count' ),
	'ordinary checkbox is not enable variant'    => str_contains( $page, '$classes = \'cb-core-field\' . ( $enabled_control ? \' cb-core-field--enable\' : \'\' );' ),
	'provider assets use canonical identity'     => str_contains( $assets, 'SettingsRegistry::url( CoreBlueprint::ID )' ) && str_contains( $assets, "\$_GET['extension']" ) && ! str_contains( $assets, 'core-blueprint_page_core-blueprint-profiles' ),
	'Base readiness uses SettingsRegistry'       => str_contains( $bootstrap, "class_exists( '\\\\CB\\\\Core\\\\Admin\\\\SettingsRegistry' )" ) && ! str_contains( $bootstrap, "class_exists( '\\\\CB\\\\Core\\\\Admin\\\\PageRegistry' )" ),
	'canonical settings links are used'          => str_contains( $integration, 'SettingsRegistry::url( self::ID )' ) && ! str_contains( $integration, 'admin.php?page=core-blueprint-profiles' ),
	'feature CSS does not redefine Base colors'  => ! preg_match( '/--cb-(?:surface|text|border|accent|success|warning|danger)\s*:/', $css ),
	'feature CSS does not restyle Base buttons'  => ! str_contains( $css, '.button-primary' ) && ! str_contains( $css, '.cb-core-button' ),
	'public version line is unchanged'           => str_contains( $bootstrap, 'Version:           1.0.0-rc1' ) && str_contains( $bootstrap, "define( 'CB_PROFILES_VERSION', '1.0.0-rc1' )" ),
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
