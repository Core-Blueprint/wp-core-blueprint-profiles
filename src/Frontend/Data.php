<?php
declare(strict_types=1);

namespace CB\Profiles\Frontend;

use CB\Profiles\ProfilePolicy;
use CB\Profiles\ProfileSlug;
use CB\Profiles\Routing;
use WP_Error;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Builder-neutral public profile data contract.
 *
 * Sensitive WordPress identity fields such as user_login, user_email and
 * user_nicename are deliberately never exposed by this API.
 */
final class Data {

	/**
	 * @return array{
	 *   id:int,
	 *   display_name:string,
	 *   first_name:string,
	 *   last_name:string,
	 *   bio:string,
	 *   profile_url:string,
	 *   profile_slug:string,
	 *   avatar_url:string,
	 *   website_url:string
	 * }|WP_Error
	 */
	public static function get( int $user_id, ?int $viewer_user_id = null ): array|WP_Error {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error( 'cb_profiles_invalid_profile', __( 'Profile not found.', 'core-blueprint-profiles' ) );
		}

		if ( ! ProfilePolicy::can_view( $user_id, $viewer_user_id ) ) {
			return new WP_Error( 'cb_profiles_profile_not_viewable', __( 'This profile is not available to the current visitor.', 'core-blueprint-profiles' ) );
		}

		return [
			'id'           => (int) $user->ID,
			'display_name' => sanitize_text_field( (string) $user->display_name ),
			'first_name'   => sanitize_text_field( (string) get_user_meta( $user_id, 'first_name', true ) ),
			'last_name'    => sanitize_text_field( (string) get_user_meta( $user_id, 'last_name', true ) ),
			'bio'          => wp_kses_post( (string) get_user_meta( $user_id, 'description', true ) ),
			'profile_url'  => Routing::profile_url( $user_id ),
			'profile_slug' => ProfileSlug::get( $user_id ),
			'avatar_url'   => (string) get_avatar_url( $user_id, [ 'size' => 512 ] ),
			'website_url'  => esc_url_raw( (string) $user->user_url ),
		];
	}

	public static function field( int $user_id, string $field, ?int $viewer_user_id = null ): string|int|null {
		$data = self::get( $user_id, $viewer_user_id );
		if ( is_wp_error( $data ) || ! array_key_exists( $field, $data ) ) {
			return null;
		}

		$value = $data[ $field ];
		return is_int( $value ) || is_string( $value ) ? $value : null;
	}
}
