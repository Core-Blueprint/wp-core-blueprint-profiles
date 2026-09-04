<?php
declare(strict_types=1);

namespace CB\Profiles\Frontend;

use CB\Profiles\ProfilePolicy;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Builder-neutral profile condition contract.
 */
final class Conditions {

	public static function is_profile_request(): bool {
		$user = ProfilePolicy::current_profile_user();
		return $user instanceof WP_User && ProfilePolicy::is_profile_available( (int) $user->ID );
	}

	public static function profile_available( int $user_id ): bool {
		return $user_id > 0 && ProfilePolicy::is_profile_available( $user_id );
	}

	public static function can_view( int $user_id, ?int $viewer_user_id = null ): bool {
		return $user_id > 0 && ProfilePolicy::can_view( $user_id, $viewer_user_id );
	}

	public static function is_own_profile( int $user_id ): bool {
		return $user_id > 0 && get_current_user_id() === $user_id;
	}

	public static function has_role( int $user_id, string $role ): bool {
		if ( $user_id <= 0 || '' === $role || ! ProfilePolicy::can_view( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		return $user instanceof WP_User && in_array( $role, (array) $user->roles, true );
	}
}
