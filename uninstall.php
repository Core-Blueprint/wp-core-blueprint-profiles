<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'cb_profiles_settings' );
delete_option( 'cb_profiles_version' );
delete_transient( 'cb_profiles_flush_rewrite_rules' );
delete_metadata( 'user', 0, '_cb_profiles_public_enabled', '', true );
delete_metadata( 'user', 0, '_cb_profiles_public_slug', '', true );

$wp_roles = wp_roles();
foreach ( array_keys( $wp_roles->roles ) as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		$role->remove_cap( 'cb_profiles_manage' );
	}
}
