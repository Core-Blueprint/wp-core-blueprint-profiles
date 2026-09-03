<?php
declare(strict_types=1);
namespace CB\Profiles;

use CB\Profiles\Admin\Assets;
use CB\Profiles\Integration\CoreBlueprint;
use CB\Profiles\Integration\Likes;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		Install::maybe_upgrade();
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_filter( 'option_page_capability_cb_profiles_settings_group', static fn(): string => Capabilities::MANAGE );
		add_filter( 'cb_core_capability_catalog', [ Capabilities::class, 'register_catalog' ] );

		Routing::init();
		DeniedRenderer::init();
		UserProfile::init();
		CoreBlueprint::init();
		Assets::init();
		Likes::init();

		do_action( 'cb_profiles_loaded' );
	}

	public static function register_settings(): void {
		register_setting( 'cb_profiles_settings_group', Settings::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [ Settings::class, 'sanitize' ],
			'default'           => Settings::defaults(),
		] );
	}
}
