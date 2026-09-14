<?php
declare(strict_types=1);

namespace CB\Profiles\Integration;

defined( 'ABSPATH' ) || exit;

/** Optional adapter for the central Core Blueprint Updates client. */
final class Updates {
	public const PRODUCT_KEY = 'core-blueprint-profiles';
	public const VENDOR_ID   = 'core-blueprint';

	private static bool $registered = false;

	public static function init(): void {
		if ( ! function_exists( 'cb_profiles_runtime_ready' ) || ! \cb_profiles_runtime_ready() ) {
			return;
		}
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;
		add_action( 'cb_updates_register_products', [ self::class, 'register_product' ] );
	}

	public static function register_product(): void {
		$registry = '\\CB\\Updates\\ProductRegistry';
		if ( ! class_exists( $registry ) ) {
			return;
		}

		$registry::register( [
			'name'          => 'Core Blueprint Profiles',
			'plugin'        => CB_PROFILES_BASENAME,
			'version'       => CB_PROFILES_VERSION,
			'product_key'   => self::PRODUCT_KEY,
			'vendor_id'     => self::VENDOR_ID,
			'software_uuid' => '',
		] );
	}
}
