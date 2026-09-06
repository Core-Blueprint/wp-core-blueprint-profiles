<?php
declare(strict_types=1);

namespace CB\Profiles\Integration;

defined( 'ABSPATH' ) || exit;

final class Updates {
	private const PRODUCT_KEY = 'core-blueprint-profiles';
	private const VENDOR_ID   = 'core-blueprint';

	public static function init(): void {
		add_action( 'cb_updates_register_products', [ self::class, 'register' ] );
	}

	/** @param object $registry Updates product registry. */
	public static function register( object $registry ): void {
		if ( ! method_exists( $registry, 'register' ) ) {
			return;
		}

		$registry->register(
			self::PRODUCT_KEY,
			[
				'name'        => 'Core Blueprint Profiles',
				'plugin'      => CB_PROFILES_BASENAME,
				'version'     => CB_PROFILES_VERSION,
				'product_key' => self::PRODUCT_KEY,
				'vendor_id'   => self::VENDOR_ID,
			]
		);
	}
}
