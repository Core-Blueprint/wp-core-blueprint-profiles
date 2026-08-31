<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class Install {
	public const VERSION_OPTION = 'cb_profiles_version';

	public static function activate(): void {
		if ( false === get_option( Settings::OPTION, false ) ) {
			add_option( Settings::OPTION, Settings::defaults(), '', false );
		}
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( Capabilities::MANAGE );
		}
		update_option( self::VERSION_OPTION, CB_PROFILES_VERSION, false );
		Routing::register_rewrite_rules();
		flush_rewrite_rules();
	}

	public static function maybe_upgrade(): void {
		if ( (string) get_option( self::VERSION_OPTION, '' ) === CB_PROFILES_VERSION ) {
			return;
		}
		update_option( self::VERSION_OPTION, CB_PROFILES_VERSION, false );
		set_transient( 'cb_profiles_flush_rewrite_rules', '1', MINUTE_IN_SECONDS );
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
