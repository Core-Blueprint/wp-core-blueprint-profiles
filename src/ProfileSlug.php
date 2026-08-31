<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class ProfileSlug {
	public const META_SLUG = '_cb_profiles_public_slug';
	private const MAX_ATTEMPTS = 20;

	public static function get( int $user_id ): string {
		$slug = sanitize_title( (string) get_user_meta( $user_id, self::META_SLUG, true ) );
		if ( '' !== $slug ) {
			$user = get_userdata( $user_id );
			if ( $user instanceof \WP_User && ! self::looks_sensitive( $slug, $user ) ) {
				return $slug;
			}
			update_user_meta( $user_id, self::META_SLUG, '' );
		}
		return self::generate_and_store( $user_id );
	}

	public static function find_user_id( string $slug ): int {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return 0;
		}
		$ids = get_users( [
			'fields'     => 'ID',
			'number'     => 1,
			'meta_key'   => self::META_SLUG,
			'meta_value' => $slug,
		] );
		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}

	public static function validate( int $user_id, string $requested ): string|\WP_Error {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'cb_profiles_invalid_user', __( 'Invalid profile user.', 'core-blueprint-profiles' ) );
		}

		$raw = trim( $requested );
		if ( self::looks_sensitive( $raw, $user ) ) {
			return new \WP_Error( 'cb_profiles_sensitive_slug', __( 'Profile URLs cannot contain or be derived from an email address or login name.', 'core-blueprint-profiles' ) );
		}

		$slug = sanitize_title( $raw );
		if ( '' === $slug || self::is_reserved( $slug ) ) {
			return new \WP_Error( 'cb_profiles_invalid_slug', __( 'Choose a different profile URL.', 'core-blueprint-profiles' ) );
		}
		if ( self::slug_in_use( $slug, $user_id ) ) {
			return new \WP_Error( 'cb_profiles_slug_exists', __( 'That profile URL is already in use.', 'core-blueprint-profiles' ) );
		}
		return $slug;
	}

	public static function set( int $user_id, string $requested ): string|\WP_Error {
		$slug = self::validate( $user_id, $requested );
		if ( is_wp_error( $slug ) ) {
			return $slug;
		}
		update_user_meta( $user_id, self::META_SLUG, $slug );
		return $slug;
	}

	public static function generate_and_store( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return '';
		}

		$format = (string) ( Settings::all()['slug_format'] ?? 'anonymous' );
		$base   = self::candidate_for_format( $user, $format );
		if ( '' === $base ) {
			$slug = self::anonymous_slug();
			for ( $i = 0; $i < self::MAX_ATTEMPTS && self::slug_in_use( $slug, $user_id ); $i++ ) {
				$slug = self::anonymous_slug();
			}
		} else {
			$slug   = $base;
			$suffix = 2;
			while ( self::slug_in_use( $slug, $user_id ) && $suffix <= 9999 ) {
				$slug = $base . '-' . $suffix;
				$suffix++;
			}
			if ( self::slug_in_use( $slug, $user_id ) ) {
				$slug = self::anonymous_slug();
			}
		}

		if ( self::looks_sensitive( $slug, $user ) ) {
			$slug = self::anonymous_slug();
			for ( $i = 0; $i < self::MAX_ATTEMPTS && ( self::slug_in_use( $slug, $user_id ) || self::looks_sensitive( $slug, $user ) ); $i++ ) {
				$slug = self::anonymous_slug();
			}
		}

		update_user_meta( $user_id, self::META_SLUG, $slug );
		return $slug;
	}

	private static function candidate_for_format( \WP_User $user, string $format ): string {
		if ( 'name' === $format ) {
			$first = trim( (string) get_user_meta( $user->ID, 'first_name', true ) );
			$last  = trim( (string) get_user_meta( $user->ID, 'last_name', true ) );
			$raw   = trim( $first . ' ' . $last );
			if ( '' !== $raw && ! self::looks_sensitive( $raw, $user ) ) {
				return sanitize_title( $raw );
			}
			$format = 'display_name';
		}

		if ( 'display_name' === $format ) {
			$raw = trim( (string) $user->display_name );
			if ( '' !== $raw && ! self::looks_sensitive( $raw, $user ) ) {
				$slug = sanitize_title( $raw );
				return self::is_reserved( $slug ) ? '' : $slug;
			}
		}

		return '';
	}

	private static function anonymous_slug(): string {
		$alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
		$max      = strlen( $alphabet ) - 1;
		$slug     = '';
		for ( $i = 0; $i < 10; $i++ ) {
			$slug .= $alphabet[ random_int( 0, $max ) ];
		}
		return $slug;
	}

	private static function slug_in_use( string $slug, int $exclude_user_id = 0 ): bool {
		$ids = get_users( [
			'fields'     => 'ID',
			'number'     => 2,
			'meta_key'   => self::META_SLUG,
			'meta_value' => $slug,
		] );
		foreach ( $ids as $id ) {
			if ( (int) $id !== $exclude_user_id ) {
				return true;
			}
		}
		return false;
	}

	private static function looks_sensitive( string $raw, \WP_User $user ): bool {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return false;
		}
		if ( false !== strpos( $raw, '@' ) || is_email( $raw ) ) {
			return true;
		}
		$slug = sanitize_title( $raw );
		foreach ( [ (string) $user->user_login, (string) $user->user_email ] as $sensitive ) {
			if ( '' !== $sensitive && $slug === sanitize_title( $sensitive ) ) {
				return true;
			}
		}
		return false;
	}

	private static function is_reserved( string $slug ): bool {
		$reserved = [ 'author', 'profile', 'profiles', 'user', 'users', 'member', 'members', 'login', 'logout', 'register', 'account', 'admin', 'wp-admin', 'wp-login', 'feed', 'page', 'search' ];
		$reserved = (array) apply_filters( 'cb_profiles_reserved_slugs', $reserved );
		return in_array( $slug, array_map( 'sanitize_title', $reserved ), true );
	}
}
