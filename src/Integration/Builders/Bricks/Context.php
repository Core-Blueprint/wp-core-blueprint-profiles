<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders\Bricks;

use CB\Profiles\ProfilePolicy;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the canonical profile user for Bricks archive and query-loop contexts.
 */
final class Context {

	public static function user( mixed $context = null ): WP_User|false {
		$loop = self::loop_object();
		$user = self::from_value( $loop );
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$user = self::from_value( $context );
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$current = ProfilePolicy::current_profile_user();
		return $current instanceof WP_User ? $current : false;
	}

	public static function user_id( mixed $context = null ): int {
		$user = self::user( $context );
		return $user instanceof WP_User ? (int) $user->ID : 0;
	}

	private static function loop_object(): mixed {
		if ( class_exists( '\Bricks\Query' ) && method_exists( '\Bricks\Query', 'get_loop_object' ) ) {
			return \Bricks\Query::get_loop_object();
		}
		return null;
	}

	private static function from_value( mixed $value ): WP_User|false {
		if ( $value instanceof WP_User ) {
			return $value;
		}

		if ( is_array( $value ) && isset( $value['user_id'] ) && is_numeric( $value['user_id'] ) ) {
			$user = get_userdata( (int) $value['user_id'] );
			return $user instanceof WP_User ? $user : false;
		}

		if ( is_object( $value ) && ! $value instanceof \WP_Post && isset( $value->user_id ) && is_numeric( $value->user_id ) ) {
			$user = get_userdata( (int) $value->user_id );
			return $user instanceof WP_User ? $user : false;
		}

		return false;
	}
}
