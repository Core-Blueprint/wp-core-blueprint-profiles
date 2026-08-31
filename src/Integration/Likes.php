<?php
declare(strict_types=1);
namespace CB\Profiles\Integration;
use CB\Profiles\ProfilePolicy;
defined( 'ABSPATH' ) || exit;

final class Likes {
	public static function init(): void {
		add_filter( 'cb_likes_user_can_view_target', [ __CLASS__, 'visibility' ], 10, 4 );
		add_filter( 'cb_likes_user_can_like_target', [ __CLASS__, 'visibility' ], 10, 4 );
	}

	public static function visibility( bool $allowed, int $viewer_user_id, string $target_type, int $target_id ): bool {
		if ( ! $allowed || 'user' !== $target_type ) {
			return $allowed;
		}
		return ProfilePolicy::can_view( $target_id, $viewer_user_id );
	}
}
