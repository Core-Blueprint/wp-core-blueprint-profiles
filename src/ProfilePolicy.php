<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class ProfilePolicy {
	public const META_ENABLED = '_cb_profiles_public_enabled';

	public static function current_profile_user(): \WP_User|false {
		if ( ! is_author() ) {
			return false;
		}
		$object = get_queried_object();
		return $object instanceof \WP_User ? $object : false;
	}

	public static function individual_enabled( int $user_id ): bool {
		$value = get_user_meta( $user_id, self::META_ENABLED, true );
		return '' === $value || '1' === (string) $value;
	}

	public static function is_profile_available( int $user_id ): bool {
		$settings = Settings::all();
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User || ! self::individual_enabled( $user_id ) ) {
			return false;
		}
		$excluded = (array) $settings['excluded_roles'];
		foreach ( (array) $user->roles as $role ) {
			if ( in_array( $role, $excluded, true ) ) {
				return false;
			}
		}
		return (bool) apply_filters( 'cb_profiles_profile_available', true, $user_id );
	}

	public static function can_view( int $profile_user_id, ?int $viewer_user_id = null ): bool {
		if ( ! self::is_profile_available( $profile_user_id ) ) {
			return false;
		}
		$settings = Settings::all();
		$viewer_user_id ??= get_current_user_id();
		$allowed = 'public' === $settings['visibility'] || $viewer_user_id > 0;
		return (bool) apply_filters( 'cb_profiles_can_view_profile', $allowed, $profile_user_id, $viewer_user_id );
	}

	public static function denial_reason( int $profile_user_id ): string {
		if ( ! self::is_profile_available( $profile_user_id ) ) {
			return 'hidden';
		}
		return self::can_view( $profile_user_id ) ? 'allowed' : 'restricted';
	}
}
