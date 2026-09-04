<?php
declare(strict_types=1);

namespace CB\Profiles\Frontend;

use CB\Profiles\ProfilePolicy;
use CB\Profiles\Settings;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Builder-neutral member query contract.
 *
 * Results are privacy-safe arrays rather than raw WP_User objects so consumers
 * do not accidentally gain access to login, email or nicename fields.
 */
final class Queries {

	/**
	 * Query profiles that the current viewer is allowed to see.
	 *
	 * @param array{number?:int,offset?:int,search?:string,role?:string,orderby?:string,order?:string} $args
	 * @return array<int,array<string,int|string>>
	 */
	public static function profiles( array $args = [] ): array {
		$settings = Settings::all();
		if ( empty( $settings['enabled'] ) ) {
			return [];
		}
		if ( 'logged_in' === (string) ( $settings['visibility'] ?? 'public' ) && get_current_user_id() <= 0 ) {
			return [];
		}

		$number  = max( 1, min( 100, absint( $args['number'] ?? 30 ) ) );
		$offset  = max( 0, absint( $args['offset'] ?? 0 ) );
		$search  = sanitize_text_field( (string) ( $args['search'] ?? '' ) );
		$role    = sanitize_key( (string) ( $args['role'] ?? '' ) );
		$orderby = sanitize_key( (string) ( $args['orderby'] ?? 'display_name' ) );
		$order   = 'DESC' === strtoupper( (string) ( $args['order'] ?? 'ASC' ) ) ? 'DESC' : 'ASC';

		$orderby_map = [
			'display_name' => 'display_name',
			'id'           => 'ID',
			'registered'   => 'registered',
			'post_count'   => 'post_count',
		];
		$orderby = $orderby_map[ $orderby ] ?? 'display_name';

		$query_args = [
			'number'      => $number,
			'offset'      => $offset,
			'orderby'     => $orderby,
			'order'       => $order,
			'role__not_in' => array_values( array_filter( array_map( 'sanitize_key', (array) ( $settings['excluded_roles'] ?? [] ) ) ) ),
			'meta_query'  => [
				'relation' => 'OR',
				[
					'key'     => ProfilePolicy::META_ENABLED,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'   => ProfilePolicy::META_ENABLED,
					'value' => '1',
				],
			],
		];

		if ( '' !== $search ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = [ 'display_name' ];
		}
		if ( '' !== $role && wp_roles()->is_role( $role ) ) {
			$query_args['role'] = $role;
		}

		$users = get_users( $query_args );
		if ( ! is_array( $users ) ) {
			return [];
		}

		$viewer_user_id = get_current_user_id();
		$results        = [];
		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User || ! ProfilePolicy::can_view( (int) $user->ID, $viewer_user_id ) ) {
				continue;
			}
			$data = Data::get( (int) $user->ID, $viewer_user_id );
			if ( is_wp_error( $data ) ) {
				continue;
			}
			$results[] = [ 'user_id' => (int) $user->ID ] + $data;
		}
		return $results;
	}

	/** @return array<int,array<string,int|string>> */
	public static function current(): array {
		$user = ProfilePolicy::current_profile_user();
		if ( ! $user instanceof WP_User ) {
			return [];
		}
		$data = Data::get( (int) $user->ID );
		if ( is_wp_error( $data ) ) {
			return [];
		}
		return [ [ 'user_id' => (int) $user->ID ] + $data ];
	}
}
