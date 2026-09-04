<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders\Bricks;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {
	private static bool $booted = false;

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		DynamicData::init();
		Queries::init();
		Conditions::init();
		GroupOrder::init();
	}
}
