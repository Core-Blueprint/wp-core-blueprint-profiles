<?php
declare(strict_types=1);

namespace CB\Profiles\Support;

defined( 'ABSPATH' ) || exit;

/** Bootstrap v1 dependency readiness only: PHP, Base presence and Core API. */
final class Requirements {
	public static function api_compatible( string $available, string $required ): bool {
		if ( 1 !== preg_match( '/^(\d+)\.(\d+)$/', $available, $available_match ) ) {
			return false;
		}
		if ( 1 !== preg_match( '/^(\d+)\.(\d+)$/', $required, $required_match ) ) {
			return false;
		}

		return (int) $available_match[1] === (int) $required_match[1]
			&& (int) $available_match[2] >= (int) $required_match[2];
	}

	/** @return string[] Stable machine-readable issue IDs. */
	public static function issues(): array {
		$issues = [];

		if ( version_compare( PHP_VERSION, CB_PROFILES_MIN_PHP, '<' ) ) {
			$issues[] = 'php-version';
		}

		if ( ! defined( 'CB_CORE_API_VERSION' ) ) {
			$issues[] = 'base-missing';
			return $issues;
		}

		if ( ! self::api_compatible( (string) CB_CORE_API_VERSION, CB_PROFILES_REQUIRED_API ) ) {
			$issues[] = 'base-api-incompatible';
		}

		return array_values( array_unique( $issues ) );
	}

	public static function runtime_ready(): bool {
		return [] === self::issues();
	}
}
