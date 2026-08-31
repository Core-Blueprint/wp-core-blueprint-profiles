<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class Capabilities {
	public const MANAGE = 'cb_profiles_manage';

	/** @param array<string,mixed> $catalog @return array<string,mixed> */
	public static function register_catalog( array $catalog ): array {
		$catalog[ self::MANAGE ] = [
			'label' => __( 'Manage Profiles', 'core-blueprint-profiles' ),
			'group' => __( 'Core Blueprint Profiles', 'core-blueprint-profiles' ),
		];
		return $catalog;
	}
}
