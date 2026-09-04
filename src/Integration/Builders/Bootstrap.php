<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders;

defined( 'ABSPATH' ) || exit;

/**
 * Optional builder integration boundary.
 */
final class Bootstrap {
	private static bool $booted = false;

	public static function init(): void {
		add_action( 'init', [ self::class, 'boot' ], 50 );
	}

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}

		if ( ! defined( 'BRICKS_VERSION' ) && ! class_exists( '\Bricks\Query' ) ) {
			return;
		}

		self::$booted = true;
		\CB\Profiles\Integration\Builders\Bricks\Bootstrap::init();
	}
}
