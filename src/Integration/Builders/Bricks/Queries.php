<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders\Bricks;

use CB\Profiles\Frontend\Queries as ProfileQueries;

defined( 'ABSPATH' ) || exit;

final class Queries {
	public const PROFILES        = 'cb_profiles_profiles';
	public const CURRENT_PROFILE = 'cb_profiles_current_profile';

	public static function init(): void {
		add_filter( 'bricks/setup/control_options', [ self::class, 'register_query_types' ] );
		add_filter( 'bricks/query/run', [ self::class, 'run' ], 10, 2 );
	}

	/**
	 * @param array<string,mixed> $options
	 * @return array<string,mixed>
	 */
	public static function register_query_types( array $options ): array {
		if ( ! isset( $options['queryTypes'] ) || ! is_array( $options['queryTypes'] ) ) {
			$options['queryTypes'] = [];
		}

		$options['queryTypes'][ self::PROFILES ]        = __( 'Profiles: Members', 'core-blueprint-profiles' );
		$options['queryTypes'][ self::CURRENT_PROFILE ] = __( 'Profiles: Current profile', 'core-blueprint-profiles' );
		return $options;
	}

	/**
	 * @param array<int,mixed> $results
	 * @return array<int,mixed>
	 */
	public static function run( array $results, mixed $query_obj ): array {
		$object_type = is_object( $query_obj ) && isset( $query_obj->object_type )
			? (string) $query_obj->object_type
			: '';

		if ( self::CURRENT_PROFILE === $object_type ) {
			return ProfileQueries::current();
		}
		if ( self::PROFILES === $object_type ) {
			return ProfileQueries::profiles( [ 'number' => self::limit( $query_obj, 30 ) ] );
		}
		return $results;
	}

	private static function limit( mixed $query_obj, int $default ): int {
		if ( ! is_object( $query_obj ) || ! isset( $query_obj->settings ) || ! is_array( $query_obj->settings ) ) {
			return $default;
		}

		$raw = $query_obj->settings['posts_per_page'] ?? $query_obj->settings['count'] ?? $default;
		if ( ! is_scalar( $raw ) || ! is_numeric( $raw ) ) {
			return $default;
		}
		return max( 1, min( 100, (int) $raw ) );
	}
}
